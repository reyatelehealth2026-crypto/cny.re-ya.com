# Odoo Dashboard / API Performance Plan

## What was optimized now

These changes are already implemented in the current branch:

1. **Odoo API timeout budget is now configurable**
   - `ODOO_API_TIMEOUT` defaults to 15s instead of 30s.
   - `ODOO_API_CONNECT_TIMEOUT` defaults to 3s.
   - `ODOO_API_RETRY_LIMIT` defaults to 1 transient retry.
   - Read-heavy endpoints now use shorter budgets than upload/PDF flows.

2. **Odoo API client is less noisy and more resilient**
   - Retries only on transient cURL / HTTP failures.
   - Production no longer logs full successful API payloads by default.
   - Table existence checks and rate-limit counters are cached per request.

3. **Dashboard API now fails soft**
   - Hot actions have broader cache coverage.
   - If a live request fails, the API can serve a recent stale cache instead of hard-failing.
   - This reduces visible timeout/error spikes during temporary Odoo or DB slowness.

4. **Local dashboard mode is wired in safely**
   - `odoo-dashboard.php` now loads `odoo-dashboard-local.js`.
   - Local mode only activates when cache tables exist and contain data.
   - If local mode fails, the page falls back to the current dashboard API.

5. **Local dashboard path had blocking bugs fixed**
   - Broken SQL `CASE` expressions were corrected.
   - Customer detail override now maps parameters correctly.
   - Customer pagination in local mode now updates the real offset.
   - Local health endpoint now reports `local_enabled` / `has_data`.

6. **Webhook debug logging is now opt-in**
   - Signature and processing debug logs are gated by env flags.
   - Default behavior reduces log I/O and avoids flooding production logs.

## Recommended environment flags

Set only when needed:

```bash
ODOO_API_TIMEOUT=15
ODOO_API_CONNECT_TIMEOUT=3
ODOO_API_RETRY_LIMIT=1
ODOO_DASHBOARD_STALE_TTL=300
ODOO_API_DEBUG_LOG=false
ODOO_WEBHOOK_DEBUG_LOG=false
ODOO_WEBHOOK_SIGNATURE_DEBUG=false
```

## Best next step for maximum impact

If the goal is **minimum error + minimum timeout + fastest dashboard**, the next architecture should be:

### 1) Fast-ack webhook
- Accept webhook
- Validate signature and idempotency
- Persist raw event
- Return `200` immediately
- Process sync / notification / enrichment asynchronously

### 2) Local read model for dashboard
- Dashboard should read from:
  - `odoo_orders_summary`
  - `odoo_customers_cache`
  - `odoo_invoices_cache`
  - `odoo_slips_cache`
  - `odoo_order_events`
- Avoid JSON-heavy reads from `odoo_webhooks_log` on normal page loads.

### 3) Async notification delivery
- Move LINE push calls out of the webhook request.
- Use `odoo_notification_queue` + worker/cron.
- Keep webhook request under the 5s budget even during LINE API slowness.

### 4) Remove duplicate APIs
- Keep one production dashboard API.
- Keep one webhook entrypoint.
- Archive or remove legacy duplicates after parity is confirmed.

### 5) Add health + freshness checks
- Last successful cache sync age
- Queue backlog depth
- DLQ count
- Odoo upstream latency percentile
- Dashboard cache hit rate / stale-hit rate

## Suggested rebuild rollout

### Phase 1 - Stabilize
- Done in this branch: timeout alignment, stale cache, safer local mode, reduced log noise.

### Phase 2 - Activate local read model
- Create/verify migration for local cache tables.
- Run `cron/sync_odoo_dashboard_cache.php` every 5 minutes.
- Confirm `verify_odoo_local_cache.php` passes.
- Switch overview/customers first, then invoices/slips/timeline.

### Phase 3 - Move webhook work async
- Keep raw event storage synchronous.
- Push notification + projection jobs to worker queue.
- Add retry / DLQ metrics to dashboard.

### Phase 4 - Retire heavy fallback paths
- Reduce direct Odoo reads from admin dashboard.
- Use live Odoo only for drill-down actions that truly need it.

## Practical success criteria

- Dashboard first paint with cached/local data: **< 1.5s**
- Hot list APIs from local tables: **< 500ms**
- Webhook response time p95: **< 2s**
- Odoo timeout-related user errors: **reduced by at least 50%**
- Customer detail/API fallback still works when local cache is cold
