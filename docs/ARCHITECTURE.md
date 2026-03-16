# 🏗️ LINE Telepharmacy CRM - Architecture

Version 3.3 | Last Updated: March 2026

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
- **Odoo Dashboard APIs**: `odoo-dashboard-api.php`, `odoo-webhooks-dashboard.php`, `odoo-dashboard-local.php`
- **BDO/Slip Matching API**: `bdo-inbox-api.php` (internal facade for inbox matching workspace)

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
| `OdooCustomerDashboardService` | Customer 360 aggregation (parallel Odoo API + webhook fallback) |
| `OdooAPIClient` | Odoo JSON-RPC client with retry + circuit breaker |
| `OdooAPIPool` | Parallel Odoo calls via `curl_multi` |
| `OdooCircuitBreaker` | Upstream resilience guard (`closed/open/half_open`) |
| `BdoContextManager` | Multi-BDO context lifecycle for slip auto-attach |
| `BdoSlipContract` | Canonical slip/BDO IDs, statuses, and validation rules |

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

### 4. BDO Context + Slip Matching Flow

```
Odoo webhook bdo.confirmed
    → OdooWebhookHandler::handleBdoConfirmed()
    → BdoContextManager::openContext()
    → Save per-customer context in odoo_bdo_context
      (line_user_id + bdo_id + delivery_type + financial_summary + statement_pdf_path)

Customer uploads slip (inbox / dashboard)
    → /api/bdo-inbox-api.php?action=slip_upload
    → resolveSlipBdoId(line_user_id)
       - 1 open BDO  : auto-attach bdo_id
       - >1 open BDO : return ambiguous_bdos (caller must choose)
    → Odoo /reya/slip/upload
    → update local read model (odoo_slip_uploads) from Odoo response

Sales confirms/unmatches
    → /api/bdo-inbox-api.php?action=slip_match_bdo|slip_unmatch
    → Odoo-first mutation (no local-only fallback)
    → update local cache only after Odoo success

Odoo webhook bdo.done / bdo.cancelled
    → BdoContextManager::closeContext()
    → mark context done/cancel to prevent stale auto-attach
```

### 5. Customer Dashboard Data Path (Performance Mode)

```
Frontend dashboard
    → /api/odoo-dashboard-api.php (TTL cache per action)
        → OdooCustomerDashboardService
            → OdooAPIPool (parallel /reya/user/profile, /reya/credit-status, /reya/orders, /reya/invoices)
            → fallback to webhook/local projection tables when live API is empty/error
        → OdooCircuitBreaker status/reset actions for operations team

Optional local-first mode
    → /api/odoo-dashboard-local.php
    → reads only from denormalized cache tables (no external API call)
```

---

## Odoo/BDO Operations Runbook

### Required Migrations

Run once per environment before enabling the new flow:

```bash
php install/migration_bdo_context_v2.php
php install/migration_slip_verification.php
mysql -u <user> -p <db_name> < database/migration_odoo_api_performance.sql
```

> Note: `cron/sync_odoo_dashboard_cache.php` and `verify_odoo_local_cache.php` reference a `database/migration_odoo_dashboard_cache.sql` file, but that SQL file is not present in this repository snapshot. Provision cache tables from your environment-specific DB bootstrap before enabling local dashboard mode.

### Required Cron

```bash
*/5 * * * * php /path/to/project/cron/sync_odoo_dashboard_cache.php incremental
```

### Health and Verification

```bash
# BDO inbox facade
curl -s "https://<host>/api/bdo-inbox-api.php?action=health&secret=<internal_secret>"

# Dashboard API + circuit breaker
curl -s -X POST "https://<host>/api/odoo-dashboard-api.php" \
  -H "Content-Type: application/json" \
  -d '{"action":"circuit_breaker_status"}'

# Local cache verification
php verify_odoo_local_cache.php
```

### Common Pitfalls

- Use `slip_inbox_id` (Odoo ID) for match/unmatch operations; do not use local `odoo_slip_uploads.id`.
- If a customer has multiple open BDO contexts, callers must pass `bdo_id` explicitly on upload.
- `slip_unmatch` is blocked when slip status is `posted` or `done`.
- Statement PDF URL may return local cached file first, then Odoo direct fallback when local file is unavailable.

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
