# Queue Worker Setup Guide

## ✅ Current Configuration

Your queue worker setup is now **production-ready** with Supervisor!

---

## 📋 What's Configured

### **1. Supervisor Configuration**

**Location:** `supervisor/laravel-worker.conf`

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/queue.log
stopwaitsecs=3600
```

**What this means:**
- ✅ **2 queue workers** running in parallel (`numprocs=2`)
- ✅ **Auto-restart** if worker crashes
- ✅ **Auto-start** when container starts
- ✅ **3 retry attempts** for failed jobs
- ✅ **Max 1 hour** per worker before restart (prevents memory leaks)
- ✅ **Logs** saved to `storage/logs/queue.log`

---

### **2. Dockerfile**

Already includes:
```dockerfile
# Install supervisor
supervisor \

# Copy supervisor configs
COPY supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY supervisor/php-fpm.conf /etc/supervisor/conf.d/php-fpm.conf
COPY supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf

# Start supervisor (manages PHP-FPM + Queue Workers)
CMD ["/bin/sh", "-c", "cron && /usr/bin/supervisord -c /etc/supervisor/supervisord.conf"]
```

✅ Supervisor will automatically manage:
- PHP-FPM (for web requests)
- Queue workers (for background jobs)
- Cron jobs (for scheduled tasks)

---

### **3. Queue Driver**

**Current:** `QUEUE_CONNECTION=database` (in `.env`)

**Good for:**
- ✅ Simple setup (no extra services needed)
- ✅ Works immediately
- ✅ Good for low-to-medium traffic

**Optional upgrade to Redis:**
- Uncomment Redis service in `docker-compose.yaml`
- Change `.env`: `QUEUE_CONNECTION=redis`
- Better performance for high traffic

---

## 🚀 How to Use

### **Local Development**

**Option 1: Run manually (simple)**
```bash
php artisan queue:work
```

**Option 2: Use Docker (recommended)**
```bash
docker-compose up -d
# Workers start automatically via Supervisor
```

---

### **Production Deployment**

**1. Build and start containers:**
```bash
docker-compose up -d --build
```

**2. Verify queue workers are running:**
```bash
docker exec ruang_app_backend supervisorctl status
```

**Expected output:**
```
laravel-worker:laravel-worker_00   RUNNING   pid 123, uptime 0:01:23
laravel-worker:laravel-worker_01   RUNNING   pid 124, uptime 0:01:23
php-fpm                            RUNNING   pid 125, uptime 0:01:23
```

---

## 🔧 Common Commands

### **Check Worker Status**
```bash
docker exec ruang_app_backend supervisorctl status
```

### **View Queue Logs**
```bash
docker exec ruang_app_backend tail -f /var/www/storage/logs/queue.log
```

Or from host (since it's mounted):
```bash
tail -f storage/logs/queue.log
```

### **Restart Queue Workers**
```bash
docker exec ruang_app_backend supervisorctl restart laravel-worker:*
```

### **Stop Queue Workers**
```bash
docker exec ruang_app_backend supervisorctl stop laravel-worker:*
```

### **Start Queue Workers**
```bash
docker exec ruang_app_backend supervisorctl start laravel-worker:*
```

### **Check All Supervisor Processes**
```bash
docker exec ruang_app_backend supervisorctl status
```

### **Reload Supervisor Config**
```bash
docker exec ruang_app_backend supervisorctl reread
docker exec ruang_app_backend supervisorctl update
```

---

## 📊 Monitoring Queue Jobs

### **Check pending jobs:**
```bash
docker exec ruang_app_backend php artisan queue:monitor
```

### **Check failed jobs:**
```bash
docker exec ruang_app_backend php artisan queue:failed
```

### **Retry failed jobs:**
```bash
docker exec ruang_app_backend php artisan queue:retry all
```

### **Clear failed jobs:**
```bash
docker exec ruang_app_backend php artisan queue:flush
```

---

## 🔄 After Code Changes

**Important:** After deploying new code that changes queue job logic:

```bash
# Restart queue workers to load new code
docker exec ruang_app_backend supervisorctl restart laravel-worker:*
```

Or restart the entire container:
```bash
docker-compose restart app
```

---

## 🎯 Scaling Queue Workers

### **Increase number of workers:**

Edit `supervisor/laravel-worker.conf`:
```ini
numprocs=4  # Change from 2 to 4 workers
```

Then rebuild:
```bash
docker-compose up -d --build
```

### **Separate workers for different queues:**

Create `supervisor/high-priority-worker.conf`:
```ini
[program:high-priority-worker]
command=php /var/www/artisan queue:work --queue=high,default --sleep=3 --tries=3
numprocs=1
# ... other config same as laravel-worker.conf
```

---

## 📈 Performance Tips

### **For High Traffic:**

1. **Use Redis instead of database:**
   - Uncomment Redis in `docker-compose.yaml`
   - Change `.env`: `QUEUE_CONNECTION=redis`
   - Install Redis PHP extension in Dockerfile

2. **Increase workers:**
   - Set `numprocs=4` or higher
   - Monitor CPU usage to find optimal number

3. **Use separate queues:**
   ```php
   // High priority
   SendDonationNotification::dispatch($donation)->onQueue('high');
   
   // Low priority
   CleanupOldData::dispatch()->onQueue('low');
   ```

---

## 🐛 Troubleshooting

### **Workers not running:**
```bash
docker exec ruang_app_backend supervisorctl status
# If not running, check logs:
docker exec ruang_app_backend cat /var/www/storage/logs/queue.log
```

### **Jobs stuck in pending:**
```bash
# Check if workers are processing
docker exec ruang_app_backend supervisorctl status

# Check queue table
docker exec ruang_app_backend php artisan queue:monitor
```

### **Memory issues:**
```bash
# Reduce max-time to restart workers more frequently
# Edit supervisor/laravel-worker.conf:
command=php /var/www/artisan queue:work --max-time=1800  # 30 minutes
```

### **Permission errors:**
```bash
# Fix storage permissions
docker exec ruang_app_backend chown -R www-data:www-data /var/www/storage
```

---

## ✅ Verification Checklist

After deployment, verify:

- [ ] `docker exec ruang_app_backend supervisorctl status` shows workers RUNNING
- [ ] Create a test job and verify it processes
- [ ] Check logs: `tail -f storage/logs/queue.log`
- [ ] Failed jobs table is empty: `php artisan queue:failed`
- [ ] Workers restart after code changes

---

## 🎯 Summary

Your setup is **production-ready**:

✅ **2 queue workers** running automatically  
✅ **Auto-restart** on crash  
✅ **Supervisor** manages everything  
✅ **Logging** enabled  
✅ **Docker-ready** for deployment  

**No manual intervention needed** - just `docker-compose up -d` and workers start automatically! 🚀

---

**Last Updated:** November 14, 2025  
**Project:** Ruang Platform
