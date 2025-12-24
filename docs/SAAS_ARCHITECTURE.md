# LINE CRM SaaS Architecture Document

## 1. ภาพรวม (Overview)

เอกสารนี้อธิบายสถาปัตยกรรมสำหรับการแปลง LINE CRM จากระบบ Single-Tenant เป็น Multi-Tenant SaaS Platform

### 1.1 เป้าหมาย
- รองรับลูกค้าหลายรายบน Platform เดียว
- แยก Data และ Configuration ของแต่ละ Tenant อย่างปลอดภัย
- ระบบ Billing และ Subscription Management
- Scalable Infrastructure รองรับการเติบโต

### 1.2 Tenant Model
```
┌─────────────────────────────────────────────────────────────┐
│                    SaaS Platform                             │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │  Tenant A   │  │  Tenant B   │  │  Tenant C   │  ...    │
│  │ (ร้านยา A)  │  │ (คลินิก B)  │  │ (ร้านค้า C) │         │
│  └─────────────┘  └─────────────┘  └─────────────┘         │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. System Architecture

### 2.1 High-Level Architecture Diagram
```
                                    ┌──────────────────┐
                                    │   LINE Platform  │
                                    │   (Messaging)    │
                                    └────────┬─────────┘
                                             │
                    ┌────────────────────────┼────────────────────────┐
                    │                        ▼                        │
                    │              ┌──────────────────┐               │
                    │              │   Load Balancer  │               │
                    │              │    (Nginx/AWS)   │               │
                    │              └────────┬─────────┘               │
                    │                       │                         │
                    │         ┌─────────────┼─────────────┐          │
                    │         ▼             ▼             ▼          │
                    │    ┌─────────┐   ┌─────────┐   ┌─────────┐    │
                    │    │ App 1   │   │ App 2   │   │ App N   │    │
                    │    │(PHP/FPM)│   │(PHP/FPM)│   │(PHP/FPM)│    │
                    │    └────┬────┘   └────┬────┘   └────┬────┘    │
                    │         │             │             │          │
                    │         └─────────────┼─────────────┘          │
                    │                       ▼                        │
                    │              ┌──────────────────┐              │
                    │              │  Tenant Router   │              │
                    │              │   (Middleware)   │              │
                    │              └────────┬─────────┘              │
                    │                       │                        │
                    │         ┌─────────┴─────────┐              │
                    │         ▼                   ▼              │
                    │    ┌─────────┐         ┌─────────┐        │
                    │    │  Redis  │         │  MySQL  │        │
                    │    │ (Cache) │         │(Primary)│        │
                    │    └─────────┘         └─────────┘        │
                    │                                            │
                    │              SaaS Infrastructure           │
                    └────────────────────────────────────────────┘
```

### 2.2 Tenant Resolution Flow
```
Request → DNS → Load Balancer → Tenant Resolver → Application
                                      │
                    ┌─────────────────┼─────────────────┐
                    ▼                 ▼                 ▼
              Subdomain          Custom Domain      API Key
           tenant1.crm.com      crm.tenant.com    X-Tenant-ID
```

---

## 3. Database Architecture

### 3.1 Multi-Tenancy Strategy

เลือกใช้ **Shared Database with Tenant ID** (Pool Model) เหมาะสำหรับ:
- ต้นทุนต่ำ
- ง่ายต่อการ maintain
- เหมาะกับ SME customers

```
┌─────────────────────────────────────────────────────────────┐
│                    Shared Database                          │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────┐   │
│  │ tenants        │ tenant_id │ name │ subdomain │ ... │   │
│  ├─────────────────────────────────────────────────────┤   │
│  │ users          │ tenant_id │ line_user_id │ ...    │   │
│  ├─────────────────────────────────────────────────────┤   │
│  │ messages       │ tenant_id │ user_id │ content │...│   │
│  ├─────────────────────────────────────────────────────┤   │
│  │ products       │ tenant_id │ name │ price │ ...    │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### 3.2 Core SaaS Tables

