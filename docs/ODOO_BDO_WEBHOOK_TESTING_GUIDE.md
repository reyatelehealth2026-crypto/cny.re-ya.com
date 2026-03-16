# Odoo BDO Webhook Testing Guide

Updated guide for validating the current BDO/slip flow and local dashboard cache.

---

## 1. Scope

This guide covers these codepaths:

- `api/webhook/odoo.php` (webhook ingress + signature + idempotency)
- `classes/OdooWebhookHandler.php` (event routing, BDO context lifecycle)
- `classes/BdoContextManager.php` (open/close BDO context + statement PDF)
- `api/bdo-inbox-api.php` (read/write facade for matching)
- `cron/sync_odoo_dashboard_cache.php` + `api/odoo-dashboard-local.php` (local cache pipeline)

---

## 2. Prerequisites

1. DB schema migration done:

```bash
php install/migration_bdo_matching.php
php install/migration_bdo_context_v2.php
php install/migration_slip_verification.php
```

2. Odoo webhook secret and API key configured in runtime config.
3. `odoo_webhooks_log` table has writable access.

---

## 3. Quick Health Checks

```bash
curl -s "https://<host>/api/webhook/odoo.php"        # expected 405 (GET not allowed)
curl -s "https://<host>/api/odoo-webhooks-dashboard.php?action=health"
curl -s "https://<host>/api/bdo-inbox-api.php?action=health"
curl -s "https://<host>/api/odoo-dashboard-local.php?action=health"
```

Expected:

- webhook endpoint rejects non-POST
- other endpoints return `{"success":true,...}` and `status=ok`

---

## 4. Webhook Contract Validation

### 4.1 Required Headers

`api/webhook/odoo.php` requires:

- `X-Odoo-Signature`
- `X-Odoo-Timestamp`
- `X-Odoo-Delivery-Id`
- `X-Odoo-Event` (or `event` in payload)

Missing required headers should return 4xx and be logged with stable `error_code`.

### 4.2 Idempotency

Send the same payload twice with same `X-Odoo-Delivery-Id`.

Expected second response:

- HTTP 200
- `status = duplicate`
- no duplicate state mutation

---

## 5. BDO Context Lifecycle Tests

### 5.1 `bdo.confirmed` opens context

1. Send valid `bdo.confirmed` webhook payload.
2. Verify row in `odoo_bdo_context` by `(line_user_id, bdo_id)`.
3. Verify fields populated when available:

- `state = waiting`
- `qr_payload`
- `statement_pdf_path`
- `financial_summary_json`
- `selected_invoices_json`
- `selected_credit_notes_json`

### 5.2 `bdo.done` / `bdo.cancelled` closes context

1. Send `bdo.done` or `bdo.cancelled` for same `bdo_id`.
2. Verify context row state is updated to `done` / `cancel`.

---

## 6. BDO Inbox API Write Path Tests

All write actions are Odoo-first and must not perform local-only success.

### 6.1 Match

```bash
curl -X POST "https://<host>/api/bdo-inbox-api.php" \
  -H "Content-Type: application/json" \
  -H "X-Internal-Secret: <internal_secret_if_enabled>" \
  -d '{
    "action":"slip_match_bdo",
    "line_user_id":"Uxxxxxxxx",
    "slip_inbox_id":113,
    "matches":[{"bdo_id":437,"amount":15950.00}],
    "note":"test match"
  }'
```

Expected:

- success only when Odoo confirms
- local `odoo_slip_uploads` updated after success (status `matched`)

### 6.2 Unmatch block states

Call `slip_unmatch` with slip already in `posted`/`done`.

Expected:

- rejected by validation
- no local reset to `new`

### 6.3 Upload ambiguity handling

Call `slip_upload` without `bdo_id` when customer has multiple open contexts.

Expected:

- `success=false`
- `error` explains ambiguity
- `ambiguous_bdos` array returned for UI selection

---

## 7. Local Dashboard Cache Runbook

### 7.1 Full bootstrap

```bash
php cron/sync_odoo_dashboard_cache.php full
php verify_odoo_local_cache.php
```

### 7.2 Incremental cron

```bash
*/5 * * * * php /path/to/project/cron/sync_odoo_dashboard_cache.php incremental
```

### 7.3 Optional manual web trigger

```text
/trigger_odoo_sync.php?token=sync123&full=1
```

Security note: token is hardcoded in source. Restrict by network/ACL before use.

---

## 8. Troubleshooting

### 8.1 `Unauthorized` on `bdo-inbox-api`

- `INTERNAL_API_SECRET` is configured but `X-Internal-Secret` missing/mismatch.

### 8.2 Cache tables empty

1. Check `odoo_webhooks_log` has successful events.
2. Run full sync once.
3. Check latest job in `odoo_sync_log`.

### 8.3 Missing migration SQL file reference

Some scripts mention `database/migration_odoo_dashboard_cache.sql`, but this file is not present in this repository snapshot. Keep deployment artifacts aligned before provisioning new environments.

---

## 9. Success Criteria

- webhook signature + idempotency checks pass
- BDO context opens/closes correctly from webhook events
- `slip_match_bdo` / `slip_unmatch` follow Odoo-first behavior
- ambiguous slip uploads return actionable `ambiguous_bdos`
- local cache sync jobs succeed and dashboard local API returns data
