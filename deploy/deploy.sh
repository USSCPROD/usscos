#!/usr/bin/env bash
#
# USSCOS — deploy the current main branch
#
#   ssh deploy@<droplet>
#   cd /var/www/usscos && bash deploy/deploy.sh
#
# Pulls, installs dependencies, fixes permissions, and reloads PHP.
# Migrations are NOT run automatically — see the note at the end.

set -euo pipefail

APP_DIR="/var/www/usscos"
cd "$APP_DIR"

say() { echo -e "\n\033[1;34m==>\033[0m $1"; }

# Refuse to deploy over uncommitted edits made directly on the server
if [[ -n "$(git status --porcelain)" ]]; then
    echo "Working tree is dirty — someone edited files on the server."
    echo "Commit, stash, or discard them before deploying:"
    git status --short
    exit 1
fi

BEFORE="$(git rev-parse --short HEAD)"

say "Fetching latest main"
git fetch origin main
git reset --hard origin/main

AFTER="$(git rev-parse --short HEAD)"

if [[ "$BEFORE" == "$AFTER" ]]; then
    say "Already up to date at $AFTER"
else
    say "Updated $BEFORE → $AFTER"
    git --no-pager log --oneline "$BEFORE..$AFTER" | sed 's/^/    /'
fi

say "Installing dependencies"
composer install --no-dev --optimize-autoloader --no-interaction --quiet

say "Fixing permissions"
# Directories get setgid so new files inherit the group; files stay non-executable.
# A blanket `chmod -R 2775` would set the executable bit on tracked files, which git
# records as a modification — making the next deploy fail its own dirty-tree check.
chown -R deploy:www-data storage public/uploads 2>/dev/null || true
find storage public/uploads -type d -exec chmod 2775 {} + 2>/dev/null || true
find storage public/uploads -type f -exec chmod 0664 {} + 2>/dev/null || true

say "Reloading PHP-FPM"
sudo systemctl reload php8.3-fpm

# Migrations stay manual on purpose: schema changes should be applied
# deliberately, with a database backup taken first.
if [[ -n "$(git --no-pager diff --name-only "$BEFORE" "$AFTER" -- database/migrations 2>/dev/null)" ]]; then
    echo
    echo "  ⚠  This deploy includes NEW MIGRATIONS:"
    git --no-pager diff --name-only "$BEFORE" "$AFTER" -- database/migrations | sed 's/^/       /'
    echo
    echo "     Back up first, then run:  php database/migrate.php"
fi

say "Deployed."
