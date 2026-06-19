# Hotel Management System — Setup & Deployment Guide

A complete, copy‑paste guide to get this project running on a **fresh PC** — from
`git clone` through installing the stack, creating the database, importing all
tables, and logging in.

Stack: **PHP + MySQL/MariaDB + Apache** (classic LAMP), shipped via **XAMPP** on
Windows. No build step, no Composer/npm dependencies required (vendor libraries
are committed).

---

## 1. Prerequisites — install the stack

### Option A — XAMPP (recommended, Windows/Mac/Linux)

XAMPP bundles Apache + PHP + MariaDB + phpMyAdmin in one installer.

1. Download XAMPP (PHP 8.x) from <https://www.apachefriends.org/download.html>.
2. Run the installer. On Windows the default install path is `C:\xampp`.
   Make sure these components are checked: **Apache**, **MySQL**, **PHP**, **phpMyAdmin**.
3. Launch **XAMPP Control Panel** and click **Start** next to **Apache** and **MySQL**.
   Both rows should turn green.

> The app expects the document root at `C:\xampp\htdocs`. The project must live in a
> folder named `hotel` inside it → `C:\xampp\htdocs\hotel`.

### Option B — Native packages (Linux server / production)

```bash
# Debian/Ubuntu
sudo apt update
sudo apt install apache2 php php-mysqli php-mbstring mariadb-server -y
sudo systemctl enable --now apache2 mariadb
```

Document root is typically `/var/www/html` → clone into `/var/www/html/hotel`.

**Minimum versions:** PHP 7.4+ (8.x recommended), MySQL 5.7+ / MariaDB 10.4+.
The PHP **mysqli** extension is required (bundled with XAMPP, `php-mysqli` on Linux).

---

## 2. Get the code

Clone directly into the web root so the URL becomes `http://localhost/hotel`.

**Windows (XAMPP):**
```powershell
cd C:\xampp\htdocs
git clone https://github.com/Davemasibo/hotelManagementSystem.git hotel
```

**Linux:**
```bash
cd /var/www/html
sudo git clone https://github.com/Davemasibo/hotelManagementSystem.git hotel
sudo chown -R www-data:www-data hotel        # let Apache read/write uploads
```

> Already have the repo? Just `cd` into it and run `git pull` to get the latest changes
> (including `database/demo_seed.sql` used in step 4).

---

## 3. Create the database

The app connects as user **`root`** with **no password** to a database named
**`hotel_db`** (see `admin/db_connect.php`). On a default XAMPP install that
matches out of the box.

### Create the empty database

**Via command line (Windows / XAMPP):**
```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS hotel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

**Via command line (Linux):**
```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS hotel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

**Or via phpMyAdmin (GUI):** open <http://localhost/phpmyadmin> → **New** → name it
`hotel_db` → **Create**.

> **Different MySQL credentials?** If your MySQL `root` has a password, or you use a
> different DB name/user, edit **`admin/db_connect.php`** line 3:
> ```php
> $conn = new mysqli('localhost', 'root', 'YOUR_PASSWORD', 'hotel_db');
> ```

---

## 4. Import the tables (run in this exact order)

The schema is split across a base dump plus incremental migrations. **Order
matters** — migrations `ALTER` tables created by earlier files.

| # | File | What it creates |
|---|------|-----------------|
| 1 | `database/hotel_db.sql`      | Base schema + seed: `users`, `system_settings`, `rooms`, `room_categories`, `checked` + default **admin** account |
| 2 | `database/migration_v2.sql` | 9 modules: `guests`, `guest_preferences`, `guest_requests`, `housekeeping_tasks`, `invoices`, `invoice_items`, `payments`, `notifications`, `audit_logs` + ALTERs |
| 3 | `database/migration_v3.sql` | Auth fields on `users` (email, phone, status, reset tokens) for signup/forgot‑password |
| 4 | `database/migration_v4.sql` | Extra `system_settings` columns (currency, tax, invoice prefix, check‑in/out times) + user columns |
| 5 | `database/demo_seed.sql`    | **Optional** — sample guests, bookings, invoices, payments, housekeeping & notifications so the dashboard/reports look populated for a demo |

### Command line (Windows / XAMPP)