```sql
-- Tenant Management
CREATE TABLE tenants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    subdomain VARCHAR(100) UNIQUE,
    custom_domain VARCHAR(255),
    logo_url VARCHAR(500),
    primary_color VARCHAR(7) DEFAULT '#007bff',
    plan_id INT NOT NULL,
    status ENUM('trial','active','suspended','cancelled') DEFAULT 'trial',
    trial_ends_at DATETIME,
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_subdomain (subdomain),
    INDEX idx_status (status)
);

-- Subscription Plans
CREATE TABLE plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'THB',
    -- Limits
    max_line_users INT DEFAULT 500,
    max_messages_per_month INT DEFAULT 1000,
    max_broadcasts_per_month INT DEFAULT 10,
    max_bots INT DEFAULT 1,
    max_admin_users INT DEFAULT 2,
    max_products INT DEFAULT 100,
    storage_gb DECIMAL(5,2) DEFAULT 1.00,
    -- Feature Flags
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Subscriptions
CREATE TABLE subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('active','past_due','cancelled','paused') DEFAULT 'active',
    billing_cycle ENUM('monthly','yearly') DEFAULT 'monthly',
    current_period_start DATE NOT NULL,
    current_period_end DATE NOT NULL,
    cancel_at_period_end BOOLEAN DEFAULT FALSE,
    payment_method VARCHAR(50),
    external_subscription_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (plan_id) REFERENCES plans(id)
);
```

```sql
-- Usage Tracking
CREATE TABLE usage_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    metric_type ENUM('messages','broadcasts','api_calls','storage','users') NOT NULL,
    metric_value INT DEFAULT 1,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_metric (tenant_id, metric_type),
    INDEX idx_recorded (recorded_at)
);

-- Monthly Usage Summary
CREATE TABLE usage_summary (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    year_month VARCHAR(7) NOT NULL,  -- '2025-01'
    messages_sent INT DEFAULT 0,
    messages_received INT DEFAULT 0,
    broadcasts_sent INT DEFAULT 0,
    api_calls INT DEFAULT 0,
    storage_used_mb DECIMAL(10,2) DEFAULT 0,
    active_users INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_month (tenant_id, year_month)
);

-- Invoices
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    subscription_id INT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'THB',
    status ENUM('draft','pending','paid','failed','refunded') DEFAULT 'pending',
    due_date DATE,
    paid_at DATETIME,
    payment_method VARCHAR(50),
    external_invoice_id VARCHAR(255),
    line_items JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

---

## 4. Directory Structure

```
LINECRM-SAAS/
│
├── core/                              # SaaS Core Framework
│   ├── Tenant/
│   │   ├── TenantManager.php          # CRUD operations for tenants
│   │   ├── TenantResolver.php         # Resolve tenant from request
│   │   ├── TenantContext.php          # Current tenant context (singleton)
│   │   └── TenantMiddleware.php       # Middleware for tenant isolation
│   │
│   ├── Billing/
│   │   ├── SubscriptionManager.php    # Manage subscriptions
│   │   ├── PlanManager.php            # Plan CRUD and feature checks
│   │   ├── UsageTracker.php           # Track and limit usage
│   │   ├── InvoiceGenerator.php       # Generate invoices
│   │   └── Gateways/
│   │       ├── StripeGateway.php      # Stripe integration
│   │       └── OmiseGateway.php       # Omise (Thai payment)
│   │
│   ├── Auth/
│   │   ├── TenantAuth.php             # Multi-tenant authentication
│   │   ├── RoleManager.php            # Roles per tenant
│   │   └── PermissionManager.php      # Permissions per role
│   │
│   ├── Feature/
│   │   ├── FeatureManager.php         # Feature flag management
│   │   └── FeatureGate.php            # Check feature access
│   │
│   └── Database/
│       ├── TenantScope.php            # Auto-apply tenant_id to queries
│       └── TenantConnection.php       # Dynamic DB connections
│
├── platform/                          # Platform Admin (Super Admin)
│   ├── dashboard.php                  # Platform overview
│   ├── tenants/
│   │   ├── index.php                  # List all tenants
│   │   ├── create.php                 # Create new tenant
│   │   ├── edit.php                   # Edit tenant
│   │   └── impersonate.php            # Login as tenant
│   ├── plans/
│   │   ├── index.php                  # Manage plans
│   │   └── edit.php                   # Edit plan features
│   ├── billing/
│   │   ├── subscriptions.php          # All subscriptions
│   │   └── invoices.php               # All invoices
│   └── analytics/
│       ├── revenue.php                # MRR, ARR, Churn
│       └── usage.php                  # Platform usage stats
│
├── app/                               # Tenant Application (existing code)
│   ├── dashboard.php
│   ├── users.php
│   ├── chat.php
│   ├── broadcast.php
│   ├── shop/
│   ├── modules/
│   └── ... (existing features)
│
├── public/                            # Public Entry Points
│   ├── index.php                      # Main entry (tenant detection)
│   ├── webhook.php                    # LINE webhook handler
│   └── api/                           # API endpoints
│
├── config/
│   ├── app.php                        # Application config
│   ├── database.php                   # Database config
│   ├── tenancy.php                    # Multi-tenancy config
│   ├── plans.php                      # Default plans
│   └── features.php                   # Feature definitions
│
└── database/
    ├── migrations/
    │   ├── 001_create_tenants.sql
    │   ├── 002_create_plans.sql
    │   ├── 003_create_subscriptions.sql
    │   └── 004_add_tenant_id_columns.sql
    └── seeds/
        └── default_plans.sql
