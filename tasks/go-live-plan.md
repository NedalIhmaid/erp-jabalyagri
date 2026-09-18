# Al-Jabali — Go-Live Launch Checklist

**Updated:** 2026-04-18
**Status:** Code-level blockers closed. Remaining work is operator-run environment hardening — see [production-checklist.md](./production-checklist.md).
**Related:** Detailed catalog-import work lives in [Product Catalog Import Plan](./product-catalog-import-plan.md).

## Launch Blockers

### 1. Fix duplicate notifications — ✅ DONE
- Notifications are sent only from the service layer (`SalesApprovalService`, `HrRequestService`). Filament resources only emit UI flash toasts. No observers exist.
- Regression coverage: `tests/Feature/NotificationDeliveryTest.php` — asserts `Notification::assertSentToTimes(..., 1)` and total `assertCount()` across all sales + HR events.

### 2. Make sales request numbering race-safe — ✅ DONE
- `SalesApprovalService::submit()` uses a 5-attempt retry loop that catches unique-constraint violations on `sales_approval_requests.request_number`. The format stays `SAR-YYYYMMDD-XXXX`.
- Regression coverage: `tests/Feature/SalesRequestNumberingTest.php` — burst test (10 sequential submits at the same frozen second) + explicit collision-recovery test.

### 3. Close sales-form parity gaps — ✅ DONE
- `region`, `project_type`, `project_size` live on `sales_approval_requests`; form, infolist, export, PDF, and global search all reference them.
- Payment-method labels migrated to the paper terms (`on_account`, `cash_on_delivery`, etc.).
- Sales PDF reorders fields to match the paper form and adds a 6-cell signature block (Engineer + stages 1–5).

### 4. Close daily-visit mobile gaps — ✅ DONE
- Form label reads `farmer_name` (column stays `client_name`).
- `resources/views/filament/forms/geolocation-button.blade.php` — Alpine component wired to `navigator.geolocation` fills `latitude` / `longitude` via `$wire.set`.
- `visit_photo` is required for engineers, capped at 8 MB, and re-encoded through `App\Services\Images\ImageSanitizer` to strip EXIF before storage.

### 5. Align role scope with the real workflow — ✅ DONE
- Sales-manager `getEloquentQuery` on `DailyVisitResource` and `HrRequestResource` scopes to direct reports via `manager_id`; stage-2 sales approval still works.
- `LeaveCalendar::canAccess()` now restricted to `general_manager` + `sales_manager` only.
- `AuditLogPage` gated on `View:AuditLog` permission, which is now granted to `general_manager` only. Tests updated in `AuditLogAdminAccessTest`.

### 6. Production hardening — 🔧 OPERATOR WORK
- All env-level, infra, and deploy steps documented in [production-checklist.md](./production-checklist.md).
- Login rate limiting is provided out of the box by Filament's login page (5 attempts per IP+email).
- Queue worker is optional at launch; notifications and the sales PDF route run synchronously.

### 7. Launch validation — ✅ DONE (automated coverage)
- `tests/Feature/RoleAccessMatrixTest.php` — 43 data-driven cases across the 6 core roles.
- `tests/Feature/SalesRequestNumberingTest.php` — burst + collision-recovery tests.
- `tests/Feature/ConcurrentApprovalRaceTest.php` — two-keeper stage-1 race + state guards.
- `tests/Feature/SalesRequestPdfTest.php` — PDF smoke test (content-type + `%PDF-` marker).
- `tests/Feature/NotificationDeliveryTest.php` — per-event delivery count assertions.
- Arabic RTL QA on sales PDF and day-one screens must still be done manually on production staging before cut-over (see production-checklist §10).

## Needs Decisions

- **Payment-method mapping:** how should legacy `cash` and `credit` values map to the paper terms?
- **Project type input:** should `project_type` be a controlled enum or free text?
- **File storage target:** S3, MinIO, or local private disk for v1?
- **Audit-log access policy:** GM-only, or permission-based for selected admin roles?
- **Sales-manager visibility:** direct reports only, or broader reporting access?
- **2FA:** required for finance and GM at launch, or deferred?
- **Mail provider:** which SMTP service will production use?

## Already Done Or Remove From The Old Draft

- Brand color `#133316` is already configured in the Filament panel.
- `phpoffice/phpspreadsheet` is already installed.
- Sick leave already defaults to `14`; this is not an open item.
- The generic Filament `Dashboard` is already hidden; dashboard work should focus on role-specific pages and leave visibility instead.

## Defer Unless Explicitly Promoted To Launch

- PWA, service worker, and offline shell work.
- Configurable approval-stage ordering or a stage-disable admin UI.
- Logo upload and settings management.
- A generic settings package rollout.
- Filament-based catalog import UI.
- Broader UX polish that does not block submission, approval, or reporting.

## Exit Criteria

- Engineers can submit sales requests and daily visits from mobile without missing fields or duplicate alerts.
- Approvers see only the records they should act on.
- Production uses real mail, secure transport, and a tested backup-and-restore path.
- The core launch path is covered by automated tests.
