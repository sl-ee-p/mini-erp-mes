# Mini ERP/MES

ERP/MES for **Oakline Woodworks**, a fictional furniture workshop. It covers the path from a customer order to finished goods on the shelf: sales orders, bills of materials and routings, production orders run from the shop floor, stock, and an audit trail.

Built with Laravel 13, Blade, Tailwind and Alpine.js.

## Quick start

```bash
docker compose up -d
docker compose exec app composer setup
docker compose exec app php artisan db:seed
```

| Service | URL |
|---|---|
| App | http://localhost:8080 |
| Mailpit | http://localhost:8025 |
| MySQL | `localhost:33060` |

Without Docker: `composer setup && php artisan db:seed && composer dev` (SQLite by default).

### Demo data

`db:seed` loads a complete demo workshop: master data plus a few weeks of history booked through the domain
actions (orders in every status, one late, steps running and paused, goods receipts, a stock count and a material
shortage). Dates are relative to the day you seed. The history is only added to a database without sales orders.

The deploy job migrates but never seeds. To load the demo workshop once on a fresh home server:

```bash
docker exec mini-erp-mes-app-1 php artisan db:seed --force
```

The app is then at `http://<server-ip>:8081`.

### Demo accounts

All use the password `password`.

| Role | Email | Lands on |
|---|---|---|
| Administrator | admin@oakline.test | Dashboard |
| Production planner | planner@oakline.test | Dashboard |
| Shop-floor operator | operator@oakline.test | Shop floor |
| Sales | sales@oakline.test | Dashboard |

## Features

- **Dashboard**: open order value, late orders, orders ready to ship, production in progress, material shortages and scrap rate, with the lists behind them and the remaining planned work per work center.
- **Search**: one box (press `/`) across orders, customers, products and materials; an exact document number or SKU, typed or scanned, opens the record directly.
- **Sales**: customers, sales orders (draft → confirmed → shipped / cancelled), gap-free document numbers, order confirmation PDFs generated in a queued job.
- **Catalog**: products with a bill of materials and a production routing across work centers.
- **Production**: confirming a sales order releases one production order per line. Operators start, pause and complete steps; time is logged and material is consumed automatically. A running step can only be paused or completed by the operator working on it, or by a planner.
- **Inventory**: stock ledger, manual adjustments, low-stock alerts, and material allocated to released production with shortages flagged before work stalls. Stock list and ledger export to CSV with the current filters.
- **Administration**: user management with four roles, and an audit log recording model changes with user, IP and before/after values.

## Quality

```bash
composer test          # PHPUnit
vendor/bin/pint --test # code style
vendor/bin/phpstan analyse # Larastan level 6
```

CI runs all of the above plus the Vite build on every push, with tests against MySQL.

## Architecture

Logic lives in `app/Domain/<Context>` as single-purpose Actions; controllers stay thin. Status workflows are enum state machines. The reasoning behind each choice is recorded in [`docs/adr`](docs/adr/README.md).
