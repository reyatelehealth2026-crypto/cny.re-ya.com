# Odoo Dashboard Performance & Operations Runbook

This document reflects behavior verified from the current implementation in:
- `api/odoo-dashboard-api.php`
- `api/odoo-dashboard-local.php`
- `api/webhook/odoo.php`
- `classes/OdooAPIClient.php`
- `cron/sync_odoo_dashboard_cache.php`
- `database/migration_odoo_dashboard_cache.sql`
- `odoo-dashboard.php`
- `odoo-dashboard-local.js`
- `verify_odoo_local_cache.php`

## 1. What Is Implemented Now

### Odoo API client resiliency and timeout budgets
- `ODOO_API_TIMEOUT` default is `15` seconds.
- `ODOO_API_CONNECT_TIMEOUT` default is `3` seconds.
- `ODOO_API_RETRY_LIMIT` default is `1` transient retry.
- Retry is limited to transient cURL failures and HTTP `408/425/429/500/502/503/504`.
- Endpoint-specific budgets are enforced in `OdooAPIClient::resolveTimeoutForEndpoint()`:
  - `/reya/health` capped to 5s.
  - Upload/PDF paths (for example `/reya/slip/upload`, `/reya/bdo/statement-pdf`) allowed up to 25s.
  - Read-heavy dashboard endpoints capped to 12s.

### Dashboard API soft-fail behavior
- `api/odoo-dashboard-api.php` uses file-based response caching for hot actions.
- On action failure, the API attempts stale cache fallback via `dashboardApiCacheGetStale()`.
- Stale window is controlled by `ODOO_DASHBOARD_STALE_TTL` (default `300` seconds).
- This reduces visible failures during short upstream/database incidents.

### Local dashboard read model (safe auto-enable)
- `odoo-dashboard.php` loads `odoo-dashboard-local.js` after `odoo-dashboard.js`.
- `odoo-dashboard-local.js` checks `api/odoo-dashboard-local.php?action=health`.
- Local mode is enabled only when local tables are available and contain data.
- If local calls fail, UI falls back to original webhook/dashboard API functions.

### Webhook debug logs are opt-in
- Signature/process debug logs are controlled by env flags:
  - `ODOO_API_DEBUG_LOG`
  - `ODOO_WEBHOOK_DEBUG_LOG`
  - `ODOO_WEBHOOK_SIGNATURE_DEBUG`
- Default behavior reduces production log noise and I/O.

## 2. Runtime Interfaces

### Environment flags (effective defaults)

```bash
ODOO_API_TIMEOUT=15
ODOO_API_CONNECT_TIMEOUT=3
ODOO_API_RETRY_LIMIT=1
ODOO_DASHBOARD_STALE_TTL=300
ODOO_API_DEBUG_LOG=false
ODOO_WEBHOOK_DEBUG_LOG=false
ODOO_WEBHOOK_SIGNATURE_DEBUG=false
```

### Public API: `api/odoo-dashboard-api.php`

Reads/writes are selected via `action` in GET or POST payload.

Frequently used read actions include:
- `health`
- `stats`
- `customer_list`
- `invoice_list`
- `order_list`
- `overview_today`
- `customer_full_detail`
- `overview_combined`

Operational actions include:
- `dlq_list`
- `dlq_stats`
- `dlq_retry`
- `order_status_override`
- `order_note_add`
- `slip_match_bdo`
- `slip_unmatch`

Example:

```bash
curl -sS "https://<host>/api/odoo-dashboard-api.php?action=health"
```

### Public API: `api/odoo-dashboard-local.php`

Implemented actions:
- `health`
- `overview_kpi`
- `orders_list`
- `orders_today`
- `customers_list`
- `customer_detail`
- `invoices_list`
- `invoices_overdue`
- `slips_list`
- `slips_pending`
- `order_timeline`
- `search_global`
- `cache_status`

Example:

```bash
curl -sS -X POST "https://<host>/api/odoo-dashboard-local.php" \
  -H "Content-Type: application/json" \
  -d '{"action":"overview_kpi"}'
```

