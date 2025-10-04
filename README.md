# Laravel Backend Setup

This README explains how to set up and configure the backend using **Laravel** with **Sanctum** for API authentication and additional useful packages.

---

## 1. Create New Laravel Project

```sh
composer create-project laravel/laravel ./
```

This command installs a fresh Laravel application in the current directory.

---

## 2. Install Sanctum (API Authentication)

```sh
composer require laravel/sanctum
php artisan install:api
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

* `laravel/sanctum`: Package for API token authentication.
* `install:api`: Installs basic API scaffolding.
* `vendor:publish`: Publishes Sanctum config and migration files.
* `migrate`: Runs database migrations (creates tables for users, tokens, etc.).

---

## 3. File Storage Linking

```sh
php artisan storage:link
```

Creates a symbolic link between `public/storage` and `storage/app/public` so uploaded files can be accessed via the browser.

---

## 4. Database Seeding

```sh
php artisan db:seed
```

Seeds the database with initial test data defined in `DatabaseSeeder`.

---

## 5. Queue Worker

```sh
php artisan queue:work
```

Starts processing queued jobs (for emails, notifications, etc.).

---

## 6. Composer & Artisan Maintenance Commands

```sh
composer dump-autoload
php artisan config:clear
php artisan optimize:clear
php artisan key:generate
```

* **dump-autoload**: Regenerates Composer autoloader (after adding new classes).
* **config:clear**: Clears cached configuration.
* **optimize:clear**: Clears all cached files (routes, config, views).
* **key:generate**: Generates a new application key for encryption.

---

## 7. Additional Composer Packages

```sh
composer require endroid/qr-code
composer require simplesoftwareio/simple-qrcode
composer require fruitcake/laravel-cors
composer require barryvdh/laravel-dompdf
```

* **endroid/qr-code**: Generate QR codes in PHP.
* **simple-qrcode**: Laravel wrapper for QR code generation.
* **fruitcake/laravel-cors**: Handles Cross-Origin Resource Sharing (CORS) for API requests.
* **barryvdh/laravel-dompdf**: Generate PDFs from Blade templates.

---

## 8. API Authentication & Sanctum

### How it Works:

* When a user registers or logs in, the backend issues a **token**.
* This token must be included in every API request header as:

  ```
  Authorization: Bearer <token>
  ```
* Sanctum validates the token and ensures the request is authenticated.

### Endpoints Overview:

* **Register (`POST /api/register`)** → create new user, return token & user info.
* **Login (`POST /api/login`)** → authenticate user, return token & user info.
* **User Info (`GET /api/user`)** → get details of the authenticated user.
* **Logout (`POST /api/logout`)** → revoke current token.

### Error Handling:

* If token is missing or invalid → **401 Unauthorized**.
* Validation errors (e.g., invalid email/password) → **422 Unprocessable Entity**.

---

## 9. Notes

* Make sure `.env` file is properly configured with **database connection**, **queue driver**, and **storage settings**.
* Run `php artisan serve` to start the development server.
* Run migrations whenever new tables are added.
* Always clear caches (`php artisan optimize:clear`) after config or route changes.

---
