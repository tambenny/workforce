# Workforce

Workforce is a Laravel 12 employee scheduling and time-clock application. It includes:

- staff management
- locations and kiosks
- schedule approval flow
- time punches and summaries
- a basic kiosk login (`staff_id + pin`)
- a camera kiosk variant for photo-based verification

## Stack

- PHP `^8.2`
- Laravel `^12`
- MySQL or MariaDB
- Node/Vite for frontend assets
- XAMPP is the current local development setup

## Local Setup

1. Install PHP, Composer, Node.js, and MySQL/XAMPP.
2. Clone the repo.
3. Create the environment file:

```powershell
Copy-Item .env.example .env
```

4. Update `.env` for your local machine.
5. Install PHP dependencies:

```powershell
composer install
```

6. Generate the app key:

```powershell
php artisan key:generate
```

7. Run database migrations:

```powershell
php artisan migrate
```

8. Create the public storage symlink:

```powershell
php artisan storage:link
```

9. Install frontend dependencies and build assets:

```powershell
npm install
npm run build
```

## Recommended Local `.env`

See [environment-checklist.md](/c:/xampp/htdocs/workforce/docs/environment-checklist.md).

For this project, the important items are usually:

- `APP_URL=https://workforce.local`
- MySQL connection values
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=database`

## Local URL

The current local setup uses:

```text
https://workforce.local
```

If you are using the camera kiosk, HTTPS matters for webcam access.

## Kiosk Modes

### Basic Kiosk

Original flow using `staff_id + pin`:

```text
/kiosk
```

### Camera Kiosk

Photo-based kiosk flow:

```text
/kiosk-camera
```

The first kiosk visit needs a valid kiosk token in the URL:

```text
https://workforce.local/kiosk?token=YOUR_TOKEN
https://workforce.local/kiosk-camera?token=YOUR_TOKEN
```

After the token is accepted, the kiosk cookie is set and later visits can work without adding the token every time.

## Photo Storage

Camera punch photos are stored on the `public` disk under:

```text
storage/app/public/kiosk-punches
```

Those files are intentionally ignored by git.

## Backups

Local backups are stored under:

```text
backups/
```

That folder is ignored by git and should stay local.

## Git / GitHub

The repository is initialized locally and `main` is connected to:

```text
https://github.com/tambenny/workforce.git
```

Normal workflow:

```powershell
git status
git add .
git commit -m "Describe the change"
git push
```

See [git-workflow.md](/c:/xampp/htdocs/workforce/docs/git-workflow.md) for a slightly safer workflow.

## Files Ignored From Git

The project already ignores:

- `.env`
- `vendor/`
- `node_modules/`
- `backups/`
- `public/storage`
- `storage/app/public/kiosk-punches`
- runtime cache/session/view files
- SQL dumps

## Notes

- Do not commit production or local `.env` files.
- Do not commit employee punch photos unless you intentionally want them in source control.
- For camera kiosk debugging, keep changes small and test after each change. The camera page is sensitive to layout and browser camera behavior.
