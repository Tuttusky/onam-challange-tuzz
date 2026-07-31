# Installation Guide — Onam Dare Challenge Platform

## Requirements

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- MySQL 8+ (recommended) or SQLite (local dev)
- XAMPP / Laragon / Docker

## Quick Start

```bash
# 1. Clone & install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database (MySQL)
# Create database: onam_dare
# Update .env:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=onam_dare
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 4. Migrate & seed
php artisan migrate --seed

# 5. Storage link
php artisan storage:link

# 6. Build assets
npm run build

# 7. Run (development)
composer dev
# OR separately:
php artisan serve
php artisan queue:listen
npm run dev
```

## Default Admin Login

- URL: `http://localhost:8000/admin/login`
- Email: `admin@onamdare.com`
- Password: `password`

## Public SPA

- URL: `http://localhost:8000/`

## Windows PHP 8.3 Note

If XAMPP ships PHP 8.2, use winget PHP 8.3 and prepend to PATH:

```powershell
$env:Path = "C:\Users\<YOU>\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_...\;" + $env:Path
```

Enable extensions in `php.ini`: openssl, curl, mbstring, zip, pdo_mysql, pdo_sqlite, fileinfo.

## Scheduled Tasks

Add to cron (production):

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Queue Worker

```
php artisan queue:work --tries=3
```
