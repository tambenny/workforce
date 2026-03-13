# Environment Checklist

Use this before running the app locally or on a new server.

## App

- `APP_NAME=Workforce`
- `APP_ENV=local` for local development
- `APP_DEBUG=true` only for local development
- `APP_URL=https://workforce.local` for the current local XAMPP setup

## Database

Use MySQL or MariaDB values that match your XAMPP database:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workforce
DB_USERNAME=root
DB_PASSWORD=
```

## Session / Cache / Queue

Current app defaults rely on database-backed drivers:

```dotenv
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Make sure the related migration tables exist.

## Mail

Local safe default:

```dotenv
MAIL_MAILER=log
```

## Storage

Run:

```powershell
php artisan storage:link
```

This is required for public photo access through `/storage/...`.

## Kiosk Camera

For local webcam testing:

- use `https://workforce.local`
- trust the local certificate in Windows/browser
- allow browser camera permission

## Final Local Commands

```powershell
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
npm install
npm run build
```
