# ============================================================
# CNY Sync System - Production Deployment Examples
# ============================================================

# ------------------------------------------------------------
# 1. CRON JOB EXAMPLES (Linux)
# ------------------------------------------------------------

# Edit crontab: crontab -e
# แล้วเพิ่มบรรทัดนี้:

# รัน worker ทุก 5 นาที (ทำ 50 jobs แล้วหยุด)
*/5 * * * * cd /var/www/html/cny-sync-system && /usr/bin/php public/sync_worker.php --mode=batch --batch-size=50 >> /var/log/cny-sync.log 2>&1

# รัน worker ทุก 10 นาที (ทำจนกว่า queue จะหมด หรือ 200 jobs)
*/10 * * * * cd /var/www/html/cny-sync-system && /usr/bin/php public/sync_worker.php --max-jobs=200 >> /var/log/cny-sync.log 2>&1

# Cleanup stuck jobs ทุกชั่วโมง
0 * * * * cd /var/www/html/cny-sync-system && /usr/bin/php -r "require 'vendor/autoload.php'; (new CnySync\Queue\SyncQueue(Database::getInstance()->getConnection()))->cleanupStuckJobs(30);" >> /var/log/cny-cleanup.log 2>&1

# สร้าง batch ใหม่ทุกวันเวลา 2:00 น.
0 2 * * * curl -X POST http://localhost/cny-sync-system/public/sync_api.php?action=create_batch -H "Content-Type: application/json" -d '{"name":"Daily Sync","source":"api","priority":5}' >> /var/log/cny-batch.log 2>&1


# ------------------------------------------------------------
# 2. SYSTEMD SERVICE (รัน worker แบบ daemon)
# ------------------------------------------------------------

# สร้างไฟล์: /etc/systemd/system/cny-sync-worker.service

[Unit]
Description=CNY Pharmacy Sync Worker
After=network.target mysql.service
Requires=mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/html/cny-sync-system
ExecStart=/usr/bin/php /var/www/html/cny-sync-system/public/sync_worker.php --mode=continuous --batch-size=10
Restart=always
RestartSec=10
StandardOutput=append:/var/log/cny-sync-worker.log
StandardError=append:/var/log/cny-sync-worker-error.log

# Limits
LimitNOFILE=65536
MemoryLimit=512M
CPUQuota=80%

[Install]
WantedBy=multi-user.target

# จากนั้นรันคำสั่ง:
# sudo systemctl daemon-reload
# sudo systemctl enable cny-sync-worker.service
# sudo systemctl start cny-sync-worker.service
# sudo systemctl status cny-sync-worker.service


# ------------------------------------------------------------
# 3. SUPERVISOR CONFIG (Alternative to systemd)
# ------------------------------------------------------------

# สร้างไฟล์: /etc/supervisor/conf.d/cny-sync-worker.conf

[program:cny-sync-worker]
command=/usr/bin/php /var/www/html/cny-sync-system/public/sync_worker.php --mode=continuous --batch-size=10
directory=/var/www/html/cny-sync-system
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/cny-sync-worker.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stopwaitsecs=30
stopsignal=TERM

# จากนั้นรันคำสั่ง:
# sudo supervisorctl reread
# sudo supervisorctl update
# sudo supervisorctl start cny-sync-worker


# ------------------------------------------------------------
# 4. LOG ROTATION (ป้องกัน log ไฟล์โต)
# ------------------------------------------------------------

# สร้างไฟล์: /etc/logrotate.d/cny-sync

/var/log/cny-sync*.log {
    daily
    rotate 7
    compress
    delaycompress
    notifempty
    missingok
    create 0640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload cny-sync-worker >/dev/null 2>&1 || true
    endscript
}


# ------------------------------------------------------------
# 5. NGINX REVERSE PROXY (สำหรับ dashboard)
# ------------------------------------------------------------

# เพิ่มใน nginx config:

