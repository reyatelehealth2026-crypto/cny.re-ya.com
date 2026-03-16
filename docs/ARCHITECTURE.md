# 🏗️ LINE Telepharmacy CRM - Architecture

Version 3.2 | Last Updated: March 2026

---

## System Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     LINE Telepharmacy CRM Platform                           │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐                   │
│  │   LINE App   │    │  Web Browser │    │  Admin Panel │                   │
│  │   (LIFF)     │    │  (Landing)   │    │  (Backend)   │                   │
│  └──────┬───────┘    └──────┬───────┘    └──────┬───────┘                   │
│         │                   │                   │                            │
│         ▼                   ▼                   ▼                            │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                        Entry Points                                  │    │
│  │  /liff/index.php   │   /index.php    │   /admin/    │  /webhook.php │    │
│  │  (LIFF SPA)        │   (Landing)     │   (Admin)    │  (LINE Hook)  │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                    │                                         │
│                                    ▼                                         │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                          API Layer (/api/)                           │    │
│  │  checkout │ member │ orders │ ai-chat │ pharmacy-ai │ inbox-v2 │... │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                    │                                         │
│                                    ▼                                         │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                    Service Classes (/classes/)                       │    │
│  │  LineAPI │ LoyaltyPoints │ GeminiAI │ WMSService │ POSService │...  │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                    │                                         │
│                                    ▼                                         │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                       Database (MySQL/MariaDB)                       │    │
│  │                    40+ Tables | UTF8MB4 Encoding                     │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| **Frontend** | HTML5, CSS3, JavaScript (ES6+), LIFF SDK |
| **Backend** | PHP 8.0+ |
| **Database** | MySQL 5.7+ / MariaDB 10.2+ |
| **AI** | Google Gemini AI, OpenAI (optional) |
| **APIs** | LINE Messaging API, LINE LIFF, Telegram Bot API |
| **Real-time** | WebSocket (Node.js) |

---

## Directory Structure

```
/
├── api/                       # REST API endpoints (59 files)
│   ├── checkout.php           # E-commerce checkout
│   ├── member.php             # Member management
│   ├── orders.php             # Order management
│   ├── ai-chat.php            # AI assistant
│   ├── pharmacy-ai.php        # Pharmacy AI engine
│   ├── inbox-v2.php           # Chat inbox API
│   ├── points.php             # Loyalty points
│   └── ...
│
├── classes/                   # Service classes (74 files)
│   ├── LineAPI.php            # LINE Messaging API
│   ├── LoyaltyPoints.php      # Points & rewards
│   ├── GeminiAI.php           # Google Gemini integration
│   ├── WMSService.php         # Warehouse management
│   ├── POSService.php         # Point of sale
│   ├── BusinessBot.php        # Business logic bot
│   └── ...
│
├── config/                    # Configuration
│   ├── config.php             # Main configuration
│   ├── database.php           # Database connection
│   └── installed.lock         # Installation marker
│
├── cron/                      # Scheduled tasks (14 files)
│   ├── medication_reminder.php
│   ├── appointment_reminder.php
│   ├── process_broadcast_queue.php
│   └── ...
│
├── database/                  # SQL files (36 files)
│   ├── install_complete_latest.sql
│   ├── migration_*.sql
│   └── ...
│
├── includes/                  # Shared includes (97 files)
│   ├── header.php
│   ├── footer.php
│   ├── auth.php
│   └── ...
│
├── install/                   # Installation wizard
│   ├── wizard.php             # Modern 7-step installer
│   ├── wizard-api.php         # Installer API
│   └── ...
│
├── liff/                      # LIFF SPA Application
│   ├── index.php              # SPA entry point
│   └── assets/
│       ├── css/liff-app.css
│       └── js/
│           ├── store.js       # State management
│           ├── router.js      # Client-side routing
│           └── liff-app.js    # Main controller
│
├── modules/                   # Feature modules
│   ├── AIChat/                # AI chat module
│   ├── Onboarding/            # User onboarding
│   └── Core/                  # Core utilities
│
├── admin/                     # Admin panel pages
├── shop/                      # Shop management
├── inventory/                 # Inventory management
├── user/                      # User panel pages
│
├── index.php                  # Landing page
├── webhook.php                # LINE webhook handler
├── inbox-v2.php               # Chat inbox UI
└── ...
```

---

## Core Components

### 1. Entry Points

