# 🏛️ CNY Sync System - Architecture Documentation

## 📐 System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         CNY Sync System                         │
│                       Queue-Based Architecture                   │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────────┐
│    External Systems      │
│  ┌──────────────────┐   │
│  │  CNY Pharmacy    │   │
│  │      API         │   │
│  └────────┬─────────┘   │
│           │ HTTPS        │
└───────────┼──────────────┘
            │
            ▼
┌───────────────────────────────────────────────────────────────┐
│                    API Layer                                  │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │  CnyPharmacyAPI.php                                     │ │
│  │  - getProductBySku()                                    │ │
│  │  - getAllProducts()                                     │ │
│  │  - getSkuList()                                         │ │
│  │  - syncProduct()                                        │ │
│  └───────────────────┬─────────────────────────────────────┘ │
└────────────────────────┼───────────────────────────────────────┘
                         │
                         ▼
┌───────────────────────────────────────────────────────────────┐
│                 Rate Limiter                                  │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │  RateLimiter.php                                        │ │
│  │  - Token Bucket Algorithm                               │ │
│  │  - 20 requests/minute limit                             │ │
│  │  - APCu or File-based cache                             │ │
│  └───────────────────┬─────────────────────────────────────┘ │
└────────────────────────┼───────────────────────────────────────┘
                         │
                         ▼
┌───────────────────────────────────────────────────────────────┐
│                  Queue System                                 │
│  ┌──────────────────┐    ┌──────────────────┐               │
│  │  SyncQueue.php   │───▶│  BatchManager    │               │
│  │  - addJob()      │    │  - createBatch() │               │
│  │  - getReadyJobs()│    │  - updateProgress()              │
│  │  - lockJob()     │    │  - completeBatch()               │
│  │  - completeJob() │    │                  │               │
│  └────────┬─────────┘    └──────────────────┘               │
└───────────┼──────────────────────────────────────────────────┘
            │
            ▼
┌───────────────────────────────────────────────────────────────┐
│                 Worker Layer                                  │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │  SyncWorker.php                                         │ │
│  │  - processBatch()  (single batch)                       │ │
│  │  - processAll()    (continuous mode)                    │ │
│  │  - processJob()    (individual job)                     │ │
│  └───────────────────┬─────────────────────────────────────┘ │
└────────────────────────┼───────────────────────────────────────┘
                         │
                         ▼
┌───────────────────────────────────────────────────────────────┐
│                  Database Layer                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │ sync_queue   │  │ sync_batches │  │  sync_logs   │       │
│  ├──────────────┤  ├──────────────┤  ├──────────────┤       │
│  │ id           │  │ id           │  │ id           │       │
│  │ sku          │  │ batch_name   │  │ queue_id     │       │
│  │ status       │  │ total_jobs   │  │ sku          │       │
│  │ priority     │  │ completed    │  │ action       │       │
│  │ attempts     │  │ failed       │  │ duration_ms  │       │
│  │ error_msg    │  │ status       │  │ details      │       │
│  │ api_data     │  │ started_at   │  │ created_at   │       │
│  │ result       │  │ completed_at │  │              │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
│                                                               │
│  ┌──────────────┐  ┌──────────────────────────────────┐     │
│  │ sync_config  │  │  products (existing table)       │     │
│  ├──────────────┤  ├──────────────────────────────────┤     │
│  │ config_key   │  │ id, sku, name, price, etc.       │     │
│  │ config_value │  │                                  │     │
│  └──────────────┘  └──────────────────────────────────┘     │
└───────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌───────────────────────────────────────────────────────────────┐
│                  Presentation Layer                           │
│  ┌──────────────────┐    ┌──────────────────┐               │
│  │ sync_dashboard   │───▶│   sync_api.php   │               │
│  │ (Web UI)         │    │  (REST API)      │               │
│  │ - Real-time stats│    │  - stats         │               │
│  │ - Progress bar   │    │  - create_batch  │               │
│  │ - Create batch   │    │  - clear_queue   │               │
│  │ - View logs      │    │  - recent_logs   │               │
│  └──────────────────┘    └──────────────────┘               │
│                                                               │
│  ┌──────────────────┐                                        │
│  │ sync_worker.php  │                                        │
│  │ (CLI Script)     │                                        │
│  │ - Batch mode     │                                        │
│  │ - Continuous mode│                                        │
│  └──────────────────┘                                        │
└───────────────────────────────────────────────────────────────┘
```

---

## 🔄 Data Flow

### 1. Job Creation Flow

```
User/Cron → sync_api.php → BatchManager → SyncQueue
                                             │
                                             ▼
                                        Database
                                      (sync_queue)