```

---

## 5. Core Components

### 5.1 Tenant Resolver

```php
<?php
// core/Tenant/TenantResolver.php

class TenantResolver
{
    public function resolve(): ?Tenant
    {
        // 1. Try subdomain
        $tenant = $this->resolveFromSubdomain();
        if ($tenant) return $tenant;
        
        // 2. Try custom domain
        $tenant = $this->resolveFromCustomDomain();
        if ($tenant) return $tenant;
        
        // 3. Try API header
        $tenant = $this->resolveFromHeader();
        if ($tenant) return $tenant;
        
        // 4. Try webhook path
        $tenant = $this->resolveFromWebhookPath();
        if ($tenant) return $tenant;
        
        return null;
    }
    
    private function resolveFromSubdomain(): ?Tenant
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        // tenant1.linecrm.com → tenant1
        if (preg_match('/^([a-z0-9-]+)\.linecrm\.com$/', $host, $matches)) {
            return Tenant::findBySubdomain($matches[1]);
        }
        return null;
    }
    
    private function resolveFromCustomDomain(): ?Tenant
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return Tenant::findByCustomDomain($host);
    }
    
    private function resolveFromWebhookPath(): ?Tenant
    {
        $path = $_SERVER['REQUEST_URI'] ?? '';
        // /webhook/abc123-uuid → tenant uuid
        if (preg_match('/^\/webhook\/([a-f0-9-]{36})/', $path, $matches)) {
            return Tenant::findByUuid($matches[1]);
        }
        return null;
    }
}
```

### 5.2 Tenant Context (Singleton)

```php
<?php
// core/Tenant/TenantContext.php

class TenantContext
{
    private static ?Tenant $current = null;
    
    public static function set(Tenant $tenant): void
    {
        self::$current = $tenant;
    }
    
    public static function get(): ?Tenant
    {
        return self::$current;
    }
    
    public static function id(): ?int
    {
        return self::$current?->id;
    }
    
    public static function require(): Tenant
    {
        if (!self::$current) {
            throw new TenantNotFoundException('No tenant in context');
        }
        return self::$current;
    }
}
```

### 5.3 Tenant Scope (Auto-filter queries)

```php
<?php
// core/Database/TenantScope.php

