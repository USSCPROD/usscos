#!/usr/bin/env bash
#
# USSCOS — one-time server provisioning for Ubuntu 24.04 LTS
#
# Installs nginx, PHP 8.3-FPM, MySQL, Composer, certbot, and a firewall,
# then creates the deploy user and directory layout.
#
# Run ONCE on a fresh droplet, as root:
#   bash provision.sh
#
# Everything here is idempotent — re-running is safe.

set -euo pipefail

DEPLOY_USER="deploy"
APP_DIR="/var/www/usscos"
SITE_DIR="/var/www/usscos.com"
DB_NAME="usscos"
DB_USER="usscos"

say() { echo -e "\n\033[1;34m==>\033[0m $1"; }

if [[ $EUID -ne 0 ]]; then
    echo "Run as root: sudo bash provision.sh"
    exit 1
fi

# ---------------------------------------------------------------- packages
say "Updating packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq

say "Installing nginx, PHP 8.3, MySQL, and tools"
apt-get install -y -qq software-properties-common
add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1 || true
apt-get update -qq

apt-get install -y -qq \
    nginx \
    mysql-server \
    php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath \
    git unzip curl fail2ban ufw certbot python3-certbot-nginx

say "Installing Composer"
if ! command -v composer >/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- \
        --install-dir=/usr/local/bin --filename=composer
fi

# ---------------------------------------------------------------- user + dirs
say "Creating deploy user and directories"
if ! id -u "$DEPLOY_USER" >/dev/null 2>&1; then
    adduser --disabled-password --gecos "" "$DEPLOY_USER"
    usermod -aG www-data "$DEPLOY_USER"
fi

mkdir -p "$APP_DIR" "$SITE_DIR"
# Uploads live OUTSIDE the web root — served through PHP so access can be checked
mkdir -p /var/www/usscos-storage/uploads
chown -R "$DEPLOY_USER":www-data "$APP_DIR" "$SITE_DIR" /var/www/usscos-storage
chmod -R 2775 /var/www/usscos-storage

# Let the deploy user reach the server over SSH with root's authorised keys
if [[ -f /root/.ssh/authorized_keys ]]; then
    mkdir -p "/home/$DEPLOY_USER/.ssh"
    cp /root/.ssh/authorized_keys "/home/$DEPLOY_USER/.ssh/"
    chown -R "$DEPLOY_USER":"$DEPLOY_USER" "/home/$DEPLOY_USER/.ssh"
    chmod 700 "/home/$DEPLOY_USER/.ssh"
    chmod 600 "/home/$DEPLOY_USER/.ssh/authorized_keys"
fi

# ---------------------------------------------------------------- php tuning
say "Tuning PHP for file uploads"
PHP_INI="/etc/php/8.3/fpm/php.ini"
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 32M/' "$PHP_INI"
sed -i 's/^post_max_size = .*/post_max_size = 40M/'             "$PHP_INI"
sed -i 's/^memory_limit = .*/memory_limit = 256M/'              "$PHP_INI"
sed -i 's/^max_execution_time = .*/max_execution_time = 120/'   "$PHP_INI"
sed -i 's/^;\?expose_php = .*/expose_php = Off/'                "$PHP_INI"

# ---------------------------------------------------------------- mysql
say "Securing MySQL and creating the database"
DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | head -c 24)"

mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "${DB_PASS}" > /root/.usscos-db-password
chmod 600 /root/.usscos-db-password

# ---------------------------------------------------------------- firewall
say "Configuring firewall and fail2ban"
ufw allow OpenSSH   >/dev/null
ufw allow 'Nginx Full' >/dev/null
ufw --force enable  >/dev/null
systemctl enable --now fail2ban >/dev/null

# ---------------------------------------------------------------- services
say "Restarting services"
systemctl enable --now php8.3-fpm nginx mysql >/dev/null
systemctl restart php8.3-fpm nginx

# ---------------------------------------------------------------- done
cat <<DONE

────────────────────────────────────────────────────────────────
  Provisioning complete.

  App directory     : ${APP_DIR}
  Website directory : ${SITE_DIR}
  Uploads (private) : /var/www/usscos-storage/uploads
  Deploy user       : ${DEPLOY_USER}

  Database  : ${DB_NAME}
  Username  : ${DB_USER}
  Password  : saved to /root/.usscos-db-password

  Next:
    1. Copy the nginx configs from deploy/ into /etc/nginx/sites-available
       and symlink them into sites-enabled
    2. Clone the repo:
         sudo -u ${DEPLOY_USER} git clone <repo-url> ${APP_DIR}
    3. Create ${APP_DIR}/.env from .env.example using the password above
    4. Point DNS at this droplet, then:
         certbot --nginx -d os.usscos.com -d usscos.com -d www.usscos.com
────────────────────────────────────────────────────────────────

DONE
