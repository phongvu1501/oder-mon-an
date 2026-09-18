# OderMonAn

OderMonAn is a Laravel 12 restaurant operations and customer ordering system for a small or medium restaurant. It separates table visits, orders, bills, payments, and inventory history so financial records remain stable.

## Requirements

- PHP 8.2+ with MySQL PDO; enable `pdo_sqlite` and `sqlite3` for tests
- MySQL 8.0.16+ (tested on 8.0.30)
- Composer 2, Node.js 20+, npm, and Laragon or an equivalent environment

## Installation with Laragon

1. Place the project in `D:/laragon/www/Oder-Mon-An`.
2. Create an empty MySQL database named `oder_mon_an`.
3. Copy `.env.example` to `.env` and set local database credentials.
4. Run:

```powershell
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
npm run build
```

Do not use `migrate:fresh` or `db:wipe` on an existing installation. Start locally with `php artisan serve --host=127.0.0.1 --port=8000`, then open `http://127.0.0.1:8000`.

## Environment

```dotenv
APP_NAME=OderMonAn
APP_TIMEZONE=Asia/Ho_Chi_Minh
APP_LOCALE=vi
RESTAURANT_NAME=OderMonAn
RESTAURANT_PAGINATION=15
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

Generate an independent JWT secret of at least 32 random bytes. If `JWT_SECRET` is empty, the local app key is used as fallback. Never commit the real `.env`, JWT secret, database credentials, or bank data.

```powershell
php -r "echo bin2hex(random_bytes(32));"
```

## Demo accounts

All local demo accounts use password `Password@123`.

| Role | Email |
|---|---|
| Super Admin | `superadmin@odermonan.test` |
| Admin | `admin@odermonan.test` |
| Manager | `manager@odermonan.test` |
| Staff | `staff@odermonan.test` |
| Kitchen | `kitchen@odermonan.test` |

Change or remove demo credentials before real deployment.

## Architecture

Controllers validate and authorize requests, then delegate transactional rules to focused services:

- `TableSessionService`: opens one active visit per table and closes paid visits.
- `OrderService`: validates products, snapshots price/name, enforces transitions, handles idempotency, and recalculates bills.
- `InventoryService`: locks stock rows and records every movement.
- `PaymentService`: creates attempts, confirms funds, blocks overpayment, and closes sessions.
- `JwtService` and middleware: issue/verify access and rotating refresh tokens.
- `QrCodeService`: generates table and payment instruction QR as local SVG.

Detailed schema, constraints, delete rules, decisions, and ERD are in [docs/architecture.md](docs/architecture.md).

## Business flow

```text
Restaurant Table
  -> Table Session (one active session per table)
      -> Orders (multiple calls in one visit)
          -> Order Items (name and price snapshots)
      -> Bill (one aggregate bill)
          -> Payments (one or more attempts/receipts)
```

A paid bill closes its table session only after unfinished kitchen work is resolved, then releases the table to `available`.

<!-- README_MORE -->
