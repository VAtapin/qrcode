# Q to me

PHP 8.2+ service for moderated short links and QR codes.

The main page shows a public gallery of approved QR codes. New links are created on `/new`, receive a QR code immediately, and start redirecting only after approval.

## Features

- Public gallery on `/` with approved public QR codes only.
- Search by title and short code with `/?search=solar`.
- Quick filters: all, public, latest.
- Pagination: 20 QR codes per page.
- Public and private links through `is_public`.
- Result page after creation with immediate QR download.
- QR page on `/qr/{short_code}` for approved links.
- Admin moderation: pending, approved, rejected, blocked.
- Optional instant approval when an admin creates a link.
- E-mail notifications after submission and approval.
- SMTP settings with safe fallback to `storage/logs/mail.log`.
- Editable short-code blacklist in `/admin/blacklist`.
- Click statistics: count and last click date.
- Admin deletion removes both the database record and the generated QR PNG.

## Installation

1. Install dependencies:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. Edit `config/config.php`:

   ```php
   'app' => [
       'base_url' => 'https://q-2.me',
       'timezone' => 'Europe/Berlin',
       'secret_salt' => 'change-this-long-random-secret',
   ],
   'db' => [
       'dsn' => 'mysql:host=localhost;dbname=DB_NAME;charset=utf8mb4',
       'user' => 'DB_USER',
       'password' => 'DB_PASSWORD',
   ],
   ```

3. Import the schema on a fresh install:

   ```bash
   mysql -u DB_USER -p DB_NAME < database/schema.sql
   ```

   For an existing installation, apply:

   ```bash
   mysql -u DB_USER -p DB_NAME < database/update_gallery_email_blacklist.sql
   ```

4. Create the first administrator:

   ```bash
   php database/create_admin.php admin strong-password
   ```

5. Make storage writable:

   ```bash
   chmod -R 775 storage/qrcodes storage/logs
   ```

6. Keep the document root on `public` when possible. If the host points to the project root, the root `.htaccess` forwards requests to `public/index.php` and blocks service folders.

## SMTP / Gmail

Notifications are sent with PHPMailer when SMTP is configured:

```php
'mail' => [
    'from' => 'no-reply@q-2.me',
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-account@gmail.com',
        'password' => 'gmail-app-password-without-spaces',
        'encryption' => 'tls',
    ],
],
```

If SMTP is empty or sending fails, the message is written to:

```text
storage/logs/mail.log
```

For Gmail, enable 2-Step Verification and create an App Password. Google shows it in groups like `dlni ixzt jkrj dowc`; store it without spaces: `dlniixztjkrjdowc`.

The application does not fail because of mail delivery problems.

## Spam Protection

The public create form uses a hidden honeypot field, a minimum form-fill time check, the existing 5 submissions per 10 minutes limit, and a 20 submissions per day limit per IP hash. Admin-created links are not limited by these public form checks.

## Public Gallery

Only links with these values are shown:

```text
status = approved
is_public = 1
```

Private approved links still redirect by short code, but do not appear in the gallery.

## QR Downloads

- Result page: `/result/{short_code}`
- Approved QR page: `/qr/{short_code}`
- Download QR: `/qr/{short_code}/download`

QR download is available immediately after creation.

## Blacklist

Admins can manage forbidden short codes at:

```text
/admin/blacklist
```

The initial schema seeds examples such as `admin`, `login`, `logout`, `api`, `config`, `root`, and `system`.
