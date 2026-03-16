# Odoo Dashboard Performance and Operations Guide

This page documents the current behavior of the Odoo dashboard stack and how to operate it safely in production.

## Scope and codepaths

The guide covers these codepaths:

- `api/odoo-dashboard-api.php` (main dashboard API, mixed live/fallback)
- `api/odoo-dashboard-local.php` (local-table read API)
- `odoo-dashboard.js` and `odoo-dashboard-local.js` (frontend routing + local override)
- `cron/sync_odoo_dashboard_cache.php` (local cache sync worker)
- `database/migration_odoo_dashboard_cache.sql` (local cache schema)
- `api/webhook/odoo.php` and `classes/OdooWebhookHandler.php` (webhook ingestion path)
- `classes/OdooAPIClient.php` and `config/config.php` (timeouts, retries, logging flags)

## Architecture and intent

### Main dashboard API (`api/odoo-dashboard-api.php`)

- Accepts both `POST` JSON and `GET` query requests.
- Uses `action` routing; empty `action` defaults to `health`.
- Adds server-side file cache for hot actions (TTL varies per action).
- On action failure, returns stale cached payload (if still within stale window) instead of hard-failing.
- Response metadata indicates cache mode:
  - `meta.cache = "hit"` for fresh cache
  - `meta.cache = "stale"` with `meta.warning` when serving fallback data

### Local dashboard API (`api/odoo-dashboard-local.php`)

- Reads only local denormalized tables; no Odoo API calls.
- Empty `action` also defaults to `health`.
- `health` reports table existence/count and `local_enabled`/`has_data`.
- Used by frontend as first choice when local cache is available and has rows.

### Frontend local-mode switch (`odoo-dashboard-local.js`)

- `initLocalApi()` calls local `health`.
- Local mode is enabled only when:
  - local endpoint is reachable, and
  - at least one local table has data (`has_data = true`).
- If local calls fail, UI falls back to original functions in `odoo-dashboard.js`.

### Local cache sync pipeline

- `cron/sync_odoo_dashboard_cache.php` populates:
  - `odoo_orders_summary`
  - `odoo_customers_cache`
  - `odoo_invoices_cache`
  - `odoo_slips_cache`
  - `odoo_order_events`
- Source data primarily comes from `odoo_webhooks_log` (plus `odoo_slip_uploads` for slips).
- Supports `full`, `incremental`, and per-domain sync modes.

## Runtime controls (env/config)

Configured in `config/config.php`:

```bash
ODOO_API_TIMEOUT=15
ODOO_API_CONNECT_TIMEOUT=3
ODOO_API_RETRY_LIMIT=1
ODOO_DASHBOARD_STALE_TTL=300
ODOO_API_DEBUG_LOG=false
ODOO_WEBHOOK_DEBUG_LOG=false
ODOO_WEBHOOK_SIGNATURE_DEBUG=false
```

Behavior notes:

- `ODOO_API_TIMEOUT` default is 15s.
- `ODOO_API_CONNECT_TIMEOUT` default is 3s.
- `ODOO_API_RETRY_LIMIT` default is 1 retry (transient failures only).
- `ODOO_DASHBOARD_STALE_TTL` controls maximum stale fallback age for main dashboard API.
- Debug flags are opt-in and should stay off in normal production traffic.

## Public interface reference

### Main dashboard API (`/api/odoo-dashboard-api.php`)

Common actions used by dashboard UI:

- `health`
- `stats`
- `list`
- `detail`
- `order_grouped_today`
- `customer_list`
- `invoice_list`
- `order_list`
- `customer_detail`
- `overview_today`
- `notification_log`
- `order_timeline`
- `odoo_orders`
- `odoo_invoices`
- `odoo_slips`
- `odoo_bdo_list_api`
- `odoo_bdo_detail_api`
- `odoo_slip_match_api`
- `odoo_slip_unmatch_api`
- `odoo_bdo_statement_pdf`

Example:

```bash
curl -sS -X POST https://<host>/api/odoo-dashboard-api.php \
  -H 'Content-Type: application/json' \
  -d '{"action":"overview_today"}'
```

### Local dashboard API (`/api/odoo-dashboard-local.php`)

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
curl -sS -X POST https://<host>/api/odoo-dashboard-local.php \
  -H 'Content-Type: application/json' \
  -d '{"action":"customers_list","limit":30,"offset":0,"search":"CUST"}'
```

### Odoo webhook endpoint (`/api/webhook/odoo.php`)

Request contract:

- Method: `POST`
- Required headers:
  - `X-Odoo-Delivery-Id`
  - `X-Odoo-Signature`
  - `X-Odoo-Timestamp`
- Event type accepted from `X-Odoo-Event` or payload `event`.

Error mapping:

- `INVALID_JSON` -> HTTP `400`
- `MISSING_SIGNATURE` -> HTTP `400`
- `MISSING_TIMESTAMP` -> HTTP `400`
- `MISSING_DELIVERY_ID` -> HTTP `400`
- `INVALID_SIGNATURE` -> HTTP `401`
- Retriable processing failures -> HTTP `500`

## Setup and rollout runbook

1. Apply local cache schema:

```bash
mysql -u <user> -p <db_name> < database/migration_odoo_dashboard_cache.sql
```

2. Run first sync:

```bash
php cron/sync_odoo_dashboard_cache.php full
```

3. Verify local cache health:

```bash
php verify_odoo_local_cache.php
```

4. Schedule incremental sync every 5 minutes:

```bash
*/5 * * * * php /path/to/project/cron/sync_odoo_dashboard_cache.php incremental
```

5. Validate local-mode readiness:

```bash
curl -sS https://<host>/api/odoo-dashboard-local.php?action=health
```

Expected: `success=true`, `data.status="ok"`, and `data.local_enabled=true` after data is synced.

## Troubleshooting and common pitfalls

### Dashboard still uses old API path

Likely causes:

- Local tables exist but are empty.
- `health` returns `has_data=false`.

Actions:

- Run `full` sync once, then keep `incremental` cron active.
- Re-check via `verify_odoo_local_cache.php`.

### UI looks healthy but data is stale

Main API can return stale cache with `success=true`. Check response metadata:

- `meta.cache = "stale"`
- `meta.warning` contains root action error

Actions:

- Inspect upstream DB/Odoo availability.
- Temporarily lower stale window for stricter freshness if needed.

### Customer search feels slower than list view

- `customer_list` with non-empty `search` intentionally skips server cache in `dashboardApiShouldCache()`.
- This avoids serving stale search-specific results.

### Local action mismatch errors

- `api/odoo-dashboard-local.php` does not implement `refresh_cache` even though older comments reference it.
- Use `cron/sync_odoo_dashboard_cache.php` for refresh operations.

### Webhook verification failures

Common causes:

- Missing/invalid signature headers
- Timestamp outside tolerance
- Legacy signature format still in sender

Actions:

- Validate sender headers and clock sync.
- Enable `ODOO_WEBHOOK_SIGNATURE_DEBUG=true` temporarily for diagnosis.
- Turn debug flag back off after fix.

### Emergency/manual sync endpoint

- `trigger_odoo_sync.php` executes sync via a query token.
- Treat it as internal-only operational helper and do not expose publicly without network restrictions.

## Current constraints

- Local mode requires both schema and non-empty cache data.
- Cache freshness depends on cron reliability; missing cron directly affects dashboard freshness.
- Main dashboard API can intentionally prefer availability (stale fallback) over hard failure.
