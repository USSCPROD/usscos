#!/usr/bin/env bash
#
# USSCOS database backup.
#
# Dumps the database, compresses it, and rotates old copies. Designed to run from cron:
# silent on success, loud and non-zero on failure.
#
#   bash deploy/backup.sh              take a backup
#   bash deploy/backup.sh --verify     take a backup AND prove it restores
#   bash deploy/backup.sh --verify-only <file>   test an existing dump, take nothing new
#
# Why --verify matters: an untested backup is a guess. It restores the dump into a
# scratch database, counts the rows, compares them with the live database, and drops
# the scratch copy. That is the only way to know the file is usable.
#
# Credentials come from .env and are passed via a temporary defaults-file, never on the
# command line — command-line arguments are visible to every user on the box via `ps`.
#
# Dumps contain every customer, invoice and price in the business, so the directory and
# the files are owner-only.
#
# OFF-SITE COPY: if /root/.s3cfg exists and SPACES_BUCKET is set below, each dump is
# also uploaded to DigitalOcean Spaces. A backup that lives only on the machine it
# protects does not survive losing that machine. Upload failure is reported but does not
# fail the run — a local backup that exists beats no backup at all.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/usscos}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
MIN_BYTES="${MIN_BYTES:-1000000}"      # a real dump of this database is tens of MB
SPACES_BUCKET="${SPACES_BUCKET:-}"     # e.g. usscos-backups — empty disables upload
SPACES_PREFIX="${SPACES_PREFIX:-db}"
S3CFG="${S3CFG:-/root/.s3cfg}"
REMOTE_RETENTION_DAYS="${REMOTE_RETENTION_DAYS:-60}"

BOLD=$'\033[1m'; RED=$'\033[0;31m'; GREEN=$'\033[0;32m'; YELLOW=$'\033[0;33m'; OFF=$'\033[0m'
say()  { echo "${BOLD}==>${OFF} $*"; }
warn() { echo "${YELLOW}  ! $*${OFF}"; }
die()  { echo "${RED}  ✗ $*${OFF}" >&2; exit 1; }
ok()   { echo "${GREEN}  ✓ $*${OFF}"; }

VERIFY=0
VERIFY_ONLY=""
for arg in "$@"; do
    case "$arg" in
        --verify)      VERIFY=1 ;;
        --verify-only) VERIFY_ONLY="next" ;;
        *)             [ "$VERIFY_ONLY" = "next" ] && VERIFY_ONLY="$arg" ;;
    esac
done

# ---------------------------------------------------------------- credentials

[ -f "$APP_DIR/.env" ] || die "No .env at $APP_DIR/.env"

env_get() {
    # Read one key from .env without sourcing it — .env may contain characters that
    # would be interpreted by the shell.
    sed -n "s/^$1=//p" "$APP_DIR/.env" | head -1 | sed 's/^"//; s/"$//; s/^'"'"'//; s/'"'"'$//'
}

DB_HOST="$(env_get DB_HOST)";     DB_HOST="${DB_HOST:-localhost}"
DB_PORT="$(env_get DB_PORT)";     DB_PORT="${DB_PORT:-3306}"
DB_NAME="$(env_get DB_DATABASE)"
DB_USER="$(env_get DB_USERNAME)"
DB_PASS="$(env_get DB_PASSWORD)"

[ -n "$DB_NAME" ] || die "DB_DATABASE is not set in .env"
[ -n "$DB_USER" ] || die "DB_USERNAME is not set in .env"

# Credentials in a 600 file, removed on exit however we leave.
CNF="$(mktemp)"
chmod 600 "$CNF"
cleanup() { rm -f "$CNF"; }
trap cleanup EXIT INT TERM

cat > "$CNF" <<EOF
[client]
host=$DB_HOST
port=$DB_PORT
user=$DB_USER
password=$DB_PASS
EOF

# ---------------------------------------------------------------- verify helper

# Verification needs to CREATE a scratch database, which the application's MySQL user
# deliberately cannot do — it is granted only its own schema, and widening that to make
# backups testable would be the wrong trade. So verification uses the local root socket
# instead, which is available to cron (running as root) but to nobody over the network.
admin_mysql() {
    if [ "$ADMIN_SOCKET" -eq 1 ]; then
        mysql "$@"
    else
        mysql --defaults-extra-file="$CNF" "$@"
    fi
}

ADMIN_SOCKET=0
mysql -e "SELECT 1" >/dev/null 2>&1 && ADMIN_SOCKET=1