```powershell
$mysql = "C:\xampp\mysql\bin\mysql.exe"
Get-Content database\hotel_db.sql      -Raw | & $mysql -u root hotel_db
Get-Content database\migration_v2.sql  -Raw | & $mysql -u root hotel_db
Get-Content database\migration_v3.sql  -Raw | & $mysql -u root hotel_db
Get-Content database\migration_v4.sql  -Raw | & $mysql -u root hotel_db
Get-Content database\demo_seed.sql     -Raw | & $mysql -u root hotel_db   # optional demo data
```

### Command line (Linux)

```bash
mysql -u root hotel_db < database/hotel_db.sql
mysql -u root hotel_db < database/migration_v2.sql
mysql -u root hotel_db < database/migration_v3.sql
mysql -u root hotel_db < database/migration_v4.sql
mysql -u root hotel_db < database/demo_seed.sql        # optional demo data
```

### Or via phpMyAdmin (GUI)

Select `hotel_db` → **Import** tab → choose the file → **Go**. Repeat for each
file **in the order above (1 → 5)**.

> The migration files are **idempotent** (they use `ADD COLUMN IF NOT EXISTS`), so
> re‑running them is safe. `demo_seed.sql` is also idempotent — it deletes its own
> prior DEMO rows before re‑inserting, so you can refresh demo data anytime.

---

## 5. Run it & log in

1. Ensure **Apache** and **MySQL** are running (XAMPP Control Panel → both green).
2. Open the app in a browser:

   | Surface | URL |
   |---------|-----|
   | Public website | <http://localhost/hotel/> |
   | Admin / staff portal | <http://localhost/hotel/admin/login.php> |

3. **Default login:**

   ```
   Username: admin
   Password: admin123
   ```

   > On first login the plaintext seed password is transparently upgraded to a
   > bcrypt hash — the credentials stay the same.

If the database isn't reachable, the app shows a friendly **"Database Setup
Required"** page with these same steps — that means you missed step 3 or 4.

---

## 6. Verify the install (optional)

```powershell
# Should list 14 tables
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT COUNT(*) AS tables FROM information_schema.tables WHERE table_schema='hotel_db';"

# Should show the admin user, status=active
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT id,username,type,status FROM hotel_db.users;"
```

Then click through: **Dashboard** (KPIs), **Front Desk → Check‑in/out**, **Guests**,
**Housekeeping**, **Billing**, **Reports**. With the demo seed imported these are
all populated.

---

## 7. Production deployment notes

This project is built for a local/LAN environment. Before exposing it publicly,
harden it:

- **Change the admin password** immediately and create real staff accounts under
  **Admin → Users**. New signups land as `pending` until an admin approves them.
- **Set a MySQL root password** (or a dedicated app user with least privilege) and
  update `admin/db_connect.php` accordingly. Never ship `root` + blank password.
- **Serve over HTTPS** — put Apache behind a TLS cert (Let's Encrypt) or a reverse
  proxy. Sessions and the login form are sent in clear text otherwise.
- **Lock down the document root** so `database/*.sql` and `*.md` aren't web‑served,
  e.g. move them outside `htdocs` or add an Apache `<FilesMatch>` deny rule.
- **Disable PHP error display** in `php.ini` (`display_errors = Off`) and enable a
  log file instead.
- **Back up** the `hotel_db` database regularly:
  ```bash
  mysqldump -u root hotel_db > backup_$(date +%F).sql
  ```
- **`.gitignore`** already excludes `node_modules/`, `vendor/`, `.env`, and logs —
  keep secrets out of the repo.

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| "Database Setup Required" page | DB not created or tables not imported — redo steps 3–4 |
| `Access denied for user 'root'@'localhost'` | MySQL root has a password — set it in `admin/db_connect.php` |
| Port 80 already in use (Apache won't start) | Stop IIS / Skype / another web server, or change Apache's port in XAMPP → Config → `httpd.conf` |
| Port 3306 in use (MySQL won't start) | Another MySQL/MariaDB instance is running — stop it or change the port |
| Blank page / 500 error | Check `C:\xampp\apache\logs\error.log`; ensure the **mysqli** PHP extension is enabled |
| Images/uploads not saving | Ensure Apache has write permission to `admin/assets/img/` (Linux: `chown -R www-data`) |

---

**Quick reference**

```
Project root : C:\xampp\htdocs\hotel  (Windows)  /var/www/html/hotel  (Linux)
App URL      : http://localhost/hotel/
Admin URL    : http://localhost/hotel/admin/login.php
Database     : hotel_db   (user root, no password by default)
Login        : admin / admin123
Repo         : https://github.com/Davemasibo/hotelManagementSystem.git
```