| Entry | Path | Description |
|-------|------|-------------|
| **LIFF SPA** | `/liff/index.php` | Main customer app (LINE) |
| **Landing** | `/index.php` | Public website |
| **Admin** | `/admin/` | Admin dashboard |
| **Webhook** | `/webhook.php` | LINE events handler |
| **Inbox** | `/inbox-v2.php` | Chat management |

### 2. API Layer (`/api/`)

All APIs return JSON and follow RESTful conventions:

- **Member APIs**: `member.php`, `health-profile.php`
- **E-commerce APIs**: `checkout.php`, `orders.php`, `shop-products.php`
- **Points APIs**: `points.php`, `rewards.php`, `points-rules.php`
- **Communication APIs**: `messages.php`, `inbox-v2.php`, `broadcast.php`
- **AI APIs**: `ai-chat.php`, `pharmacy-ai.php`, `symptom-assessment.php`
- **Pharmacy APIs**: `pharmacist.php`, `appointments.php`, `video-call.php`

### 3. Service Classes (`/classes/`)

| Class | Description |
|-------|-------------|
| `LineAPI` | LINE Messaging API wrapper |
| `LoyaltyPoints` | Points calculation & management |
| `GeminiAI` | Google Gemini AI integration |
| `BusinessBot` | Main business logic (172KB) |
| `WMSService` | Warehouse management |
| `POSService` | Point of sale operations |
| `InboxService` | Chat inbox operations |
| `NotificationService` | Push notifications |

---

## Odoo Dashboard + BDO Matching Subsystem

### Intent

This subsystem separates fast dashboard reads from authoritative Odoo writes:

- `api/odoo-dashboard-local.php` serves local denormalized tables for low-latency dashboard views.
- `api/odoo-dashboard-api.php` serves webhook/live projection endpoints and circuit-breaker controls.
- `api/bdo-inbox-api.php` is the normalized facade for BDO matching UI flows.
- `classes/BdoContextManager.php` and `classes/BdoSlipContract.php` enforce BDO/slip rules.

### Data Planes

| Plane | Primary code paths | Source of truth | Typical use |
|------|---------------------|-----------------|-------------|
| Local read model | `api/odoo-dashboard-local.php`, `cron/sync_odoo_dashboard_cache.php` | Local cache tables | KPI cards, customer/order/slip list screens |
| Live/projection API | `api/odoo-dashboard-api.php`, `api/odoo-dashboard-functions.php` | Webhook log + Odoo-backed reads | Monitoring, timeline, circuit breaker controls |
| BDO matching facade | `api/bdo-inbox-api.php`, `classes/BdoContextManager.php`, `classes/BdoSlipContract.php` | Odoo for write operations | Slip upload, match/unmatch, BDO context workspace |

### Public Interface Quick Reference

| Endpoint | Auth | Key actions |
|---------|------|-------------|
| `api/odoo-dashboard-local.php` | Session/bot context | `health`, `overview_kpi`, `orders_list`, `customers_list`, `invoices_list`, `slips_list`, `search_global`, `cache_status` |
| `api/odoo-dashboard-api.php` | Session/API caller | `overview_today`, `customer_full_detail`, `odoo_bdo_list_api`, `slip_match_bdo`, `slip_unmatch`, `circuit_breaker_status`, `circuit_breaker_reset` |
| `api/bdo-inbox-api.php` | `X-Internal-Secret` | `bdo_list`, `bdo_detail`, `slip_list`, `matching_workspace`, `slip_upload`, `slip_match_bdo`, `slip_unmatch`, `statement_pdf_url` |

### Workflow Notes (Verified)

1. `bdo.confirmed` webhooks call `BdoContextManager::openContext()` from `classes/OdooWebhookHandler.php`.
2. Context rows are keyed by `(line_user_id, bdo_id)` so one customer can have multiple open BDOs.
3. `bdo.done` and `bdo.cancelled` close context via `BdoContextManager::closeContext(...)`.
4. `action=slip_upload` in `api/bdo-inbox-api.php` auto-resolves `bdo_id` only when exactly one open context exists.
5. Match/unmatch actions call Odoo first and only update local cache after Odoo success.

### Operational Runbook

```bash
# 1) BDO schema baseline
php install/migration_bdo_matching.php

# 2) BDO context v2 fields (financial summary + canonical slip IDs)
php install/migration_bdo_context_v2.php

# 3) Slip verification fields (optional, if SlipMate is used)
php install/migration_slip_verification.php

# 4) Build local dashboard cache
php cron/sync_odoo_dashboard_cache.php full

# 5) Verify local cache readiness
php verify_odoo_local_cache.php
```

### API Examples

