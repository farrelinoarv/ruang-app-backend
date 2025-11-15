# 🚀 Ruang App Backend - Installation & Deployment Guide

Laravel 11 + Filament 3.2 backend untuk aplikasi donasi kampanye dengan integrasi Midtrans payment gateway.
Live on http://103.150.116.34:8000/

Credentials

email: admin-ruang@secret.com

password: adminruangapp1125

## 📋 Table of Contents

- [Prerequisites](#prerequisites)
- [Installation (Fastest Way)](#installation-fastest-way)
- [Installation (Local Development)](#installation-local-development)
  - [Manual Installation](#manual-installation)
  - [Docker Installation](#docker-installation-recommended)
- [Deployment (Production Server)](#deployment-production-server)
- [Configuration](#configuration)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

---

## Prerequisites

### For Manual Installation:
- PHP 8.2 or higher
- Composer 2.x
- MySQL 5.7+ or MariaDB 10.3+
- Node.js 18+ & NPM (untuk Vite assets)
- Git

### For Docker Installation:
- Docker 20.x or higher
- Docker Compose 2.x or higher

---

## Installation (Fastest Way)
Since this zip already contains all the source code and necessary stuff including .env, you can just build it using Docker with command
```bash
docker compose up -d --build
```
Or if you want to run it using XAMPP, LAMP, Laragon, or such.. just run it with PHP.
```bash
php artisan serve
```
You also have to run 
```bash
php artisan queue:listen
```
or
```bash
php artisan queue:work
```

---

## Installation (Local Development)

### Manual Installation

1. **Clone Repository**
   ```bash
   git clone https://github.com/farrelinoarv/ruang-app-backend.git
   cd ruang-app-backend
   ```

2. **Install Dependencies**
   ```bash
   # Install PHP dependencies
   composer install

   # Install Node dependencies (optional, untuk Filament custom themes)
   npm install
   ```

3. **Environment Setup**
   ```bash
   # Copy environment file
   cp .env.example .env

   # Generate application key
   php artisan key:generate
   ```

4. **Configure Database**
   
   Edit `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ruang_app
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

   Create database:
   ```sql
   CREATE DATABASE ruang_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. **Configure Midtrans (Optional untuk testing)**
   
   Edit `.env`:
   ```env
   MIDTRANS_SERVER_KEY=your_sandbox_server_key
   MIDTRANS_CLIENT_KEY=your_sandbox_client_key
   MIDTRANS_IS_PRODUCTION=false
   MIDTRANS_SNAP_URL=https://app.sandbox.midtrans.com/snap/v1/transactions
   ```

6. **Run Migrations & Seeders**
   ```bash
   # Run migrations
   php artisan migrate

   # Seed initial data (categories, admin user, etc.)
   php artisan db:seed
   ```

7. **Storage Link**
   ```bash
   php artisan storage:link
   ```

8. **Start Development Server**
   ```bash
   # Terminal 1: Laravel server
   php artisan serve

   # Terminal 2: Queue worker (untuk background jobs)
   php artisan queue:work

   # Terminal 3 (Optional): Auto-complete donations untuk testing
   php artisan schedule:work
   # atau langsung jalankan
   php artisan donations:auto-complete
   ```

9. **Access Application**
   - **API:** http://localhost:8000/api
   - **Admin Panel:** http://localhost:8000/admin
   - **Default Admin:**
     - Email: admin@ruang.id
     - Password: password

---

### Docker Installation (Recommended)

Docker setup sudah include: PHP 8.3-FPM, Nginx, MySQL 5.7, Cron, Supervisor untuk queue workers.

1. **Clone Repository**
   ```bash
   git clone https://github.com/farrelinoarv/ruang-app-backend.git
   cd ruang-app-backend
   ```

2. **Environment Setup**
   ```bash
   cp .env.example .env
   ```

3. **Configure Environment untuk Docker**
   
   Edit `.env`:
   ```env
   APP_NAME="Ruang App"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=ruang_app
   DB_USERNAME=root
   DB_PASSWORD=root

   QUEUE_CONNECTION=database

   # Midtrans Configuration (Sandbox)
   MIDTRANS_SERVER_KEY=your_sandbox_server_key
   MIDTRANS_CLIENT_KEY=your_sandbox_client_key
   MIDTRANS_IS_PRODUCTION=false
   MIDTRANS_SNAP_URL=https://app.sandbox.midtrans.com/snap/v1/transactions
   ```

4. **Build & Start Containers**
   ```bash
   docker-compose up -d --build
   ```

   Proses ini akan:
   - Build PHP-FPM image dengan dependencies
   - Install Composer packages
   - Start Nginx web server
   - Start MySQL database
   - Setup Cron untuk scheduler
   - Start Supervisor untuk queue workers

5. **Generate App Key**
   ```bash
   docker exec ruang_app_backend php artisan key:generate
   ```

6. **Run Migrations & Seeders**
   ```bash
   docker exec ruang_app_backend php artisan migrate --seed
   ```

7. **Create Storage Link**
   ```bash
   docker exec ruang_app_backend php artisan storage:link
   ```

8. **Fix Permissions (jika error)**
   ```bash
   docker exec ruang_app_backend chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
   docker exec ruang_app_backend chmod -R 775 /var/www/storage /var/www/bootstrap/cache
   ```

9. **Access Application**
   - **API:** http://localhost:8000/api
   - **Admin Panel:** http://localhost:8000/admin
   - **MySQL:** localhost:3307 (untuk akses dari host machine)

10. **View Logs**
    ```bash
    # Application logs
    docker exec ruang_app_backend tail -f storage/logs/laravel.log

    # Nginx logs
    docker logs ruang_app_nginx -f

    # Container logs
    docker-compose logs -f app
    ```

---

## Deployment (Production Server)

### Prerequisites
- Server dengan Docker & Docker Compose terinstall
- Domain name dengan DNS pointing ke server IP
- SSL certificate (gunakan Certbot/Let's Encrypt)

### Step-by-Step Deployment

1. **SSH ke Server**
   ```bash
   ssh user@your-server-ip
   ```

2. **Clone Repository**
   ```bash
   cd /var/www
   git clone https://github.com/farrelinoarv/ruang-app-backend.git
   cd ruang-app-backend
   ```

3. **Setup Environment**
   ```bash
   cp .env.example .env
   nano .env
   ```

   Production `.env` configuration:
   ```env
   APP_NAME="Ruang App"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com

   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=ruang_app
   DB_USERNAME=root
   DB_PASSWORD=strong_random_password_here

   QUEUE_CONNECTION=database

   # Midtrans Production
   MIDTRANS_SERVER_KEY=your_production_server_key
   MIDTRANS_CLIENT_KEY=your_production_client_key
   MIDTRANS_IS_PRODUCTION=true
   MIDTRANS_SNAP_URL=https://app.midtrans.com/snap/v1/transactions

   # Mail Configuration (optional)
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailtrap.io
   MAIL_PORT=587
   MAIL_USERNAME=your_username
   MAIL_PASSWORD=your_password
   MAIL_FROM_ADDRESS=noreply@yourdomain.com
   MAIL_FROM_NAME="Ruang App"
   ```

4. **Update docker-compose.yaml untuk Production**
   
   Edit `docker-compose.yaml`, ubah port jika diperlukan:
   ```yaml
   webserver:
     image: nginx:alpine
     container_name: ruang_app_nginx
     ports:
       - "80:80"  # Ubah dari 8000:80 ke 80:80
   ```

5. **Build & Deploy**
   ```bash
   docker-compose up -d --build
   ```

6. **Setup Application**
   ```bash
   # Generate app key
   docker exec ruang_app_backend php artisan key:generate

   # Run migrations
   docker exec ruang_app_backend php artisan migrate --force

   # Seed initial data (admin, categories)
   docker exec ruang_app_backend php artisan db:seed --force

   # Create storage link
   docker exec ruang_app_backend php artisan storage:link

   # Optimize for production
   docker exec ruang_app_backend php artisan config:cache
   docker exec ruang_app_backend php artisan route:cache
   docker exec ruang_app_backend php artisan view:cache
   ```

7. **Setup SSL with Certbot (HTTPS)**
   
   Install Certbot di host machine:
   ```bash
   sudo apt update
   sudo apt install certbot python3-certbot-nginx -y
   ```

   Untuk Nginx di Docker, copy SSL certificates ke volume:
   ```bash
   # Stop nginx container dulu
   docker stop ruang_app_nginx

   # Get certificate
   sudo certbot certonly --standalone -d yourdomain.com

   # Edit nginx/default.conf untuk add SSL
   # Lalu restart container
   docker-compose up -d
   ```

8. **Configure Midtrans Notification URL**
   
   Login ke Midtrans Dashboard → Settings → Configuration:
   ```
   Payment Notification URL: https://yourdomain.com/api/midtrans/callback
   ```

9. **Auto-restart on Server Reboot**
   
   Docker containers sudah di-set `restart: unless-stopped`, jadi akan auto-start saat server reboot.

10. **Monitor Application**
    ```bash
    # Check container status
    docker-compose ps

    # View logs
    docker-compose logs -f

    # Check Laravel logs
    docker exec ruang_app_backend tail -f storage/logs/laravel.log
    ```

---

## Configuration

### File Storage

Default menggunakan `local` disk (storage/app/public). Untuk production, gunakan S3:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=your_bucket_name
```

### Queue Configuration

Default menggunakan `database` driver. Untuk better performance, gunakan Redis:

1. Uncomment Redis service di `docker-compose.yaml`
2. Update `.env`:
   ```env
   QUEUE_CONNECTION=redis
   REDIS_HOST=redis
   REDIS_PORT=6379
   ```

### Cron Jobs

Cron sudah berjalan otomatis di container. Jadwal:

```bash
# /etc/cron.d/laravel-cron
* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1
```

Tasks yang dijadwalkan (routes/console.php):
- Auto-complete pending donations (setiap 1 menit) - DEV ONLY

---

## Testing

### API Testing

Import Postman collection: `docs/postman_collection.json` (jika tersedia)

Atau test manual:

```bash
# Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password",
    "password_confirmation": "password"
  }'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }'

# Get campaigns
curl http://localhost:8000/api/campaigns
```

### Midtrans Testing (Sandbox)

1. Create donation via API
2. Get Snap token
3. Open Snap URL di browser
4. Pilih payment method (Gopay/QRIS/VA)
5. Gunakan simulator: https://simulator.sandbox.midtrans.com

**Development Mode - Auto Complete Donations:**
```bash
# Manual run
php artisan donations:auto-complete

# Auto-run setiap 1 menit (Windows)
.\auto_complete_donations.bat

# Auto-run setiap 1 menit (Linux/Server)
nohup ./auto_complete_donations.sh > auto_complete_donations.log 2>&1 &
```

### Unit Testing

```bash
# Run all tests
docker exec ruang_app_backend php artisan test

# Run specific test
docker exec ruang_app_backend php artisan test --filter=CampaignTest
```

---

## Troubleshooting

### Permission Issues

```bash
docker exec ruang_app_backend chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker exec ruang_app_backend chmod -R 775 /var/www/storage /var/www/bootstrap/cache
```

### Database Connection Failed

```bash
# Check MySQL container
docker-compose ps db

# Check logs
docker-compose logs db

# Wait for MySQL to be ready
docker exec ruang_app_db mysqladmin ping -h localhost -u root -proot
```

### Composer Install Failed

```bash
# Clear cache dan reinstall
docker exec ruang_app_backend rm -rf vendor
docker exec ruang_app_backend composer clear-cache
docker exec ruang_app_backend composer install --no-interaction
```

### Queue Not Processing

```bash
# Check supervisor status
docker exec ruang_app_backend supervisorctl status

# Restart workers
docker exec ruang_app_backend supervisorctl restart laravel-worker:*

# Check logs
docker exec ruang_app_backend tail -f storage/logs/laravel.log
```

### Midtrans Callback Not Working

1. **Check notification URL di Midtrans Dashboard**
2. **Pastikan menggunakan HTTPS (production)**
3. **Check logs:**
   ```bash
   docker exec ruang_app_backend tail -f storage/logs/laravel.log | grep "Midtrans"
   ```
4. **Test callback manual:**
   ```bash
   curl -X POST https://yourdomain.com/api/midtrans/callback \
     -H "Content-Type: application/json" \
     -d '{"order_id":"test","status_code":"200","transaction_status":"settlement"}'
   ```

### CSRF Token Mismatch (Admin Panel)

```bash
docker exec ruang_app_backend php artisan config:clear
docker exec ruang_app_backend php artisan cache:clear
```

---

## Useful Commands

```bash
# Docker Commands
docker-compose up -d                    # Start containers
docker-compose down                     # Stop containers
docker-compose restart                  # Restart containers
docker-compose logs -f app              # View logs
docker exec ruang_app_backend bash      # Access container shell

# Laravel Commands (dalam container)
docker exec ruang_app_backend php artisan migrate
docker exec ruang_app_backend php artisan db:seed
docker exec ruang_app_backend php artisan cache:clear
docker exec ruang_app_backend php artisan config:clear
docker exec ruang_app_backend php artisan route:list
docker exec ruang_app_backend php artisan queue:work

# Database Backup
docker exec ruang_app_db mysqldump -u root -proot ruang_app > backup.sql

# Database Restore
docker exec -i ruang_app_db mysql -u root -proot ruang_app < backup.sql
```

---

## Security Recommendations

1. **Change default passwords** di `.env` (DB_PASSWORD, dll)
2. **Disable debug mode** di production (`APP_DEBUG=false`)
3. **Use HTTPS** untuk production
4. **Setup firewall** (UFW) di server
5. **Regular backups** untuk database
6. **Update dependencies** secara berkala:
   ```bash
   composer update
   docker-compose pull
   docker-compose up -d --build
   ```

---

## Support & Documentation

- **API Documentation:** `SECTION_4_API_TESTING_GUIDE.md`
- **Midtrans Integration:** https://docs.midtrans.com
- **Laravel Docs:** https://laravel.com/docs/11.x
- **Filament Docs:** https://filamentphp.com/docs/3.x

---

## License

Private repository - All rights reserved.
