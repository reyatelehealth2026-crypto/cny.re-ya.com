# AGENTS.md

## Cursor Cloud specific instructions

### Overview
This is a PHP 8.0+ LINE Telepharmacy CRM platform. The core stack is PHP + MySQL + optional Node.js WebSocket server. Tests use PHPUnit with in-memory SQLite.

### Services

| Service | How to start | Port |
|---------|-------------|------|
| PHP dev server | `php -S 0.0.0.0:8080 -t .` (from repo root) | 8080 |
| MySQL 8 | `sudo mysqld --user=mysql &` (check if already running first) | 3306 |
| WebSocket server (optional) | `node websocket-server.js` | 3000 |

### Database setup
- MySQL must be running before the PHP app can serve pages.
- Start MySQL: `sudo mysqld --user=mysql --datadir=/var/lib/mysql --socket=/var/run/mysqld/mysqld.sock --pid-file=/var/run/mysqld/mysqld.pid &`
- The database schema is at `database/install_complete_latest.sql`. Import with relaxed SQL mode because it uses MariaDB syntax (TEXT columns with defaults):
  ```
  mysql -u root -e "SET GLOBAL sql_mode = '';"
  mysql -u root -e "CREATE DATABASE IF NOT EXISTS telepharmacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  sed "s/\`offline_message\` text DEFAULT '.*'/\`offline_message\` text/" database/install_complete_latest.sql | mysql -u root telepharmacy
  ```

### Local config.php
The committed `config/config.php` contains production credentials. For local dev, patch DB credentials:
```
DB_HOST = 127.0.0.1
DB_NAME = telepharmacy
DB_USER = root
DB_PASS = (empty)
```
Also update `APP_URL` and `BASE_URL` to `http://localhost:8080`.

### Admin login
Create a local admin user after importing the schema:
```bash
ADMIN_PASS=$(php -r "echo password_hash('admin123', PASSWORD_BCRYPT);")
mysql -u root telepharmacy -e "INSERT INTO admin_users (username, email, password, role, is_active, created_at) VALUES ('admin', 'admin@example.com', '$ADMIN_PASS', 'super_admin', 1, NOW()) ON DUPLICATE KEY UPDATE password = '$ADMIN_PASS', is_active = 1;"
```
Login at `http://localhost:8080/auth/login.php` with `admin` / `admin123`.

### Commands reference
See `composer.json` scripts and `.github/copilot-instructions.md` for the canonical commands:
- **Tests:** `composer test` (PHPUnit, 8600+ tests; requires php8.2-sqlite3 extension)
- **Lint:** `composer lint` (php-cs-fixer dry-run)
- **Fix lint:** `composer lint:fix`
- **Static analysis:** `composer analyse` (PHPStan level 0)

### Gotchas
- PHPUnit tests use in-memory SQLite (`pdo_sqlite`), not MySQL. The `php8.2-sqlite3` package must be installed.
- Some test suites have pre-existing failures (AIChat SessionContinuity, VibeSelling, InboxChat TemplateRoundTrip, AdminMenu). These are NOT caused by the environment.
- The SQL schema file uses `text DEFAULT '...'` which is valid MariaDB but fails on MySQL 8 strict mode. Must set `sql_mode = ''` before importing.
- `config/config.php` is tracked in git with production values. Local dev requires overwriting DB credentials. Do not commit local config changes.
- The PHP built-in server does not support `.htaccess` rewrites. Use explicit `.php` URLs (e.g., `/auth/login.php`, `/dashboard.php`) or access files directly.
