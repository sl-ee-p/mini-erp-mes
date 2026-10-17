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

### Demo accounts

All use the password `password`.

| Role | Email | Lands on |
|---|---|---|
| Administrator | admin@oakline.test | Dashboard |
| Production planner | planner@oakline.test | Dashboard |
| Shop-floor operator | operator@oakline.test | Shop floor |
| Sales | sales@oakline.test | Sales orders |

## Features

- **Sales**: customers, sales orders (draft → confirmed → shipped / cancelled), gap-free document numbers, order confirmation PDFs generated in a queued job.
- **Catalog**: products with a bill of materials and a production routing across work centers.
- **Production**: confirming a sales order releases one production order per line. Operators start, pause and complete steps; time is logged and material is consumed automatically.
- **Inventory**: stock ledger, manual adjustments, low-stock alerts.
- **Administration**: user management with four roles, and an audit log recording model changes with user, IP and before/after values.

## Quality

```bash
composer test          # PHPUnit (191 tests)
vendor/bin/pint --test # code style
vendor/bin/phpstan analyse # Larastan level 6
```

CI runs all of the above plus the Vite build on every push, with tests against MySQL.

## Architecture

Logic lives in `app/Domain/<Context>` as single-purpose Actions; controllers stay thin. Status workflows are enum state machines. The reasoning behind each choice is recorded in [`docs/adr`](docs/adr/README.md).
