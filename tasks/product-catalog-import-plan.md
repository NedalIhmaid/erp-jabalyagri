# Al-Jabali — Product Catalog Import Plan

**Updated:** 2026-04-18  
**Purpose:** Import `مواد الشركة.xlsx` into the product catalog without mixing that work into the launch checklist.

## Current Repo Facts

- The source workbook currently exists at the repo root as `مواد الشركة.xlsx`.
- `phpoffice/phpspreadsheet` is already installed, so the importer can read XLSX directly.
- `ProductUnitType` currently supports `ml`, `L`, `g`, `kg`, `ton`, `sack`, `packet`, and `unit`.
- Sales requests depend on priced `ProductUnit` records, so import and pricing must both be addressed before engineers use the real catalog.

## Implementation Plan

### 1. Source handling
- Move the workbook to `database/seeds/data/products-seed.xlsx`.
- Read XLSX directly; do not convert to CSV.
- Make the import idempotent and support a dry-run mode.

### 2. Category classification
- Add a simple rule-based classifier with six launch categories: `بذور`, `أسمدة`, `مبيدات`, `شباك وشاش`, `عدد وأدوات`, and `أخرى`.
- Keep the rules in code or config so they are reviewable and easy to adjust.
- Dry-run output should group rows by inferred category and highlight anything that falls back to `أخرى`.

### 3. Unit normalization
- Add `config/product_units.php` with the known raw Arabic unit strings from the workbook.
- Preserve the original Arabic label verbatim in `product_units.label`.
- Parse and store `unit_type` and `unit_value`.
- For `متر مربع` and `متر طولي`, keep `unit_type = unit` for v1 and rely on the raw label for display; only expand the enum later if analytics or conversions require it.
- Fail fast if a workbook row contains a unit string that is not in the mapping.

### 4. Pricing workflow
- Import units with `price = 0`.
- Add a `needs_price_review` boolean to `product_units` and default imported rows to `true`.
- Prevent engineers from selecting units with `price = 0` or `needs_price_review = true`.
- Provide a finance or sales-manager pricing-review workflow before go-live.
- Log price changes through the existing audit logging.

### 5. Import command
- Add `php artisan products:import {file?} {--dry-run}`.
- Read the workbook, trim values, classify rows, normalize units, and upsert categories, products, and units.
- Upsert by exact match only; do not fuzzy-merge similar names.
- Wrap writes in a transaction.
- Write an import log under `storage/app/imports/`.

### 6. Validation
- Add a unit-normalizer test with one case per known raw unit string.
- Add a dry-run smoke test against the workbook.
- Verify post-import counts are in the expected range and that there are no unknown unit strings.
- Verify engineers cannot select zero-price or price-review-pending units.
- Create one end-to-end sales request using imported products after pricing is complete.

## Assumptions Used In This Plan

- The workbook is the source of truth, not CSV exports.
- Exact-match upserts are safer than fuzzy deduplication for v1.
- Raw Arabic unit labels must remain unchanged for user-facing display.
- Meter-based rows can stay on the generic `unit` type for v1.
- Pricing happens after import and before launch.

## Out Of Scope

- Filament drag-and-drop import UI.
- Fuzzy duplicate detection.
- Inventory or stock tracking.
