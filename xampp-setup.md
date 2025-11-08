# XAMPP Deployment Guide

Detailed steps for running the MDRRMO Geotagged Incident Reporting System on a local XAMPP stack. These instructions mirror the configuration style used in the ZDSPGC Event & Inventory Management System reference project.[^zdspgc]

[^zdspgc]: https://github.com/fzkn4/ZDSPGC-EVENT-AND-INVENTORY-MANAGEMENT-SYSTEM

---

## 1. Prerequisites

- **XAMPP** (PHP 8.1+ recommended). Download from [apachefriends.org](https://www.apachefriends.org/).
- **Git** (optional) to clone the repository, otherwise download the ZIP.
- Administrative access to edit `hosts` and Apache configuration files.

> The examples below assume a default XAMPP installation at `C:\xampp`. Adjust paths if you chose a different location.

---

## 2. Install XAMPP

1. Run the XAMPP installer and select at minimum **Apache** and **MySQL** components.
2. After installation, launch the *XAMPP Control Panel*. Do **not** start the services yet—we will configure them first.

---

## 3. Place the Project Files

1. Clone or extract the project into the XAMPP document root:
   - Recommended path: `C:\xampp\htdocs\geotagged`.
   - The main entry point should now be `C:\xampp\htdocs\geotagged\index.php`.
2. Ensure writable files keep standard permissions (Windows marks them writable by default).

---

## 4. Configure Environment Variables

The application reads database settings from environment variables or a local `.env` file. Create `C:\xampp\htdocs\geotagged\.env` with:

```
APP_ENV=development
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=geotagged
DB_USERNAME=geotagged_user
DB_PASSWORD=geotagged_password
DB_CONNECTION_RETRIES=5
DB_CONNECTION_SLEEP=1
```

> For production-like testing, remove `APP_ENV` or set it to `production` so connection failures return generic errors.

---

## 5. Configure Apache

### 5.1 Enable Required Modules

1. Open `C:\xampp\apache\conf\httpd.conf` in a text editor running as Administrator.
2. Ensure the following lines are **uncommented**:
   ```
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. Confirm PHP is loaded (XAMPP enables this by default).

### 5.2 Set Document Root (Option A: Replace Default Site)

Replace the `DocumentRoot` block:
```
DocumentRoot "C:/xampp/htdocs/geotagged"
<Directory "C:/xampp/htdocs/geotagged">
    Options Indexes FollowSymLinks Includes ExecCGI
    AllowOverride All
    Require all granted
</Directory>
```

### 5.3 Create a Virtual Host (Option B: Keep Default Site)

1. Open `C:\xampp\apache\conf\extra\httpd-vhosts.conf`.
2. Append:
   ```
   <VirtualHost *:80>
       ServerName geotagged.local
       DocumentRoot "C:/xampp/htdocs/geotagged"
       <Directory "C:/xampp/htdocs/geotagged">
           AllowOverride All
           Require all granted
       </Directory>
       ErrorLog "logs/geotagged-error.log"
       CustomLog "logs/geotagged-access.log" combined
   </VirtualHost>
   ```
3. Map the hostname in `C:\Windows\System32\drivers\etc\hosts`:
   ```
   127.0.0.1 geotagged.local
   ```

Either approach ensures `.htaccess` overrides work, matching the Docker configuration’s mod_rewrite support.

---

## 6. Configure PHP Extensions (Optional Check)

1. Open `C:\xampp\php\php.ini`.
2. Verify that the following extensions are enabled (uncomment if necessary):
   ```
   extension=pdo_mysql
   extension=mysqli
   ```
3. Optional: set production-like logging
   ```
   log_errors = On
   error_log = "C:\xampp\php\logs\php_error_log"
   display_errors = Off
   ```

---

## 7. Create the MySQL Database

1. Start **MySQL** from the XAMPP Control Panel.
2. Launch phpMyAdmin (`http://localhost/phpmyadmin/`).
3. Run the following SQL to create the database and user:
   ```sql
   CREATE DATABASE geotagged CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

   CREATE USER 'geotagged_user'@'localhost' IDENTIFIED BY 'geotagged_password';

   GRANT ALL PRIVILEGES ON geotagged.* TO 'geotagged_user'@'localhost';

   FLUSH PRIVILEGES;
   ```
4. Import the schema/seed data provided in `db\init.sql`:
   - In phpMyAdmin, select the `geotagged` database → **Import** → choose `C:\xampp\htdocs\geotagged\db\init.sql`.
   - Alternatively, use the MySQL CLI:
     ```
     "C:\xampp\mysql\bin\mysql.exe" -u root geotagged < "C:\xampp\htdocs\geotagged\db\init.sql"
     ```

---

## 8. Start Services and Test

1. In the XAMPP Control Panel, start **Apache** and **MySQL**.
2. Visit `http://localhost/` (or `http://geotagged.local/` if you configured the virtual host).
3. Load `test_db.php` to verify connectivity (`http://localhost/test_db.php` if placed at the web root).
4. Sign in with a valid user or create one via the application workflow.

---

## 9. Post-Installation Hardening

- Change default database credentials.
- Delete diagnostic files such as `test_db.php` before production use.
- Restrict phpMyAdmin by setting a password for the MySQL `root` user.
- Enable HTTPS for production environments (self-signed certificates are sufficient for local testing).

---

## 10. Troubleshooting

| Symptom | Fix |
| --- | --- |
| `Database connection failed` | Confirm MySQL is running, credentials match `.env`, and the `users` table exists. |
| `500 Internal Server Error` | Check `C:\xampp\apache\logs\error.log`. Ensure `.env` doesn’t contain BOM/Unicode characters. |
| Static assets not loading | Confirm `AllowOverride All` is set and `.htaccess` is readable. |
| Changes aren’t reflected | Clear browser cache, or disable opcode caching (`opcache.enable=0`) during development. |

---

You now have the MDRRMO Geotagged Incident Reporting System running locally on XAMPP with parity to the Docker reference stack.