```

### 2. Job Processing Flow

```
Worker → SyncQueue.getReadyJobs()
           │
           ▼
        [Lock Job]
           │
           ▼
     RateLimiter.wait()
           │
           ▼
     CnyPharmacyAPI.getProductBySku()
           │
           ▼
     CnyPharmacyAPI.syncProduct()
           │
           ▼
     SyncQueue.completeJob() / failJob()
           │
           ▼
        sync_logs
```

### 3. Progress Tracking Flow

```
Dashboard → sync_api.php → SyncQueue.getStats()
              │                    │
              ├─────────────────────┤
              │                     │
              ▼                     ▼
         sync_logs          v_queue_summary
              │                     │
              └─────────┬───────────┘
                        ▼
                   JSON Response
```

---

## 🎯 Design Patterns Used

### 1. **Singleton Pattern**
- **Where:** Database connection
- **Why:** Single connection instance ตลอด lifecycle

### 2. **Repository Pattern**
- **Where:** SyncQueue, BatchManager
- **Why:** แยก data access logic ออกจาก business logic

### 3. **Worker Pattern**
- **Where:** SyncWorker
- **Why:** ประมวลผล jobs จาก queue แบบ background

### 4. **Token Bucket Algorithm**
- **Where:** RateLimiter
- **Why:** จำกัด rate ของ API requests

### 5. **Strategy Pattern**
- **Where:** Worker modes (batch vs continuous)
- **Why:** เปลี่ยน algorithm ได้ runtime

---

## 🔒 Security Architecture

### 1. **SQL Injection Prevention**
```php
// ✅ ALWAYS use prepared statements
$stmt = $db->prepare("SELECT * FROM sync_queue WHERE sku = ?");
$stmt->execute([$sku]);

// ❌ NEVER concatenate SQL
$sql = "SELECT * FROM sync_queue WHERE sku = '$sku'"; // DANGEROUS
```

### 2. **Type Safety**
```php
// ✅ Use strict types
declare(strict_types=1);

// ✅ Type hints everywhere
public function addJob(string $sku, int $priority): int

// ✅ Return types
public function getStats(): array
```

### 3. **Input Validation**
```php
// ✅ Validate and sanitize
$batchSize = max(1, min(100, (int)$_GET['batch_size']));

// ✅ Whitelist validation
if (!in_array($action, $allowedActions)) {
    throw new Exception('Invalid action');
}
```

### 4. **Error Handling**
```php
// ✅ Catch and log errors
try {
    $result = $api->sync();
} catch (Exception $e) {
    $this->log($e->getMessage());
    $this->failJob($jobId, $e->getMessage());
}
```

---

## ⚡ Performance Optimizations

### 1. **Database Indexes**
```sql
-- Speed up queue queries
INDEX idx_status (status)
INDEX idx_priority_status (priority, status)
INDEX idx_sku (sku)

-- Speed up log queries
INDEX idx_created_at (created_at)
INDEX idx_action (action)
```

### 2. **Bulk Operations**
```php
// ✅ Bulk insert (100x faster)
INSERT INTO sync_queue (sku, priority) VALUES 
    ('0001', 5), ('0002', 5), ..., ('1000', 5);

// ❌ Individual inserts (slow)
for each sku:
    INSERT INTO sync_queue (sku, priority) VALUES ('0001', 5);
```

### 3. **Caching**
```php
// Cache API response
if (isset($job['api_data'])) {
    $data = json_decode($job['api_data'], true); // Use cache
} else {
    $data = $api->fetch($sku); // Fetch fresh
}
```

### 4. **Memory Management**
```php
// Monitor memory usage
if (memory_get_usage() > $limit * 0.9) {
    $this->stop();
}