trait TenantScope
{
    public static function bootTenantScope()
    {
        // Auto-add tenant_id to INSERT
        static::creating(function ($model) {
            if (!$model->tenant_id) {
                $model->tenant_id = TenantContext::id();
            }
        });
        
        // Auto-filter SELECT queries
        static::addGlobalScope('tenant', function ($query) {
            if ($tenantId = TenantContext::id()) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }
}

// Usage in Model
class User extends Model
{
    use TenantScope;
}
```

### 5.4 Feature Gate

```php
<?php
// core/Feature/FeatureGate.php

class FeatureGate
{
    public static function allows(string $feature): bool
    {
        $tenant = TenantContext::get();
        if (!$tenant) return false;
        
        $plan = $tenant->plan;
        $features = json_decode($plan->features, true) ?? [];
        
        return $features[$feature] ?? false;
    }
    
    public static function check(string $feature): void
    {
        if (!self::allows($feature)) {
            throw new FeatureNotAvailableException(
                "Feature '{$feature}' is not available in your plan. Please upgrade."
            );
        }
    }
}

// Usage
if (FeatureGate::allows('ai_chat')) {
    // Show AI Chat feature
}

FeatureGate::check('pharmacy_module'); // Throws if not allowed
```

### 5.5 Usage Tracker

```php
<?php
// core/Billing/UsageTracker.php

class UsageTracker
{
    public static function track(string $metric, int $value = 1): void
    {
        $tenantId = TenantContext::id();
        if (!$tenantId) return;
        
        DB::table('usage_logs')->insert([
            'tenant_id' => $tenantId,
            'metric_type' => $metric,
            'metric_value' => $value,
            'recorded_at' => now()
        ]);
        
        // Update monthly summary
        self::updateMonthlySummary($tenantId, $metric, $value);
    }
    
    public static function checkLimit(string $metric): bool
    {
        $tenant = TenantContext::require();
        $plan = $tenant->plan;
        $usage = self::getCurrentUsage($tenant->id, $metric);
        
        $limits = [
            'messages' => $plan->max_messages_per_month,
            'broadcasts' => $plan->max_broadcasts_per_month,
            'users' => $plan->max_line_users,
        ];
        
        $limit = $limits[$metric] ?? PHP_INT_MAX;
        return $usage < $limit;
    }
    
    public static function enforceLimit(string $metric): void
    {
        if (!self::checkLimit($metric)) {
            throw new UsageLimitExceededException(
                "You have reached your {$metric} limit. Please upgrade your plan."
            );
        }
    }
}
```

---

## 6. Subscription Plans

### 6.1 Plan Structure

| Feature | Free | Starter | Pro | Business | Enterprise |
|---------|------|---------|-----|----------|------------|
| **Price/month** | ฿0 | ฿990 | ฿2,490 | ฿5,990 | Custom |
| **LINE Users** | 300 | 2,000 | 10,000 | 50,000 | Unlimited |
| **Messages/month** | 500 | 5,000 | 30,000 | 150,000 | Unlimited |
| **Broadcasts/month** | 3 | 20 | 100 | 500 | Unlimited |
| **LINE Bots** | 1 | 2 | 5 | 15 | Unlimited |
| **Admin Users** | 1 | 3 | 10 | 30 | Unlimited |
| **Products** | 50 | 500 | 2,000 | 10,000 | Unlimited |
| **Storage** | 500MB | 5GB | 20GB | 100GB | Unlimited |
| **CRM Basic** | ✓ | ✓ | ✓ | ✓ | ✓ |
| **Auto Reply** | ✓ | ✓ | ✓ | ✓ | ✓ |
| **Rich Menu** | - | ✓ | ✓ | ✓ | ✓ |
| **Broadcast** | - | ✓ | ✓ | ✓ | ✓ |
| **E-commerce** | - | - | ✓ | ✓ | ✓ |
| **AI Chatbot** | - | - | ✓ | ✓ | ✓ |
| **Loyalty Points** | - | - | ✓ | ✓ | ✓ |
| **Pharmacy Module** | - | - | - | ✓ | ✓ |
| **Video Call** | - | - | - | ✓ | ✓ |
| **Analytics Pro** | - | - | - | ✓ | ✓ |
| **API Access** | - | - | - | ✓ | ✓ |
| **White Label** | - | - | - | - | ✓ |
| **Custom Domain** | - | - | - | - | ✓ |
| **Priority Support** | - | - | - | ✓ | ✓ |
| **Dedicated Support** | - | - | - | - | ✓ |

### 6.2 Feature Flags JSON

```json
{
  "free": {
    "crm_basic": true,
    "auto_reply": true,
    "rich_menu": false,
    "broadcast": false,
    "ecommerce": false,
    "ai_chatbot": false,
    "loyalty_points": false,
    "pharmacy_module": false,
    "video_call": false,
    "analytics_pro": false,
    "api_access": false,
    "white_label": false,
    "custom_domain": false
  },
  "pro": {
    "crm_basic": true,
    "auto_reply": true,
    "rich_menu": true,
    "broadcast": true,
    "ecommerce": true,
    "ai_chatbot": true,
    "loyalty_points": true,
    "pharmacy_module": false,
    "video_call": false,
    "analytics_pro": false,
    "api_access": false,
    "white_label": false,
    "custom_domain": false
  },
  "business": {
    "crm_basic": true,
    "auto_reply": true,
    "rich_menu": true,
    "broadcast": true,
    "ecommerce": true,
    "ai_chatbot": true,
    "loyalty_points": true,
    "pharmacy_module": true,
    "video_call": true,
    "analytics_pro": true,
    "api_access": true,
    "white_label": false,
    "custom_domain": false
  }
}
```

---

## 7. Webhook Architecture

### 7.1 Webhook URL Pattern

แต่ละ Tenant จะมี Webhook URL เฉพาะ:

```
https://api.linecrm.com/webhook/{tenant_uuid}
```

ตัวอย่าง:
- Tenant A: `https://api.linecrm.com/webhook/a1b2c3d4-e5f6-7890-abcd-ef1234567890`
- Tenant B: `https://api.linecrm.com/webhook/b2c3d4e5-f6a7-8901-bcde-f12345678901`

### 7.2 Webhook Handler

```php
<?php
// public/webhook.php

require_once '../bootstrap.php';

// Extract tenant UUID from URL
$path = $_SERVER['REQUEST_URI'];
preg_match('/\/webhook\/([a-f0-9-]{36})/', $path, $matches);
$tenantUuid = $matches[1] ?? null;

if (!$tenantUuid) {
    http_response_code(404);
    exit('Tenant not found');
}

// Resolve tenant
$tenant = Tenant::findByUuid($tenantUuid);
if (!$tenant || $tenant->status !== 'active') {
    http_response_code(403);
    exit('Tenant inactive');
}

// Set tenant context
TenantContext::set($tenant);

// Get LINE bot config for this tenant
$lineAccount = LineAccount::where('tenant_id', $tenant->id)
    ->where('is_active', true)
    ->first();

if (!$lineAccount) {
    http_response_code(404);
    exit('LINE account not configured');
}

// Verify LINE signature
$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';
$body = file_get_contents('php://input');

if (!verifySignature($body, $signature, $lineAccount->channel_secret)) {
    http_response_code(400);
    exit('Invalid signature');
}

// Process webhook events
$events = json_decode($body, true)['events'] ?? [];
foreach ($events as $event) {
    // Track usage
    UsageTracker::track('messages');
    
    // Check limits
    if (!UsageTracker::checkLimit('messages')) {
        // Send limit warning to admin
        continue;
    }
    
    // Process event (existing logic)
    processWebhookEvent($event, $lineAccount);
}

http_response_code(200);
```

---

## 8. Security Considerations

### 8.1 Data Isolation

```php
// ทุก Query ต้องมี tenant_id
// ❌ ผิด - ไม่มี tenant isolation
$users = DB::query("SELECT * FROM users WHERE status = 'active'");

// ✓ ถูก - มี tenant isolation
$users = DB::query(
    "SELECT * FROM users WHERE tenant_id = ? AND status = 'active'",
    [TenantContext::id()]
);

// ✓ ดีกว่า - ใช้ TenantScope trait
$users = User::where('status', 'active')->get(); // Auto-filtered
```

### 8.2 Authentication Flow

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Browser   │────▶│   Tenant    │────▶│    Auth     │
│             │     │  Resolver   │     │   Check     │
└─────────────┘     └─────────────┘     └──────┬──────┘
                                               │
                    ┌──────────────────────────┘
                    ▼
            ┌───────────────┐
            │ Session/JWT   │
            │ + tenant_id   │
            │ + user_id     │
            │ + role        │
            └───────────────┘
```

### 8.3 API Rate Limiting

```php
// Per-tenant rate limiting
$rateLimiter = new RateLimiter();
$key = "tenant:{$tenantId}:api";

if (!$rateLimiter->attempt($key, $maxAttempts = 1000, $decayMinutes = 1)) {
    http_response_code(429);
    exit(json_encode(['error' => 'Too many requests']));
}
```

---

## 9. Migration Strategy

### 9.1 Migration from Single-Tenant to Multi-Tenant

```
Phase 1: Database Preparation
├── Add tenant_id column to all tables
├── Create SaaS core tables (tenants, plans, subscriptions)
├── Create default tenant for existing data
└── Backfill tenant_id for existing records

Phase 2: Code Refactoring
├── Implement TenantContext singleton
├── Add TenantScope trait to all models
├── Update all raw queries to include tenant_id
├── Implement TenantResolver middleware
└── Update authentication to be tenant-aware

Phase 3: Billing Integration
├── Set up payment gateway (Stripe/Omise)
├── Implement subscription management
├── Create billing portal
└── Set up usage tracking

Phase 4: Platform Admin
├── Create super admin dashboard
├── Implement tenant management
├── Add platform analytics
└── Set up monitoring

Phase 5: Onboarding Flow
├── Create tenant registration
├── Implement LINE bot setup wizard
├── Create plan selection UI
└── Set up trial management
```

### 9.2 Database Migration Script

```sql
-- Step 1: Create tenants table
CREATE TABLE tenants (...);

-- Step 2: Create default tenant
INSERT INTO tenants (uuid, name, subdomain, plan_id, status)
VALUES (UUID(), 'Default Tenant', 'default', 1, 'active');

SET @default_tenant_id = LAST_INSERT_ID();

-- Step 3: Add tenant_id to existing tables
ALTER TABLE users ADD COLUMN tenant_id INT AFTER id;
ALTER TABLE messages ADD COLUMN tenant_id INT AFTER id;
ALTER TABLE products ADD COLUMN tenant_id INT AFTER id;
-- ... repeat for all tables

-- Step 4: Backfill tenant_id
UPDATE users SET tenant_id = @default_tenant_id WHERE tenant_id IS NULL;
UPDATE messages SET tenant_id = @default_tenant_id WHERE tenant_id IS NULL;
UPDATE products SET tenant_id = @default_tenant_id WHERE tenant_id IS NULL;
-- ... repeat for all tables

-- Step 5: Add NOT NULL constraint and indexes
ALTER TABLE users MODIFY tenant_id INT NOT NULL;
ALTER TABLE users ADD INDEX idx_tenant (tenant_id);
-- ... repeat for all tables
```

---

## 10. Deployment Architecture

### 10.1 Infrastructure Diagram

```
                        ┌─────────────────────────────────────┐
                        │           CloudFlare CDN            │
                        │      (SSL, DDoS, Caching)           │
                        └──────────────┬──────────────────────┘
                                       │
                        ┌──────────────▼──────────────────────┐
                        │         Load Balancer               │
                        │     (AWS ALB / Nginx)               │
                        └──────────────┬──────────────────────┘
                                       │
              ┌────────────────────────┼────────────────────────┐
              │                        │                        │
    ┌─────────▼─────────┐   ┌─────────▼─────────┐   ┌─────────▼─────────┐
    │    App Server 1   │   │    App Server 2   │   │    App Server N   │
    │   (PHP-FPM)       │   │   (PHP-FPM)       │   │   (PHP-FPM)       │
    └─────────┬─────────┘   └─────────┬─────────┘   └─────────┬─────────┘
              │                        │                        │
              └────────────────────────┼────────────────────────┘
                                       │
              ┌────────────────────────┼────────────────────────┐
              │                        │                        │
    ┌─────────▼─────────┐   ┌─────────▼─────────┐   ┌─────────▼─────────┐
    │   MySQL Primary   │   │   Redis Cluster   │   │   File Storage    │
    │   + Replicas      │   │   (Sessions/Cache)│   │   (S3/MinIO)      │
    └───────────────────┘   └───────────────────┘   └───────────────────┘
```

### 10.2 Scaling Strategy

| Component | Scaling Method | Trigger |
|-----------|---------------|---------|
| App Servers | Horizontal (Auto-scaling) | CPU > 70% |
| Database | Read Replicas | Query load |
| Redis | Cluster mode | Memory usage |
| Storage | S3/CDN | Automatic |
| Cron Jobs | Queue workers | Job backlog |

---

## 11. Monitoring & Analytics

### 11.1 Platform Metrics (Super Admin)

```php
// Platform-wide metrics
class PlatformAnalytics
{
    public function getDashboardMetrics(): array
    {
        return [
            'mrr' => $this->calculateMRR(),           // Monthly Recurring Revenue
            'arr' => $this->calculateARR(),           // Annual Recurring Revenue
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::active()->count(),
            'trial_tenants' => Tenant::trial()->count(),
            'churn_rate' => $this->calculateChurnRate(),
            'total_users' => User::count(),           // All LINE users
            'messages_today' => UsageLog::today()->sum('metric_value'),
            'revenue_this_month' => Invoice::thisMonth()->paid()->sum('total_amount'),
        ];
    }
    
    public function calculateMRR(): float
    {
        return Subscription::active()
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price_monthly');
    }
}
```

### 11.2 Tenant Metrics

```php
// Per-tenant metrics
class TenantAnalytics
{
    public function getUsageReport(int $tenantId): array
    {
        $summary = UsageSummary::where('tenant_id', $tenantId)
            ->where('year_month', date('Y-m'))
            ->first();
            
        $plan = Tenant::find($tenantId)->plan;
        
        return [
            'messages' => [
                'used' => $summary->messages_sent ?? 0,
                'limit' => $plan->max_messages_per_month,
                'percentage' => $this->percentage($summary->messages_sent, $plan->max_messages_per_month)
            ],
            'broadcasts' => [
                'used' => $summary->broadcasts_sent ?? 0,
                'limit' => $plan->max_broadcasts_per_month,
                'percentage' => $this->percentage($summary->broadcasts_sent, $plan->max_broadcasts_per_month)
            ],
            'users' => [
                'used' => User::where('tenant_id', $tenantId)->count(),
                'limit' => $plan->max_line_users,
                'percentage' => $this->percentage($userCount, $plan->max_line_users)
            ],
            'storage' => [
                'used_mb' => $summary->storage_used_mb ?? 0,
                'limit_mb' => $plan->storage_gb * 1024,
                'percentage' => $this->percentage($summary->storage_used_mb, $plan->storage_gb * 1024)
            ]
        ];
    }
}
```

---

## 12. Onboarding Flow

### 12.1 New Tenant Registration

```
┌─────────────────────────────────────────────────────────────────┐
│                     Tenant Onboarding Flow                       │
└─────────────────────────────────────────────────────────────────┘

Step 1: Sign Up
├── Email/Password registration
├── Company name
└── Phone number

Step 2: Choose Plan
├── Display plan comparison
├── Select plan (or start trial)
└── Payment info (if paid plan)

Step 3: Create Tenant
├── Generate subdomain (company-name.linecrm.com)
├── Create tenant record
├── Initialize default settings
└── Create admin user

Step 4: LINE Bot Setup
├── Guide to LINE Developers Console
├── Input Channel ID & Secret
├── Set webhook URL
├── Verify connection
└── Create default Rich Menu

Step 5: Welcome
├── Dashboard tour
├── Quick start guide
├── Support resources
└── First broadcast suggestion
```

### 12.2 Tenant Provisioning Code

```php
<?php
// core/Tenant/TenantProvisioner.php

class TenantProvisioner
{
    public function provision(array $data): Tenant
    {
        DB::beginTransaction();
        
        try {
            // 1. Create tenant
            $tenant = Tenant::create([
                'uuid' => Str::uuid(),
                'name' => $data['company_name'],
                'subdomain' => $this->generateSubdomain($data['company_name']),
                'plan_id' => $data['plan_id'],
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
                'settings' => $this->getDefaultSettings()
            ]);
            
            // 2. Create subscription
            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $data['plan_id'],
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth()
            ]);
            
            // 3. Create admin user
            AdminUser::create([
                'tenant_id' => $tenant->id,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'name' => $data['name'],
                'role' => 'owner'
            ]);
            
            // 4. Initialize default data
            $this->initializeDefaults($tenant);
            
            DB::commit();
            
            // 5. Send welcome email
            Mail::to($data['email'])->send(new WelcomeEmail($tenant));
            
            return $tenant;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    private function initializeDefaults(Tenant $tenant): void
    {
        // Create default auto-reply rules
        AutoReply::create([
            'tenant_id' => $tenant->id,
            'keyword' => 'สวัสดี',
            'response' => 'สวัสดีครับ/ค่ะ ยินดีให้บริการ',
            'is_active' => true
        ]);
        
        // Create default user tags
        UserTag::insert([
            ['tenant_id' => $tenant->id, 'name' => 'VIP', 'color' => '#FFD700'],
            ['tenant_id' => $tenant->id, 'name' => 'New', 'color' => '#00FF00'],
            ['tenant_id' => $tenant->id, 'name' => 'Inactive', 'color' => '#808080'],
        ]);
    }
}
```

---

## 13. API Design

### 13.1 API Authentication

```
Authorization: Bearer {api_key}
X-Tenant-ID: {tenant_uuid}  (optional if using tenant-specific API key)
```

### 13.2 API Endpoints

```
Base URL: https://api.linecrm.com/v1

# Users
GET    /users                    List LINE users
GET    /users/{id}               Get user details
PUT    /users/{id}               Update user
POST   /users/{id}/tags          Add tags to user
DELETE /users/{id}/tags/{tag}    Remove tag

# Messages
POST   /messages/send            Send message to user
POST   /messages/broadcast       Send broadcast
GET    /messages/history/{userId} Get chat history

# Products
GET    /products                 List products
POST   /products                 Create product
PUT    /products/{id}            Update product
DELETE /products/{id}            Delete product

# Orders
GET    /orders                   List orders
GET    /orders/{id}              Get order details
PUT    /orders/{id}/status       Update order status

# Analytics
GET    /analytics/overview       Dashboard metrics
GET    /analytics/messages       Message statistics
GET    /analytics/users          User growth

# Webhooks (for tenant's external systems)
POST   /webhooks                 Register webhook
GET    /webhooks                 List webhooks
DELETE /webhooks/{id}            Delete webhook
```

### 13.3 API Response Format

```json
{
  "success": true,
  "data": {
    "users": [...],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 150,
      "total_pages": 8
    }
  },
  "meta": {
    "request_id": "req_abc123",
    "rate_limit": {
      "limit": 1000,
      "remaining": 995,
      "reset_at": "2025-01-01T00:00:00Z"
    }
  }
}
```

---

## 14. Cost Estimation

### 14.1 Infrastructure Costs (AWS)

| Resource | Specification | Monthly Cost |
|----------|--------------|--------------|
| EC2 (App) | 2x t3.medium | ~$60 |
| RDS MySQL | db.t3.medium + replica | ~$100 |
| ElastiCache | cache.t3.micro | ~$15 |
| S3 Storage | 100GB | ~$3 |
| CloudFront | 100GB transfer | ~$10 |
| Route53 | Hosted zone | ~$1 |
| **Total** | | **~$190/month** |

### 14.2 Break-even Analysis

```
Fixed Costs: ~$190/month
Variable Costs: ~$0.01 per 1000 messages (LINE API)

Break-even with 10 Pro customers:
10 × ฿2,490 = ฿24,900/month (~$700)
Profit: $700 - $190 = $510/month

Target: 100 customers mix
- 50 Free (lead gen)
- 30 Starter @ ฿990 = ฿29,700
- 15 Pro @ ฿2,490 = ฿37,350
- 5 Business @ ฿5,990 = ฿29,950
Total MRR: ฿97,000 (~$2,700/month)
```

---

## 15. Next Steps

### Phase 1: Foundation (4-6 weeks)
- [ ] Create SaaS database schema
- [ ] Implement TenantContext and TenantScope
- [ ] Add tenant_id to all existing tables
- [ ] Create TenantResolver middleware
- [ ] Migrate existing data to default tenant

### Phase 2: Billing (2-4 weeks)
- [ ] Integrate Stripe/Omise
- [ ] Implement subscription management
- [ ] Create billing portal
- [ ] Set up usage tracking

### Phase 3: Platform Admin (2-3 weeks)
- [ ] Create super admin dashboard
- [ ] Implement tenant management
- [ ] Add platform analytics

### Phase 4: Onboarding (2-3 weeks)
- [ ] Create registration flow
- [ ] Implement LINE bot setup wizard
- [ ] Create plan selection UI
- [ ] Set up trial management

### Phase 5: Launch Preparation (2 weeks)
- [ ] Security audit
- [ ] Performance testing
- [ ] Documentation
- [ ] Marketing site

---

## Appendix A: Glossary

| Term | Definition |
|------|------------|
| Tenant | ลูกค้าแต่ละรายที่ใช้ระบบ (ร้านค้า, คลินิก, etc.) |
| MRR | Monthly Recurring Revenue - รายได้ประจำต่อเดือน |
| ARR | Annual Recurring Revenue - รายได้ประจำต่อปี |
| Churn | อัตราการยกเลิกบริการ |
| Feature Flag | การเปิด/ปิด feature ตาม plan |
| Tenant Isolation | การแยกข้อมูลระหว่าง tenant |

---

*Document Version: 1.0*
*Last Updated: December 2025*
*Author: LINE CRM Team*
