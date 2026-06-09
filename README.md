# Q to me

Q to me is a moderated short-link and QR-code service for `q-2.me`.

The home page shows a public gallery of approved QR codes. New links are created on `/new`, receive a downloadable QR code immediately, and start redirecting after moderation.

## Features

- Public QR-code gallery with search and pagination.
- Public and private approved links.
- Dedicated QR page: `/qr/{short_code}`.
- QR download immediately after link creation.
- Admin moderation: pending, approved, rejected, blocked.
- Optional immediate approval when an administrator creates a link.
- User and administrator e-mail notifications.
- HTML e-mails with buttons, links, and an embedded QR-code preview.
- PHPMailer SMTP support, including Gmail app passwords.
- Mail log fallback in `storage/logs/mail.log` when SMTP is not configured or delivery fails.
- Honeypot, time trap, and daily rate limit for public submissions.
- Editable blacklist for reserved short codes.
- Basic click statistics with click count, last click date, IP hash, and user agent.
- Multilingual interface: German, Russian, and English. Default language: German.
- Editable application settings in the admin panel.
- Editable Impressum and privacy page texts stored in the database.

## Requirements

- PHP 8.2 or newer
- Composer
- MySQL or MariaDB
- Apache with `mod_rewrite`

## Installation

Clone or upload the project files to your server.

Install dependencies:

```bash
composer install --no-dev --optimize-autoloader
```

Create the local configuration file:

```bash
cp config/config.example.php config/config.php
```

Edit `config/config.php` and set the application URL, database credentials, mail settings, and `secret_salt`.

`config/config.php` is ignored by Git so server passwords and local settings are not overwritten by updates.

## Database

For a fresh installation, import the full schema:

```bash
mysql -u DB_USER -p DB_NAME < database/schema.sql
```

Create the first administrator:

```bash
php database/create_admin.php admin strong-password
```

If you are updating an existing database, apply the migration files that match your current state:

```bash
mysql -u DB_USER -p DB_NAME < database/update_i18n.sql
mysql -u DB_USER -p DB_NAME < database/update_app_settings.sql
```

`database/update_admin_locale.sql` is available when only the administrator language column is missing.

## Writable Directories

The web server must be able to write generated QR codes and logs:

```bash
chmod -R 775 storage/qrcodes storage/logs
```

## Web Server

The recommended document root is the `public` directory.

If your hosting points to the project root, the root `.htaccess` forwards requests to `public/index.php` and blocks access to private project directories.

## Configuration

Important configuration values:

```php
'app' => [
    'name' => 'Q to me',
    'base_url' => 'https://q-2.me',
    'timezone' => 'Europe/Berlin',
    'secret_salt' => 'long-random-secret',
    'default_locale' => 'de',
    'locales' => ['de', 'ru', 'en'],
],
'db' => [
    'dsn' => 'mysql:host=localhost;dbname=DB_NAME;charset=utf8mb4',
    'user' => 'DB_USER',
    'password' => 'DB_PASSWORD',
],
```

Most public-facing settings can also be edited in `/admin/settings`, including:

- administrator notification e-mail;
- e-mail sender name;
- contact e-mail for legal pages;
- Impressum address;
- Impressum and privacy text for each language;
- public gallery visibility.

These editable settings are stored in the `app_settings` database table.

## SMTP

Example Gmail configuration:

```php
'mail' => [
    'from' => 'your-account@gmail.com',
    'from_name' => 'Q to me',
    'admin_to' => 'admin@example.com',
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-account@gmail.com',
        'password' => 'gmail-app-password-without-spaces',
        'encryption' => 'tls',
    ],
],
```

For Gmail, enable two-step verification and create an app password. Google displays app passwords in groups such as `dlni ixzt jkrj dowc`; use the password without spaces in the configuration.

If SMTP is empty or delivery fails, the message and error are written to:

```text
storage/logs/mail.log
```

The application does not break when mail delivery fails.

## Moderation

New public submissions receive the `pending` status by default.

Statuses:

- `pending`: waiting for administrator review;
- `approved`: short link works and the QR page is visible;
- `rejected`: link was not approved;
- `blocked`: link was blocked by an administrator.

When a status changes, the user receives an e-mail notification if an author e-mail is available.

## Gallery

Guests see only:

```text
status = approved
is_public = 1
```

Administrators can also view private approved links in the gallery.

Search works by title and short code. The gallery is paginated to keep it fast.

## Languages

Public language routes:

```text
/de
/ru
/en
```

Short links stay short and do not receive a language prefix:

```text
https://q-2.me/abc123
```

The selected language is stored with each submitted QR code and used for future user notifications. The administrator language is stored separately in `/admin/settings`.

## Blacklist

Administrators can manage forbidden short-code words at:

```text
/admin/blacklist
```

Blacklisted words cannot be used as short codes.

## QR Pages

- Creation result: `/result/{short_code}`
- Public QR page: `/qr/{short_code}`
- QR download: `/qr/{short_code}/download`

The QR image can be downloaded immediately after creation, even while the link is still waiting for moderation.

## Legal Pages

Public legal pages:

```text
/impressum
/datenschutz
```

The Impressum address, Impressum text, and privacy text are editable in `/admin/settings` and stored in the database. Texts are maintained separately for German, Russian, and English.

Review the legal text before publishing and adapt it to your hosting provider, retention periods, external services, and local legal requirements.
