# Al-Jabali — Next Stage Plan & Improvements

**Date:** 2026-04-26  
**Current Status:** V1 Feature-Complete · 413 Tests Passing · Ready for Production Deployment

---

## Phase Overview

| Phase | Title | Priority | Effort |
|---|---|---|---|
| **0** | Production Go-Live Checklist | Critical | 1–2 days |
| **1** | Operational Hardening | High | 1–2 weeks |
| **2** | UX & Workflow Improvements | High | 2–3 weeks |
| **3** | Analytics & Reporting Expansion | Medium | 2–3 weeks |
| **4** | Mobile & Notifications Expansion | Medium | 2–3 weeks |
| **5** | Advanced Features | Low | 4–6 weeks |

---

## Phase 0 — Production Go-Live Checklist

> Must be done before first real user logs in.

- [ ] Set `APP_ENV=production`, `APP_DEBUG=false` in production `.env`
- [ ] Configure real SMTP credentials (`MAIL_MAILER=smtp`)
- [ ] Change all default/dev passwords (`DB_PASSWORD`, user seeds)
- [ ] Run `php artisan optimize` (config + route + view caching)
- [ ] Enable HTTPS and configure SSL certificate
- [ ] Set `SESSION_SECURE_COOKIE=true` and `SESSION_SAME_SITE=strict`
- [ ] Configure a queue worker process (Supervisor or Laravel Horizon)
- [ ] Set up automated database backups (daily minimum)
- [ ] Run `php artisan filament:shield:generate --all` on production
- [ ] Remove `FakeDataSeeder` and `TestDataSeeder` from production seed chain
- [ ] Verify email notifications reach real inboxes end-to-end
- [ ] Smoke-test all 6 roles on the production server

---

## Phase 1 — Operational Hardening

*Goal: make the system reliable and maintainable in production.*

### 1.1 Queue & Background Jobs

- [ ] Move email notifications to queued jobs (currently synchronous — blocks request)
- [ ] Add a `FailedJobsMonitor` — alert admin by email when a job fails
- [ ] Consider upgrading `QUEUE_CONNECTION` to `redis` for reliability

### 1.2 Error Monitoring

- [ ] Integrate **Sentry** (or similar) for exception tracking in production
- [ ] Set `LOG_LEVEL=error` in production, `LOG_LEVEL=debug` in staging
- [ ] Add a health-check endpoint (`/health`) that verifies DB + queue connectivity

### 1.3 Performance

- [ ] Add database indexes on high-query columns:
  - `sales_approval_requests.status`
  - `sales_approval_requests.current_stage`
  - `daily_visits.user_id + visit_date`
  - `hr_requests.status + approver_id`
- [ ] Enable MySQL query cache for read-heavy report pages
- [ ] Profile the Approval Dashboard and GM Dashboard with Laravel Debugbar in staging

### 1.4 Security Hardening

- [ ] Implement rate limiting on login (`throttle:6,1`)
- [ ] Add `Content-Security-Policy` headers
- [ ] Enable `FORCE_HTTPS=true` redirect in `AppServiceProvider`
- [ ] Audit file upload paths — ensure uploaded attachments cannot be executed
- [ ] Add two-factor authentication (Filament v5 has a built-in 2FA plugin)

### 1.5 Data Integrity

- [ ] Add `SoftDeletes` to `users`, `products`, `product_categories` — prevent accidental hard deletes
- [ ] Add a `deleted_by` column pattern for audit on soft deletes
- [ ] Guard against deleting a product that is referenced by existing sales request items

---

## Phase 2 — UX & Workflow Improvements

*Goal: reduce friction for daily users — especially field engineers and approvers.*

### 2.1 Approval Workflow Enhancements

- [ ] **Bulk Approve** — allow warehouse keeper and sales manager to approve multiple pending requests at once
- [ ] **Stage Return from Any Level** — currently only Stage 1 (warehouse keeper) can return; consider allowing any approver to return with a note
- [ ] **Approval Notes** — require approvers to enter a reason when rejecting or returning (stored in `approval_stages`)
- [ ] **Deadline / SLA Tracking** — flag requests that have been pending at a stage for more than N days
- [ ] **Request Cloning** — engineers can duplicate a previous request as a starting point

### 2.2 Sales Request Form

- [ ] **Inline item calculation** — show running subtotal and grand total as engineer adds items, before submission
- [ ] **Product search** — replace select dropdown with a searchable autocomplete for large catalogs
- [ ] **Minimum order quantity** — enforce `ProductUnit.min_qty` at form validation level
- [ ] **Attach supporting documents** — allow engineers to attach images or PDFs to a sales request

### 2.3 Daily Visit Improvements

- [ ] **Visit templates** — allow engineers to save a visit skeleton (client name, location) and reuse it
- [ ] **Consecutive visit detection** — warn if engineer submits two visits at the same GPS location on the same day
- [ ] **Manager comment on visit** — sales manager can leave a note on a visit record

### 2.4 HR Request Improvements

- [ ] **Leave conflict detection** — warn manager if the requested dates overlap with another employee's approved leave
- [ ] **Team leave calendar** — visual calendar showing all approved leaves by team (currently a list view)
- [ ] **Balance enforcement** — hard-block submission if remaining balance is zero (currently only warns)
- [ ] **Carry-forward annual leave** — end-of-year cron that carries forward unused annual leave balance (up to cap)
- [ ] **Bulk balance reset** — admin tool to reset/set all leave balances at start of fiscal year

### 2.5 General UX