```bash
# Local dashboard health
curl -s "https://<host>/api/odoo-dashboard-local.php?action=health"

# BDO matching workspace (internal)
curl -s -X POST "https://<host>/api/bdo-inbox-api.php" \
  -H "Content-Type: application/json" \
  -H "X-Internal-Secret: <secret>" \
  -d '{"action":"matching_workspace","line_user_id":"Uxxxxxxxx"}'
```

### Common Pitfalls

- `slip_upload` without `bdo_id` fails with `ambiguous_bdos` when multiple open BDO contexts exist.
- Local cache sync script expects pre-created cache tables; validate schema availability before enabling cron.
- `bdo-inbox-api` write actions are intentionally Odoo-first; local-only state mutation is not a supported fallback path.

---

## Database Schema

### Core Tables

| Table | Description |
|-------|-------------|
| `line_accounts` | LINE OA accounts (multi-bot) |
| `admin_users` | System administrators |
| `users` | LINE users/customers |
| `members` | Extended member profiles |
| `health_profiles` | Health information |

### Messaging Tables

| Table | Description |
|-------|-------------|
| `messages` | Chat history |
| `conversation_assignments` | Multi-assignee support |
| `user_notes` | Internal notes |

### E-commerce Tables

| Table | Description |
|-------|-------------|
| `products` | Product catalog |
| `orders` | Customer orders |
| `order_items` | Order line items |
| `cart_items` | Shopping cart |

### Loyalty Tables

| Table | Description |
|-------|-------------|
| `points_transactions` | Points history |
| `points_rules` | Earning rules |
| `rewards` | Redeemable rewards |
| `redemptions` | Redemption records |

---

## Data Flows

### 1. User Registration Flow

```
LINE Follow → webhook.php → handleFollow()
    → Create user in DB
    → Send welcome message
    → Auto-tag (CRM)
    → Assign Rich Menu
```

### 2. Order Flow

```
LIFF Shop → Add to Cart → Checkout
    → /api/checkout.php
    → Create order
    → Deduct stock
    → Award points
    → Send LINE notification
    → Notify admin (Telegram)
```

### 3. AI Chat Flow

```
User message → /api/ai-chat.php
    → Load health profile
    → Check drug allergies
    → GeminiAI.chat()
    → Detect red flags
    → Return response + suggestions
```

---

## Cron Jobs

```bash
# Reminders
*/15 * * * * php /path/to/cron/medication_reminder.php
*/30 * * * * php /path/to/cron/appointment_reminder.php

# Broadcast & Campaigns
*/5 * * * * php /path/to/cron/process_broadcast_queue.php
*/10 * * * * php /path/to/cron/process_drip_campaigns.php

# Daily Tasks
0 7 * * * php /path/to/cron/scheduled_reports.php
0 9 * * * php /path/to/cron/medication_refill_reminder.php
0 10 * * * php /path/to/cron/reward_expiry_reminder.php

# Sync
* * * * * php /path/to/cron/sync_worker.php
```

---

## External Integrations

### LINE Platform
- **Messaging API**: Send/receive messages, rich menus
- **LIFF**: Web apps inside LINE
- **Login**: OAuth authentication

### AI Services
- **Google Gemini**: Primary AI (pharmacy assistant)
- **OpenAI**: Fallback AI option

### Notifications
- **Telegram**: Admin alerts
- **Email**: Optional notifications

### Third-party
- **CNY Pharmacy API**: Product sync

---

## Security

### Authentication
- **Admin**: Session-based (PHP sessions)
- **LIFF**: LINE ID Token validation
- **API**: Token/Session validation

### Best Practices
- Password hashing (bcrypt)
- CSRF protection
- SQL injection prevention (PDO)
- XSS prevention (htmlspecialchars)
- HTTPS required for webhook

---

## Performance

### Optimizations
- Database indexes on frequently queried columns
- Lazy loading for large datasets
- Caching for static content
- Async processing for broadcasts

### Scalability
- Stateless API design
- Queue-based broadcast processing
- Modular architecture

---

## Development

### Requirements
- PHP >= 8.0
- MySQL >= 5.7 / MariaDB >= 10.2
- Composer (for dependencies)
- Node.js (for WebSocket server)

### Setup
```bash
# Install PHP dependencies
composer install

# Copy config
cp config/config.example.php config/config.php

# Run installer
open http://yourdomain.com/install/wizard.php
```

### Testing
```bash
# Run PHPUnit tests
./vendor/bin/phpunit
```

---

*For more details, see [PROJECT_FLOW_DOCUMENTATION.md](PROJECT_FLOW_DOCUMENTATION.md)*
