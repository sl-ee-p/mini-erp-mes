# TODO

Open items from the October 2026 quality pass, in priority order.

## 1. Tests for form error accessibility

- **Problem:** The form components now set `aria-invalid` and `aria-describedby`, and the textareas show their
  errors (commit "fix(ui): link form errors to their fields and show textarea errors"). No test covers this yet.
- **Where:** `resources/views/components/form/{input,select,checkbox,textarea}.blade.php`,
  `resources/views/{customers,sales-orders,products}/_form.blade.php`.
- **Fix:** Add feature tests. Post invalid data, follow the redirect (or render the form with a
  `ViewErrorBag`), then assert `aria-invalid="true"` and `aria-describedby="<id>-error"` on, for example,
  the customer `code` input, and assert that the message for over-long `notes` / `description` appears
  on the sales-order and product forms.

## 2. `%` and `_` act as LIKE wildcards in search

- **Problem:** User input goes straight into `LIKE "%{$term}%"`, so `%` matches everything and `_` matches any
  single character.
- **Where:** `Customer::scopeSearch` (app/Models/Customer.php:47-50), `Material` (app/Models/Material.php:103-104),
  `Product` (app/Models/Product.php:82-83), `SalesOrder` (app/Models/SalesOrder.php:137-138),
  `ProductionOrderController::index` (lines 22-24), `SearchController` (line 54), `UserController::index` (line 24).
- **Fix:** Add one helper, for example `App\Support\Like::contains(string $term): string`, that escapes `\`, `%`
  and `_` with a backslash. Apply it with an explicit `ESCAPE '\'` clause through a `whereRaw`/macro, such as
  `Builder::macro('whereContains', ...)`. MySQL treats `\` as the default escape, but SQLite has no default,
  so the clause is needed for both. Add tests showing that `%` and `_` match literally and that
  `100%`-style names are still found.

## 3. Step timer wraps after 24 hours

- **Problem:** The server-rendered fallback uses `gmdate('H:i:s', $seconds)`, so 27 h shows as `03:05:10`.
- **Where:** `resources/views/production-orders/show.blade.php:51`, `resources/views/shop-floor/index.blade.php:29,68`.
- **Fix:** Add `Format::duration(int $seconds): string` in `app/Support/Format.php`
  (`sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60)`), use it in the three views,
  and add a unit test in `tests/Unit/Support` covering 0, 59, 3600 and 97510 (`27:05:10`). Check that the
  `stepTimer` Alpine component (resources/js) does not wrap either.

## 4. Smaller edge cases

### 4a. Customer deleted while an order is being created → 500

- **Problem:** `CustomerController::destroy` checks `salesOrders()->exists()` and then deletes. A concurrent
  `CreateSalesOrder` can insert in between, or the order can reference a customer that was just deleted.
  Either way the FK violation surfaces as a 500.
- **Where:** `app/Http/Controllers/Web/CustomerController.php::destroy`, `SalesOrderController::store`
  / `App\Domain\Sales\Actions\CreateSalesOrder`.
- **Fix:** Catch `Illuminate\Database\QueryException` for integrity-constraint violations (SQLSTATE `23000`)
  around the delete and the order insert. Redirect back with the same kind of flash message the app uses
  for `SalesOrderException`, e.g. "Customers with sales orders cannot be deleted." / "The selected customer
  no longer exists."

### 4b. Goods receipt can overflow the stock column → 500

- **Problem:** `materials.stock_qty`, `stock_movements.quantity` and `balance_after` are `decimal(12, 3)`
  (max 999 999 999.999). A receipt that pushes the balance past that fails in the DB.
- **Where:** `app/Http/Requests/Materials/StoreStockMovementRequest.php`; columns in
  `database/migrations/2026_10_17_145414_create_materials_table.php` and `..._create_stock_movements_table.php`.
- **Fix:** Add a `max:999999999.999` rule (ideally a constant shared with a comment that points at the
  migration). For `in` movements, also refuse a quantity where `stock_qty + quantity` exceeds the limit,
  and show a field error. Add a feature test.

### 4c. Fully scrapped order keeps its material allocated

- **Problem:** `MaterialRequirements` allocates every line of an active production order with
  `consumed_qty = 0`. If every unit is scrapped before the material-consuming step, nothing is ever
  backflushed, so the requirement stays allocated until the order completes.
- **Where:** `app/Domain/Inventory/Queries/MaterialRequirements.php` (around line 80),
  `app/Domain/Production/Actions/CompleteStep.php`, `Listeners/ConsumeStepMaterials.php`.
- **Fix:** Read how scrap reduces `input_qty` for the following steps first. Then exclude orders whose
  consuming step can no longer receive any good units (e.g. the consuming step's `input_qty = 0`, or the
  previous step's good output is 0), or scale the requirement by the units still flowing. Update ADR 0008
  and add a test where all units are scrapped at step 1 and step 2 consumes the materials.

## 5. Display timezone (not started)

- **Problem:** Timestamps are stored and shown in UTC; the workshop is in Europe/Rome.
- **Fix (proposal):** Keep storage in UTC. Add `config('app.display_timezone')` from
  `APP_DISPLAY_TIMEZONE` (default `UTC`, so nothing changes unless it is set) and route timestamp rendering
  through one helper (e.g. `Format::dateTime()`) that calls `->setTimezone(...)`. Leave document-number years
  and "late" logic alone. If the views format dates in too many places to change safely in one go, record
  this as an ADR proposal instead.
