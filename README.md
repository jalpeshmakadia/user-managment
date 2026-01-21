# User Management (Laravel)

Simple user management dashboard built with Laravel 12. It supports CRUD, search, soft deletes, avatar uploads, and a welcome email that includes a generated password.

## Features

- Create, edit, delete, and restore users (soft deletes)
- Search by name, email, or phone with AJAX updates
- Avatar upload stored on the public disk
- Welcome email sent on user creation
- Seeder to generate sample users

## Requirements

- PHP 8.2+
- Composer
- Node.js + npm (optional, only if you plan to build assets)
- MySQL (default) or any supported database

## Setup

1) Install dependencies

```
composer install
```

2) Create the environment file

If you have an `.env.example`, copy it to `.env`. Otherwise create `.env` and add the minimal settings below.

```
APP_NAME="User Management"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_pass

MAIL_MAILER=log
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

3) Generate the app key

```
php artisan key:generate
```

4) Prepare the database

The app defaults to SQLite and expects `database/database.sqlite`.

```
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
```

5) Link storage for avatars

```
php artisan storage:link
```

6) Run the app

```
php artisan serve
```

Open `http://localhost:8000` in your browser.

## Configuration

### Database

To use MySQL or another database, update `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_pass
```

Then run:

```
php artisan migrate --seed
```

### Mail

Welcome emails are sent when a user is created. For local development you can keep `MAIL_MAILER=log` to write emails to `storage/logs/laravel.log`. For SMTP, update the usual `MAIL_*` values in `.env`.

## Optional: Frontend assets

The UI uses Bootstrap and jQuery via CDN, so no asset build is required to run the app. If you want to work on Vite assets:

```
npm install
npm run dev
```

## Useful Commands

- `php artisan migrate --seed` - migrate schema and seed 25 users
- `php artisan storage:link` - expose public avatars
- `php artisan serve` - start the dev server
