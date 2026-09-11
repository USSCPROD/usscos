# Local Development Setup

For the production server, see [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Requirements

- PHP 8.3+ (production runs `ea-php83`)
- MySQL 8.0+
- Apache with `mod_rewrite`
- Composer

## First-time setup

### 1. Install dependencies

```bash
composer install
```

### 2. Configure the environment

```bash
cp .env.example .env
```

Set the database credentials and `APP_URL`.

### 3. Point BASE_PATH at your local checkout

`public/index.php` **hardcodes the server path**:

```php
define('BASE_PATH', '/home/t2a2ymc1f3z4/public_html/businessos');
define('PUBLIC_PATH', '/home/t2a2ymc1f3z4/public_html/usscos.com');
```

Change these locally — and never upload your local copy of this file to the server.

### 4. Create the database and run migrations

```bash
php database/migrate.php
```

### 5. Seed the admin user

```bash
php database/seeders/DatabaseSeeder.php
```

### 6. Serve the `public/` directory

```apache
<VirtualHost *:80>
    DocumentRoot /path/to/USSCOS/public
    ServerName usscos.local

    <Directory /path/to/USSCOS/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

`AllowOverride All` matters — without it `.htaccess` is ignored and every route except
`/` returns 404.

Or with PHP's built-in server:

```bash
php -S localhost:8000 -t public
```

### 7. Writable directories

```bash
chmod -R 775 storage/ public/uploads/
```

## Then

Open your configured URL — you'll be redirected to `/login`.

Read [CLAUDE.md](CLAUDE.md) before changing code.