# Restore a dump into a scratch database and compare row counts with the live one.
verify_dump() {
    local file="$1"
    local scratch="${DB_NAME}_verify_$$"

    say "Verifying $(basename "$file")"

    gzip -t "$file" || die "Not a valid gzip file — the dump is corrupt"

    if [ "$ADMIN_SOCKET" -eq 0 ]; then
        warn "Cannot verify: creating a scratch database needs admin access."
        warn "Run this as root on the server, where socket auth is available."
        return 0
    fi

    admin_mysql -e "DROP DATABASE IF EXISTS \`$scratch\`; CREATE DATABASE \`$scratch\`"
    # Drop the scratch database whatever happens from here.
    trap 'mysql -e "DROP DATABASE IF EXISTS \`'"$scratch"'\`" 2>/dev/null; cleanup' EXIT INT TERM

    if ! gunzip -c "$file" | admin_mysql "$scratch"; then
        die "Restore FAILED — this dump would not have saved you"
    fi

    local bad=0
    printf '  %-26s %12s %12s\n' "TABLE" "LIVE" "RESTORED"
    for t in invoices invoice_line_items customers products sales_orders sales_reps users; do
        local live restored
        live=$(admin_mysql -N -e "SELECT COUNT(*) FROM \`$DB_NAME\`.\`$t\`" 2>/dev/null || echo "n/a")
        restored=$(admin_mysql -N -e "SELECT COUNT(*) FROM \`$scratch\`.\`$t\`" 2>/dev/null || echo "n/a")
        if [ "$live" = "$restored" ]; then
            printf '  %-26s %12s %12s  ok\n' "$t" "$live" "$restored"
        else
            printf '  %-26s %12s %12s  %sMISMATCH%s\n' "$t" "$live" "$restored" "$RED" "$OFF"
            bad=1
        fi
    done

    local live_tables restored_tables
    live_tables=$(admin_mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME'")
    restored_tables=$(admin_mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$scratch'")
    printf '  %-26s %12s %12s  %s\n' "(table count)" "$live_tables" "$restored_tables" \
        "$([ "$live_tables" = "$restored_tables" ] && echo ok || { bad=1; echo MISMATCH; })"

    admin_mysql -e "DROP DATABASE IF EXISTS \`$scratch\`"
    trap cleanup EXIT INT TERM

    [ "$bad" -eq 0 ] || die "Verification failed — do not rely on this backup"
    ok "Restore verified"
}

# ---------------------------------------------------------------- verify-only

if [ -n "$VERIFY_ONLY" ] && [ "$VERIFY_ONLY" != "next" ]; then
    [ -f "$VERIFY_ONLY" ] || die "No such file: $VERIFY_ONLY"
    verify_dump "$VERIFY_ONLY"
    exit 0
fi

# ---------------------------------------------------------------- take the backup

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

STAMP="$(date +%Y%m%d-%H%M%S)"
OUT="$BACKUP_DIR/${DB_NAME}-${STAMP}.sql.gz"
TMP="$OUT.partial"

say "Backing up $DB_NAME"

# --single-transaction gives a consistent snapshot without locking tables, so the app
# keeps working during the dump. Routines, triggers and events are included because a
# schema-only restore would otherwise be silently incomplete.
if ! mysqldump --defaults-extra-file="$CNF" \
        --single-transaction \
        --quick \
        --routines --triggers --events \
        --default-character-set=utf8mb4 \
        "$DB_NAME" 2>"$TMP.err" | gzip -9 > "$TMP"; then
    warn "mysqldump reported:"; sed 's/^/    /' "$TMP.err" >&2 || true
    rm -f "$TMP" "$TMP.err"
    die "Backup FAILED — previous backups left untouched"
fi
rm -f "$TMP.err"

SIZE=$(stat -c %s "$TMP" 2>/dev/null || stat -f %z "$TMP")
if [ "$SIZE" -lt "$MIN_BYTES" ]; then
    rm -f "$TMP"
    die "Dump is only $SIZE bytes — suspiciously small, refusing to keep it or rotate"
fi

mv "$TMP" "$OUT"
chmod 600 "$OUT"
ok "$(basename "$OUT")  ($(numfmt --to=iec "$SIZE" 2>/dev/null || echo "$SIZE bytes"))"

[ "$VERIFY" -eq 1 ] && verify_dump "$OUT"

# ---------------------------------------------------------------- off-site copy

if [ -n "$SPACES_BUCKET" ] && [ -f "$S3CFG" ]; then
    say "Uploading to Spaces: s3://$SPACES_BUCKET/$SPACES_PREFIX/"
    if s3cmd --config="$S3CFG" put "$OUT" "s3://$SPACES_BUCKET/$SPACES_PREFIX/$(basename "$OUT")" >/dev/null 2>&1; then
        ok "Uploaded $(basename "$OUT")"

        # Expire remote copies too, or the bill grows forever. Kept longer than local
        # because off-site is the copy that matters when the droplet is gone.
        CUTOFF=$(date -d "-${REMOTE_RETENTION_DAYS} days" +%Y-%m-%d 2>/dev/null || echo "")
        if [ -n "$CUTOFF" ]; then
            s3cmd --config="$S3CFG" ls "s3://$SPACES_BUCKET/$SPACES_PREFIX/" 2>/dev/null \
              | awk -v c="$CUTOFF" '$1 < c {print $NF}' \
              | while read -r old; do
                    s3cmd --config="$S3CFG" del "$old" >/dev/null 2>&1 && say "Expired remote $(basename "$old")"
                done
        fi
    else
        warn "Upload FAILED — the local backup is fine, but there is no off-site copy tonight."
        warn "Check: s3cmd --config=$S3CFG ls"
    fi
elif [ -n "$SPACES_BUCKET" ]; then
    warn "SPACES_BUCKET is set but $S3CFG is missing — no off-site copy."
fi

# ---------------------------------------------------------------- rotation

# Only ever rotate AFTER a good backup exists, and never leave zero behind.
KEEP_COUNT=$(find "$BACKUP_DIR" -name "${DB_NAME}-*.sql.gz" -type f | wc -l)
if [ "$KEEP_COUNT" -gt 1 ]; then
    DELETED=$(find "$BACKUP_DIR" -name "${DB_NAME}-*.sql.gz" -type f -mtime "+$RETENTION_DAYS" -print -delete | wc -l)
    [ "$DELETED" -gt 0 ] && say "Removed $DELETED backup(s) older than $RETENTION_DAYS days"
fi

TOTAL=$(find "$BACKUP_DIR" -name "${DB_NAME}-*.sql.gz" -type f | wc -l)
say "$TOTAL backup(s) held in $BACKUP_DIR"
