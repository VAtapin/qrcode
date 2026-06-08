# QR Moderation

PHP 8.2+ service for moderated short links and QR codes. New links are saved as `pending`; redirects work only after an administrator approves the record.

## Installation

1. Run dependencies:

   ```bash
   composer install
   ```

2. Edit `config/config.php`:

   - set `app.base_url` to the public site URL
   - set a long random `app.secret_salt`
   - set the MySQL/MariaDB PDO DSN, user, and password

3. Import the schema:

   ```bash
   mysql -u root -p qrcode < database/schema.sql
   ```

4. Create the first administrator:

   ```bash
   php database/create_admin.php admin password
   ```

5. Set the web server document root to `/public`.

6. Make these directories writable by PHP:

   - `storage/qrcodes`
   - `storage/logs`

## Apache Example

If the project root is the virtual host directory, keep the root `.htaccess`; it forwards requests to `public/index.php` and blocks direct access to `app`, `config`, `database`, `vendor`, and `storage/logs`.

Recommended virtual host:

```apache
<VirtualHost *:80>
    ServerName qrcode.local
    DocumentRoot /path/to/qrcode/public

    <Directory /path/to/qrcode/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Checks

- All database work uses PDO prepared statements.
- All forms include CSRF tokens.
- User output is escaped with `htmlspecialchars`.
- URL validation allows only `http` and `https`, blocks localhost and private/internal IP ranges.
- QR PNG files are generated into `storage/qrcodes` and point to the short URL, not the original target URL.