location /sync-dashboard {
    alias /var/www/html/cny-sync-system/public;
    index sync_dashboard.php;
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
    }
    
    # Basic auth (ป้องกันการเข้าถึง)
    auth_basic "CNY Sync Dashboard";
    auth_basic_user_file /etc/nginx/.htpasswd;
}


# ------------------------------------------------------------
# 6. APACHE .htaccess (สำหรับ dashboard)
# ------------------------------------------------------------

# สร้างไฟล์: /var/www/html/cny-sync-system/public/.htaccess

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /cny-sync-system/public/
    
    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>

# Basic Auth
AuthType Basic
AuthName "CNY Sync Dashboard"
AuthUserFile /var/www/.htpasswd
Require valid-user

# สร้าง .htpasswd:
# htpasswd -c /var/www/.htpasswd admin


# ------------------------------------------------------------
# 7. MONITORING SCRIPT (ตรวจสอบ worker ทำงานหรือไม่)
# ------------------------------------------------------------

# สร้างไฟล์: /usr/local/bin/check-cny-worker.sh

#!/bin/bash

WORKER_PID=$(pgrep -f "sync_worker.php")

if [ -z "$WORKER_PID" ]; then
    echo "Worker is not running! Sending alert..."
    
    # Send email (ถ้าติดตั้ง mail)
    echo "CNY Sync Worker has stopped!" | mail -s "Alert: Worker Down" admin@example.com
    
    # หรือส่ง LINE Notify
    curl -X POST https://notify-api.line.me/api/notify \
        -H "Authorization: Bearer YOUR_LINE_TOKEN" \
        -F "message=⚠️ CNY Sync Worker has stopped!"
    
    # Restart worker
    systemctl restart cny-sync-worker
else
    echo "Worker is running (PID: $WORKER_PID)"
fi

# เพิ่มใน crontab เพื่อตรวจสอบทุก 5 นาที:
# */5 * * * * /usr/local/bin/check-cny-worker.sh >> /var/log/worker-check.log 2>&1


# ------------------------------------------------------------
# 8. ENVIRONMENT VARIABLES (Production)
# ------------------------------------------------------------

# สร้างไฟล์: /var/www/html/cny-sync-system/.env

DB_HOST=localhost
DB_NAME=your_database
DB_USER=your_user
DB_PASS=your_password

CNY_API_URL=https://manager.cnypharmacy.com/api
CNY_API_TOKEN=your_api_token

SYNC_BATCH_SIZE=10
SYNC_MAX_REQUESTS=20
SYNC_ENABLE_RATE_LIMIT=true

LOG_LEVEL=info
LOG_PATH=/var/log/cny-sync


# ------------------------------------------------------------
# 9. PERFORMANCE TUNING (MySQL)
# ------------------------------------------------------------

# เพิ่มใน /etc/mysql/my.cnf หรือ /etc/my.cnf:

[mysqld]
# Query Cache (สำหรับ MySQL 5.7)
query_cache_type = 1
query_cache_size = 64M

# InnoDB Settings
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2

# Connection Pool
max_connections = 200
max_connect_errors = 1000000

# Slow Query Log (debug)
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 1


# ------------------------------------------------------------
# 10. BACKUP SCRIPT (สำรอง database)
# ------------------------------------------------------------

# สร้างไฟล์: /usr/local/bin/backup-cny-sync.sh

#!/bin/bash

BACKUP_DIR="/backup/cny-sync"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="your_database"

mkdir -p $BACKUP_DIR

# Backup sync tables only
mysqldump -u root -p \
    --tables sync_queue sync_batches sync_logs sync_config \
    $DB_NAME | gzip > $BACKUP_DIR/cny-sync-$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "cny-sync-*.sql.gz" -mtime +30 -delete

echo "Backup completed: cny-sync-$DATE.sql.gz"

# เพิ่มใน crontab รันทุกวันเวลา 3:00 น.:
# 0 3 * * * /usr/local/bin/backup-cny-sync.sh >> /var/log/backup.log 2>&1
