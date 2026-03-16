# Odoo Dashboard and BDO Operations Runbook

Updated: March 2026

## Scope

This runbook documents the operational paths introduced by the recent Odoo integration updates:

- Dashboard live API and local cache API
- BDO Confirm slip-matching API
- Circuit breaker and sync operations

## Codepaths Covered

| Area | Primary Files |
|---|---|
| Live dashboard API | `api/odoo-dashboard-api.php`, `api/odoo-dashboard-functions.php` |
| Local dashboard cache API | `api/odoo-dashboard-local.php`, `cron/sync_odoo_dashboard_cache.php` |
| BDO matching API | `api/bdo-inbox-api.php`, `classes/BdoSlipContract.php`, `classes/BdoContextManager.php` |
| Odoo client resilience | `classes/OdooAPIClient.php`, `classes/OdooAPIPool.php`, `classes/OdooCircuitBreaker.php` |
| Local cache diagnostics | `verify_odoo_local_cache.php`, `trigger_odoo_sync.php` |

## Setup Checklist

1. Ensure Odoo config is present in app config:
   - `ODOO_API_BASE_URL`
   - `ODOO_API_KEY`
2. If internal API auth is required, set `INTERNAL_API_SECRET`.
3. Run BDO schema migrations (idempotent):
   ```bash
   php install/migration_bdo_matching.php
   php install/migration_bdo_context_v2.php
   php install/migration_slip_verification.php
   ```
4. Apply optional Odoo API performance indexes:
   ```bash
   mysql -u <user> -p <database> < database/migration_odoo_api_performance.sql
   ```

## Local Dashboard Cache Operations

### Health check

```bash
curl -s "https://<host>/api/odoo-dashboard-local.php?action=health"
```

Expected success response contains `status=ok` and table existence/count details.

### Manual sync

```bash
# Incremental (default)
php cron/sync_odoo_dashboard_cache.php incremental

# Full rebuild
php cron/sync_odoo_dashboard_cache.php full
```

Optional per-bot sync:

```bash
php cron/sync_odoo_dashboard_cache.php incremental <line_account_id>
```

### Cron recommendation

```bash
*/5 * * * * php /path/to/project/cron/sync_odoo_dashboard_cache.php incremental
```

### Quick verification

```bash
php verify_odoo_local_cache.php
```

### Local API actions (read-only)

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

## BDO Inbox API Operations

Endpoint: `POST /api/bdo-inbox-api.php`  
Header (when configured): `X-Internal-Secret: <INTERNAL_API_SECRET>`

### Read actions

- `bdo_list`
- `bdo_detail` (live Odoo with local fallback for read-only display)
- `slip_list`
- `bdo_context`
- `matching_workspace`
- `statement_pdf_url`

### Write actions (Odoo-first)

- `slip_match_bdo`
- `slip_unmatch`
- `slip_upload`

Write behavior is intentionally strict:

- Odoo is authoritative for match/unmatch status.
- Local DB updates only happen after Odoo success.
- `slip_inbox_id` is the canonical slip ID for match/unmatch.
- `bdo_id` is required for deterministic matching when multiple BDO contexts are open.

### Example: matching workspace fetch

```json
{
  "action": "matching_workspace",
  "line_user_id": "Uxxxxxxxx",
  "partner_id": "74728"
}
```

### Example: match slip to BDO

```json
{
  "action": "slip_match_bdo",
  "line_user_id": "Uxxxxxxxx",
  "slip_inbox_id": 113,
  "matches": [
    { "bdo_id": 437, "amount": 15950.00 }
  ],
  "note": "Matched in BDO confirm workspace"
}
```

### Example: slip upload with explicit BDO

```json
{
  "action": "slip_upload",
  "line_user_id": "Uxxxxxxxx",
  "bdo_id": 437,
  "amount": 15950.00,
  "transfer_date": "2026-03-16",
  "slip_image": "<base64_without_data_uri_prefix>"
}
```

If `bdo_id` is omitted and the customer has multiple open BDO contexts, the API returns an error with `ambiguous_bdos`.

## Circuit Breaker Operations

Status:

```json
{ "action": "circuit_breaker_status" }
```

Reset one circuit:

```json
{ "action": "circuit_breaker_reset", "service": "odoo_reya" }
```

Service keys used by dashboard API:

- `odoo_reya`
- `odoo_cny`

## Common Pitfalls

- Local cache API returns empty sets when cache tables are missing or unsynced.
- `slip_unmatch` is blocked for slips in posted/done states by contract.
- `statement_pdf_url` can fall back to direct Odoo URL if local cached file is absent.
- Sync data quality depends on `odoo_webhooks_log` successful events (`status = 'success'`).

## Known Gap

This repository contains cache sync and verification scripts, but no checked-in SQL bootstrap file for creating the local cache tables referenced by `api/odoo-dashboard-local.php`.  
If tables are missing, provision them using your environment's DB migration package before running sync.