### Manual sync trigger (web endpoint)
- `trigger_odoo_sync.php?token=sync123`
- Add `&full=1` for full sync.
- This executes `php cron/sync_odoo_dashboard_cache.php <full|incremental>`.

## 3. Data Flow Architecture

1. Odoo sends webhook to `api/webhook/odoo.php`.
2. `OdooWebhookHandler` stores/logs events in `odoo_webhooks_log` and processes business logic.
3. `cron/sync_odoo_dashboard_cache.php` projects webhook data into local read tables:
   - `odoo_orders_summary`
   - `odoo_customers_cache`
   - `odoo_invoices_cache`
   - `odoo_slips_cache`
   - `odoo_order_events`
4. Dashboard UI attempts local reads first (when local mode is enabled).
5. If local mode is unavailable, dashboard uses `api/odoo-dashboard-api.php` with cache/stale fallback.

## 4. Setup and Rollout Checklist

1. Apply schema:

```bash
mysql -u <user> -p <db_name> < database/migration_odoo_dashboard_cache.sql
```

2. Initial backfill:

```bash
php cron/sync_odoo_dashboard_cache.php full
```

3. Verify system:

```bash
php verify_odoo_local_cache.php
```

4. Schedule incremental sync every 5 minutes:

```bash
*/5 * * * * php /path/to/project/cron/sync_odoo_dashboard_cache.php
```

5. Confirm local health:

```bash
curl -sS "https://<host>/api/odoo-dashboard-local.php?action=health"
```

Expected: `local_enabled: true` and `has_data: true`.

## 5. Troubleshooting Guide

### Local mode not activating
Symptoms:
- Dashboard keeps using legacy/webhook API path.

Checks:
- `api/odoo-dashboard-local.php?action=health` should return `success=true`, `local_enabled=true`, `has_data=true`.
- Run `php verify_odoo_local_cache.php`.
- Ensure `odoo-dashboard-local.js` is loaded after `odoo-dashboard.js` in `odoo-dashboard.php`.

### Local tables exist but are empty
Symptoms:
- `health` returns `local_enabled=false`, `has_data=false`.

Actions:
- Run `php cron/sync_odoo_dashboard_cache.php full`.
- Check `odoo_sync_log` for latest job status and error.
- Confirm `odoo_webhooks_log` has successful source records.

### Dashboard requests time out or fail intermittently
Symptoms:
- API errors spike but then recover.

Checks:
- Inspect responses for `meta.cache = "stale"` from `api/odoo-dashboard-api.php`.
- Review `ODOO_DASHBOARD_STALE_TTL` value.
- Review Odoo upstream latency and recent webhook/database incidents.

### Excessive Odoo/webhook logging
Symptoms:
- Log files grow rapidly with debug data.

Actions:
- Ensure debug flags are disabled in production:
  - `ODOO_API_DEBUG_LOG=false`
  - `ODOO_WEBHOOK_DEBUG_LOG=false`
  - `ODOO_WEBHOOK_SIGNATURE_DEBUG=false`

## 6. Constraints and Pitfalls

- Local mode is data-gated, not only table-gated: at least one local table must contain rows.
- Most local reads are filtered by active `line_account_id` from session/request; cross-account testing should set context explicitly.
- `trigger_odoo_sync.php` uses a static query token (`sync123`) and should be protected/replaced before public exposure.
- Dashboard API file cache is stored under temp directory (`sys_get_temp_dir()/cny_odoo_dashboard_cache`), so OS temp cleanup can flush warm cache.
- In `api/odoo-dashboard-local.php`, the top comment mentions `refresh_cache`, but there is no implemented `refresh_cache` action in the switch block.

## 7. Recommended Next Improvements

1. Replace static sync trigger token with env-configured secret and restrict by IP/auth.
2. Add dashboard-level visibility for stale-hit rate and local-mode enablement status.
3. Standardize cache helper implementation (currently duplicated logic exists across dashboard API files).
4. Move webhook non-critical downstream work to explicit async queue paths to reduce webhook critical path time.
