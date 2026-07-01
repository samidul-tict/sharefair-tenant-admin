# Server setup (Share Fair Tenant Admin)

## PHP extensions required

The app uses **PostgreSQL** and **database sessions**. The PHP process (PHP-FPM, Apache, or the one used by PM2) must have the **PDO PostgreSQL** extension enabled.

### Check which PHP is used by the web app

```bash
# If using PHP-FPM
php-fpm8.2 -v   # or php-fpm8.1, etc.

# If using Apache mod_php
apache2ctl -M | grep php

# CLI (for artisan)
php -v
```

### Install PDO PostgreSQL (Ubuntu/Debian)

```bash
# PHP 8.2 (adjust version to match your server)
sudo apt update
sudo apt install php8.2-pgsql

# Or for PHP 8.1
# sudo apt install php8.1-pgsql
```

### Verify the driver is loaded

```bash
php -m | grep -i pdo_pgsql
# Should output: pdo_pgsql
```

### Restart the PHP / web server

```bash
# PHP-FPM
sudo systemctl restart php8.2-fpm

# Apache
sudo systemctl restart apache2

# If using PM2 with php artisan serve, restart PM2
pm2 restart all
```

## .env on the server

- Set `DB_CONNECTION=pgsql` (not `sqlite`).
- Set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` for your PostgreSQL database.
- After any .env change: `php artisan config:clear` then `php artisan config:cache`.

## "could not find driver"

This means the PHP that serves the app does not have the correct PDO driver:

- For PostgreSQL: install `php8.x-pgsql` and restart PHP-FPM (or the web server).
- If you see `SQLiteConnector` in the error, the app is still using SQLite; set `DB_CONNECTION=pgsql` in `.env` and clear config cache.
