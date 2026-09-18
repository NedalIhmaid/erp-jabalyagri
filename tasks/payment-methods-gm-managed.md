# Task: GM-Managed Payment Methods + 2 New Options

## Goal
1. Add payment options **نقدي وشيكات** (cash & checks) and **شيكات وذمم** (checks & on-account).
2. Let the General Manager add/edit/remove the list of payment methods at runtime.

## Approach
Migrated payment methods from a hardcoded PHP enum (`app/Enums/PaymentMethod.php`, now deleted) to a
DB table (`payment_methods`) with a GM-only Filament resource. The `sales_approval_requests.payment_method`
column stays a **string key** (widened 20→50), so all existing rows keep working untouched — the new
table is keyed by that same `key`.

## Changes
- [x] `2026_06_01_000001_create_payment_methods_table` + `..._000002_widen_payment_method_on_sales_requests`
- [x] `app/Models/PaymentMethod.php` — `getLabelAttribute()` (locale-aware), request-memoized `lookup()`,
      `options()` / `labelFor()` / `colorFor()` helpers; cache cleared on save/delete.
- [x] `PaymentMethodSeeder` (6 idempotent rows) registered in `DatabaseSeeder` before `FakeDataSeeder`.
- [x] `PaymentMethodResource` + 4 pages, GM-only (mirrors GM-only `ProductCategoryResource`),
      navigation group `settings`. Lang: `lang/{ar,en}/payment_methods.php`.
- [x] Swapped all enum consumers to the model: `SalesApprovalRequest` (cast removed, `paymentMethod()`
      relation added), sales create Select + view badge, `SalesReports` column, exporter, export
      controller, PDF blade, factory, `TestDataSeeder`. Removed unused import in `FakeDataSeeder`.
- [x] Deleted `app/Enums/PaymentMethod.php`; replaced its unit test with a `PaymentMethod` model test;
      updated feature/model tests that referenced the enum.

## Verification (done)
- `php artisan migrate` + `db:seed --class=PaymentMethodSeeder` → 6 rows present.
- Full suite: **447 tests, 1149 assertions, OK** (`php -d memory_limit=1G vendor/bin/phpunit --exclude-group browser`).
  Note: default 128M PHP memory limit OOMs mpdf when running the whole suite in one process — bump memory.

## Notes / future
- `sales.payment_methods.*` arrays in `lang/{ar,en}/sales.php` are now unused (names live in DB);
  left in place to avoid risk — safe to prune later.
- Browser (Dusk) tests not run here.
