---
trigger: always_on
---

# AI Coding Agent Rules for Re-Ya ERP Integration Project

## Project Overview
- **Stack**: PHP backend (`cny.re-ya.com`) + Next.js frontend (`inboxreyanextjs`)
- **Domain**: Odoo ERP integration, BDO-Slip payment matching, Customer 360 dashboard
- **Languages**: PHP 7.4+, TypeScript, JavaScript, HTML/CSS (Tailwind)

## Code Style

### PHP
- Use PSR-4 autoloading with `classes/` directory
- Service classes: `Odoo*Service.php` pattern (e.g., `OdooSyncService`, `OdooCustomerDashboardService`)
- API endpoints: `api/*.php` with `action` parameter routing
- Always validate config on startup; use `ConfigValidator` pattern
- Return JSON with ISO 8601 timestamps

### TypeScript/Next.js
- API routes: `src/app/api/{feature}/route.ts`
- Components: `src/components/inbox/*.tsx` with shadcn/ui + Tailwind
- Types: Define in `src/types/odoo.ts`
- Use `src/lib/odoo-api.ts` for type-safe API calls
- Use `src/lib/php-bridge.ts` for PHP backend communication

### JavaScript (Legacy Dashboard)
- File: `odoo-dashboard.js` — monolithic, function-based
- Naming: `camelCase` for functions, descriptive prefixes (`bsm*` for BDO-Slip Match, `load*` for data fetching)
- Use template literals for HTML generation
- Badge helpers: `getMatchConfidenceBadge()`, `getDeliveryTypeBadge()`

## Architecture Patterns

### API Design
- PHP endpoints proxy to Odoo via `/reya/*` REST endpoints
- Actions: `customer_360`, `webhook_stats_mini`, `dlq_list`, `dlq_retry`
- Always include pagination for list endpoints
- Return structured errors: `{success: false, error: string}`

### BDO-Slip Matching
- 3-pass matching algorithm: Pass 0 (bdo_id direct) → Pass 1 (exact amount) → Pass 2 (±5%)
- Match confidence levels: `exact_bdo`, `bdo_prepayment`, `exact`, `partial`, `manual`, `unmatched`
- Support partial payments with progress tracking

### Odoo URL Generation
```javascript
function generateOdooUrl(model, id) {
  return `https://erp.cnyrxapp.com/web#id=${id}&model=${model}&view_type=form`;
}
```

## File Organization

| Path | Purpose |
|------|---------|
| `api/odoo-webhooks-dashboard.php` | Main dashboard API |
| `api/slip-match-orders.php` | Slip matching operations |
| `classes/Odoo*Service.php` | Business logic services |
| `cron/*.php` | Scheduled tasks (projection rebuild, DLQ cleanup) |
| `config/config.php` | Environment configuration |

## Key Conventions

1. **Never hardcode credentials** — use `config.php` or environment variables
2. **Guard destructive actions** — check `payment_state` before unmatch operations
3. **Thai language support** — UI labels in Thai, code comments in English
4. **Webhook handling** — log to `odoo_webhook_log`, failed items to DLQ
5. **Customer projection** — use `odoo_customer_projection` table for dashboard queries

## Testing Checklist
- [ ] API returns correct structure with `success` boolean
- [ ] Timestamps in ISO 8601 format
- [ ] Error handling for missing Odoo partner
- [ ] Partial payment progress calculation
- [ ] Badge rendering for all match confidence levels

## Documentation
- Manual: `ODOO_SYSTEM_MANUAL_TH.html` — keep Section 6 updated for all workflow cases
- Specs: `reya_bdo_matching_workflow.md`, `reya_slip_api_spec.md`
