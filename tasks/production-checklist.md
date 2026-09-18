# Al-Jabali — Production Hardening Runbook

**Updated:** 2026-04-18
**Owner:** DevOps / release engineer
**Purpose:** Steps the operator runs on the production host before opening the system to real users. Code-level launch blockers (see [go-live-plan.md](./go-live-plan.md)) are complete; what remains is environment, secrets, infra, and ops.

## 1. Environment variables

Copy `.env.example` to `.env` on the production host and set:

| Key | Value | Notes |
| --- | --- | --- |
| `APP_ENV` | `production` | Enables production caches; disables debug pages |
| `APP_DEBUG` | `false` | No stack traces leak to end users |
| `APP_URL` | `https://jabali.example.com` | Must match the public hostname; used in mail + PDF links |
| `APP_KEY` | rotated | Run `php artisan key:generate` once on the prod host; never reuse dev keys |
| `APP_TIMEZONE` | `Asia/Amman` | Aligns dates, `visit_date`, and PDF output to local time |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` | production values | |
| `DB_USERNAME` / `DB_PASSWORD` | non-dev, strong password | Prefer a role with least privileges |
| `SESSION_DRIVER` | `database` (current default) | Persists sessions across deploys |
| `SESSION_SECURE_COOKIE` | `true` | Only send cookies over HTTPS |
| `SESSION_SAME_SITE` | `lax` | Filament is same-origin; `lax` is safe |
| `QUEUE_CONNECTION` | `database` (current default) | Notifications and PDF are synchronous today — a worker is optional |
| `FILESYSTEM_DISK` | `local` (private) | Visit photos must never be publicly served |
| `MAIL_MAILER` / `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` | real SMTP | Replace `log` with a real provider (SES, SendGrid, Mailgun, or Zoho) |
| `MAIL_FROM_ADDRESS` | `no-reply@aljabali.example` | Must match SPF/DKIM for the `aljabali.example` domain |
| `MAIL_FROM_NAME` | `Al-Jabali` | Display name |
| `LOG_CHANNEL` | `daily` or `stack` | Rotating file log + Sentry recommended |

## 2. Build, cache, and migrate

Run on the host, in order:

```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

php artisan migrate --force

php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=ShieldSeeder --force
php artisan filament:shield:generate --all --panel=admin

# Seed real production users through the admin UI (not UserSeeder, which is for tests).
```

Deploy pipeline should run `php artisan optimize:clear` before re-caching to avoid stale config.

## 3. Transport and reverse proxy

- Terminate TLS at the reverse proxy (Caddy, Nginx, or the managed load balancer). HTTP → HTTPS redirect must be permanent.
- Set `TrustProxies` allowlist if the app sits behind a load balancer, so `secure` cookies and `APP_URL` evaluate correctly.
- Enable HSTS with `max-age >= 31536000; includeSubDomains; preload`.
- Disable directory listing; serve `public/` as the docroot.
- Block direct requests to `.env`, `storage/`, and `composer.json` at the webserver level.

## 4. Mail deliverability

1. Point the production sending domain's SPF record at the real SMTP provider.
2. Publish DKIM keys provided by the SMTP vendor.
3. Enforce DMARC at `p=quarantine` (upgrade to `reject` after the first week of clean reports).
4. Send a smoke email to `gm@aljabali.example` and verify inbox delivery (not spam).
5. Verify the rendered Arabic body in Outlook, Gmail, and Apple Mail — RTL and font must both render.

## 5. File storage

- Visit photos upload to `storage/app/visit-photos/` under the `local` (private) disk. Do not move to `public/` — photos can contain sensitive location metadata even though EXIF is stripped.
- Configure a backup of `storage/app/` (see §8).
- If switching to S3/MinIO later, keep the `visit-photos` directory private (signed URLs only).

## 6. Security

- Laravel Filament's login page enforces a 5-attempt rate limit per IP+email out of the box. No additional middleware required.
- `session.cookie` must be HTTPS-only and `HttpOnly` (both defaults in Laravel; confirm after deploy).
- 2FA for finance and GM roles is **deferred** — revisit post-launch with the `filament/filament-two-factor-authentication` plugin.
- Rotate `APP_KEY` **only** on first deploy. Rotating after launch invalidates sessions and encrypted DB fields; plan a maintenance window if it ever needs to happen.
- Enable HTTP security headers at the proxy: `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` minimal.

## 7. Queue worker (optional at launch)

Notifications and the sales PDF run synchronously today. A queue worker is NOT required for launch. If throughput becomes an issue post-launch, add:

```
# /etc/supervisor/conf.d/aljabali-worker.conf
[program:aljabali-worker]
command=php /var/www/aljabali/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
stdout_logfile=/var/log/aljabali-worker.log
```

Then set `QUEUE_CONNECTION=database` and tag the heaviest notifications `implements ShouldQueue`.

## 8. Backups

- Nightly logical dump: `mysqldump --single-transaction --quick --routines aljabali > backup-$(date +%F).sql.gz`.
- Retention: 7 daily + 4 weekly + 6 monthly.
- Store off-host (S3 with lifecycle + object-lock, or a separate backup server).
- **Test restore quarterly.** An untested backup is not a backup.
- Include `storage/app/visit-photos/` in the backup set (either via rsync or the same S3 sync cycle).

## 9. Monitoring and logs

- Ship `storage/logs/laravel.log` to a log aggregator (Papertrail, Loki, Datadog). Set alerts on `ERROR`/`critical` levels.
- Install Sentry (recommended) with `SENTRY_LARAVEL_DSN=...`; capture only environment=production.
- Expose `/up` (Laravel 11 default health endpoint) to the uptime checker.
- Dashboard: queue depth, failed-jobs count, 5xx rate, slow-query count.

## 10. Launch-day checklist

- [ ] DNS points to production host with TTL ≤ 300s for fast rollback.
- [ ] HTTPS certificate issued and auto-renewal tested.
- [ ] `php artisan about` shows `env=production`, `debug=false`, `cache=file` (or redis).
- [ ] Full test suite green: `php artisan test`.
- [ ] Role-matrix test green: `php artisan test --filter=RoleAccessMatrixTest`.
- [ ] Seeded roles + shield permissions match the staging baseline.
- [ ] First real user (GM) can log in, switch to Arabic, and see the GM dashboard.
- [ ] Engineer can submit a sales request on a mobile device and attach a photo.
- [ ] Warehouse keeper receives the new-request email within 60s.
- [ ] Sales PDF opens in a browser and prints a full-page A4 layout with Arabic text rendered correctly.
- [ ] Arabic font file dropped at `public/fonts/IBMPlexSansArabic-Regular.ttf` before the first PDF is generated.
- [ ] Logo file (`public/images/logo.svg|png|webp`) installed.
- [ ] Backup cron scheduled and the first dump has been verified restorable on a scratch database.
- [ ] Incident runbook and on-call rotation shared with the team.

## 11. Rollback plan

1. Flip DNS back to the prior host (TTL ≤ 300s).
2. Restore `storage/app/visit-photos/` from the last good backup.
3. `mysql aljabali < backup-YYYY-MM-DD.sql.gz` against a fresh database, then repoint `.env`.
4. Communicate incident in the ops channel within 15 minutes.

---

**Done = every box in §10 checked, backup restore verified, and at least one cross-role end-to-end submission has completed on the production URL.**