// Process in batches (not all at once)
$jobs = $queue->getReadyJobs(10); // Only 10
```

---

## 📊 Monitoring Points

### 1. **Queue Metrics**
- Total jobs
- Pending jobs
- Processing jobs
- Completed jobs
- Failed jobs
- Average processing time

### 2. **Worker Metrics**
- Jobs per second
- Success rate
- Retry count
- Memory usage
- CPU usage

### 3. **API Metrics**
- Request count
- Response time
- Error rate
- Rate limit hits

---

## 🔧 Configuration Points

### Database Level
```sql
-- sync_config table
batch_size = 10
delay_between_jobs = 500
max_concurrent_workers = 1
api_timeout = 30
enable_rate_limiting = 1
max_requests_per_minute = 20
```

### Code Level
```php
// config/sync_config.php
public const BATCH_SIZE = 10;
public const MAX_REQUESTS_PER_MINUTE = 20;
public const WORKER_MEMORY_LIMIT = '256M';
```

### Runtime Level
```bash
# CLI arguments
php sync_worker.php --batch-size=20 --max-jobs=100
```

---

## 🚀 Deployment Architecture

### Development
```
Local Machine
├── MySQL (localhost)
├── PHP 8.1+
└── Manual worker execution
```

### Staging
```
Staging Server
├── MySQL (local or RDS)
├── PHP-FPM
├── Nginx
└── Cron job (every 5 min)
```

### Production
```
Production Server
├── MySQL (RDS or managed)
├── PHP-FPM (multiple workers)
├── Nginx + Load Balancer
├── Systemd Service (daemon worker)
├── Monitoring (New Relic / Datadog)
└── Backup (daily)
```

---

## 📦 Directory Structure Explained

```
cny-sync-system/
│
├── config/                    # Configuration files
│   └── sync_config.php       # System constants & settings
│
├── database/                  # Database schemas
│   └── sync_schema.sql       # Tables, indexes, views
│
├── src/                       # Core business logic
│   ├── SyncQueue.php         # Queue management (Repository)
│   ├── SyncWorker.php        # Job processor (Worker)
│   ├── RateLimiter.php       # API rate limiting (Utility)
│   └── BatchManager.php      # Batch operations (Manager)
│
├── public/                    # Web-accessible files
│   ├── sync_dashboard.php    # Web UI (View)
│   ├── sync_api.php          # REST API (Controller)
│   └── sync_worker.php       # CLI worker script
│
├── migrate_to_queue.php       # Migration script
├── README.md                  # Full documentation
├── QUICKSTART.md              # Quick start guide
├── PRODUCTION.md              # Production setup
└── COMPARISON.md              # Old vs New comparison
```

---

## 🎓 Best Practices Applied

### 1. **Separation of Concerns**
- API layer → Business logic → Data layer
- Each class has single responsibility

### 2. **DRY (Don't Repeat Yourself)**
- Reusable components (RateLimiter, SyncQueue)
- Shared configuration

### 3. **SOLID Principles**
- Single Responsibility
- Open/Closed
- Liskov Substitution
- Interface Segregation
- Dependency Inversion

### 4. **12-Factor App**
- Config in environment
- Stateless processes
- Log to stdout
- Admin processes

---

## 🔮 Scalability Considerations

### Horizontal Scaling
```
Multiple Workers (same queue)
Worker 1 ────┐
Worker 2 ────┼───▶ sync_queue (shared)
Worker 3 ────┘
```

### Vertical Scaling
```
Single Worker (more resources)
- Increase BATCH_SIZE
- Increase MEMORY_LIMIT
- Decrease DELAY_BETWEEN_JOBS
```

### Database Sharding (future)
```
Products by category
Shard 1: Category A-M
Shard 2: Category N-Z
```

---

**เอกสารนี้อธิบาย Architecture แบบละเอียด เหมาะสำหรับ:**
- 👨‍💻 Developers ที่จะมาพัฒนาต่อ
- 🏗️ Architects ที่ต้องการเข้าใจระบบ
- 🔧 DevOps ที่จะ deploy production
- 📚 Documentation purposes