- [ ] **Global search** — enable Filament global search across sales requests, visits, HR requests
- [ ] **Keyboard shortcuts** — Approve/Reject actions accessible via keyboard in detail views
- [ ] **Sticky table filters** — remember last-used filter state per role per resource
- [ ] **Better empty states** — custom empty-state messages per resource (e.g., "No pending requests for your stage")
- [ ] **Confirmation modals** — add a confirmation dialog for all destructive or irreversible actions (reject, cancel)

---

## Phase 3 — Analytics & Reporting Expansion

*Goal: give management real insight into sales pipeline, team productivity, and HR trends.*

### 3.1 Sales Reports

- [ ] **Monthly comparison chart** — approved vs. rejected requests per month, current year
- [ ] **Top products by volume** — most requested products across all approved requests
- [ ] **Engineer performance** — requests submitted / approved / rejected per engineer, time period filterable
- [ ] **Stage bottleneck report** — average time a request spends at each stage
- [ ] **Pending aging report** — list of all requests pending > 3 days with responsible approver

### 3.2 HR Reports

- [ ] **Leave utilization report** — per employee: balance used vs. remaining, by type
- [ ] **Department absence heatmap** — show which days/weeks had most absences
- [ ] **Leave request trend** — monthly HR request volume over the year

### 3.3 Daily Visit Reports

- [ ] **Visit frequency map** — clients visited most often (by client name / region)
- [ ] **Inactive client alert** — clients that have not been visited in > 30 days
- [ ] **Engineer visit count by week** — quick view of field activity

### 3.4 Export Enhancements

- [ ] PDF export for HR requests (currently only sales requests have PDF)
- [ ] Scheduled report emails — GM receives a weekly summary every Sunday at 8AM
- [ ] Filterable Excel exports (currently exports all rows; add date range + status filter)

---

## Phase 4 — Mobile & Notifications Expansion

*Goal: reach engineers in the field and keep approvers informed in real time.*

### 4.1 WhatsApp Notifications

- [ ] Integrate **WhatsApp Business API** (via Twilio or a local provider)
- [ ] Notify engineer when their request is approved, rejected, or returned
- [ ] Notify approver when a new request reaches their stage
- [ ] Notify employee when HR request is approved or rejected
- [ ] Keep email as fallback; WhatsApp as primary channel

### 4.2 Push Notifications (Web)

- [ ] Integrate **Laravel Reverb** (WebSocket) for real-time in-panel notifications
- [ ] Show live notification badge on the Filament topbar when a new approval is pending
- [ ] Desktop push notification (browser API) for approvers with active tab

### 4.3 Mobile Responsiveness

- [ ] Audit all Filament views on a 390px-wide screen (iPhone 14 viewport)
- [ ] Fix table column overflow on small screens (hide non-critical columns, use responsive stack layout)
- [ ] Simplify daily visit form for mobile submission in the field
- [ ] Test RTL layout on iOS Safari and Android Chrome

---

## Phase 5 — Advanced Features

*Goal: long-term value additions based on business growth.*

### 5.1 API Layer

- [ ] Build a **Laravel Sanctum** REST API for core resources
- [ ] Expose endpoints: sales requests (CRUD), visits (create/list), HR requests (submit/status)
- [ ] Generate OpenAPI spec with `dedoc/scramble`
- [ ] This unblocks a future mobile app or external system integration

### 5.2 Supplier & Customer Management

- [ ] Add a `clients` table to replace free-text client fields on sales requests
- [ ] Add a `suppliers` table linked to products
- [ ] Allow general manager to view client purchase history across all requests

### 5.3 Inventory Integration

- [ ] Track stock levels per product unit
- [ ] Warehouse keeper can deduct stock when approving a sales request
- [ ] Low-stock alert when a product unit quantity falls below threshold

### 5.4 Contract & Document Management

- [ ] Attach contracts and signed documents to approved sales requests
- [ ] Version tracking for uploaded documents
- [ ] Expiry alerts for time-limited contracts

### 5.5 Multi-Branch Support

- [ ] Add `branches` table and associate users and requests to a branch
- [ ] Branch-level filtering across all reports
- [ ] Branch manager role scoped to a single branch

---

## Technical Debt & Code Quality

These should be addressed incrementally alongside feature work, not in a big-bang refactor.

| Item | Priority |
|---|---|
| Add Dusk browser tests for the HR approval flow (Stage 1 → 2) | High |
| Add Dusk tests for product catalog CRUD | Medium |
| Extract dashboard stat queries into dedicated `QueryService` classes | Medium |
| Add `FormRequest` classes for API endpoints when Phase 5 starts | Medium |
| Replace `dd()` / `dump()` debug calls if any remain in codebase | High |
| Add `phpstan` level 5 static analysis to CI | Low |
| Set up GitHub Actions CI: run `php artisan test` on every push | High |

---

## Suggested Execution Order

```
Phase 0 (Go-Live)          ← Do this week
     │
     ▼
Phase 1 (Hardening)        ← Weeks 1–2 post-launch
     │
     ▼
Phase 2 (UX)               ← Weeks 3–5
  + Phase 3 (Analytics)    ← Run in parallel with Phase 2
     │
     ▼
Phase 4 (Mobile/Notif)     ← Weeks 6–8
     │
     ▼
Phase 5 (Advanced)         ← Quarter 2 onwards, based on business need
```

---

## Definition of "Done" for Each Phase

A phase is complete when:
1. All checklist items are marked done
2. New feature tests are written and passing
3. A Dusk smoke test covers the critical path of any new user-facing flow
4. Arabic translations are in `lang/ar/` for all new strings
5. The production deploy runs cleanly with `php artisan migrate` and `php artisan optimize`
