# Namecheap cPanel - Quick Start (15 Minutes)

## 🚀 Deploy Your Laravel App in 15 Minutes

This is the fast version. For detailed instructions, see `NAMECHEAP_CPANEL_DEPLOYMENT.md`.

---

## Prerequisites Checklist

- [ ] Namecheap hosting with cPanel access
- [ ] PHP 8.4+ enabled in cPanel
- [ ] SSH access enabled
- [ ] Domain pointing to your hosting
- [ ] Application ready to deploy

---

## Step 1: Set Up Database (5 minutes)

### 1.1 Create Database in cPanel

1. Login to cPanel
2. Go to **Databases → MySQL Databases**
3. Create database: `ieepis_db`
4. Create user: `ieepis_user` with strong password
5. Add user to database with ALL PRIVILEGES

**Save these:**
```
Database: yourname_ieepis_db
User: yourname_ieepis_user
Password: [your-strong-password]
Host: localhost
```

---

## Step 2: Upload Application (3 minutes)

### Option A: Via GitHub SSH (Recommended)

```bash
ssh yourname@yourdomain.com
cd ~/public_html
rm -rf *
git clone https://github.com/your-repo/ieepis.git .
```

### Option B: Via FTP

1. Download FileZilla or WinSCP
2. Connect with cPanel username/password
3. Navigate to `public_html`
4. Upload your Laravel application
5. **Skip:** `node_modules/` and `vendor/` folders

---

## Step 3: Install Dependencies (2 minutes)

Via SSH:

```bash
cd ~/public_html
composer install --no-dev --optimize-autoloader
```

---

## Step 4: Configure Application (3 minutes)

### Create .env File

```bash
cp .env.example .env
php artisan key:generate
```

### Edit .env

Update these values in `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=localhost
DB_DATABASE=yourname_ieepis_db
DB_USERNAME=yourname_ieepis_user
DB_PASSWORD=[password-from-step-1]

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

**Edit command:**
```bash
nano .env
# Ctrl+X to save and exit
```

---

## Step 5: Set Permissions (1 minute)

```bash
chmod -R 775 storage bootstrap/cache
chown -R nobody:nobody storage bootstrap/cache
mkdir -p storage/logs storage/framework/{cache,sessions,views}
```

---

## Step 6: Run Database Migrations (1 minute)

```bash
php artisan migrate --force
php artisan db:seed --force
```

---

## Step 7: Configure cPanel (2 minutes)

### Set Document Root to /public

In cPanel:

1. Go to **Addon Domains** (or **Domains**)
2. Click your domain
3. Change **Document Root** to: `/home/yourname/public_html/public`
4. Save and wait 5 minutes

### Enable HTTPS

1. Go to **Security → AutoSSL**
2. Find your domain
3. Click **Check and Install**

---

## Step 8: Optimize for Production (1 minute)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## Step 9: Test Your App (1 minute)

Visit: `https://yourdomain.com`

Should see:
- ✅ Homepage loads
- ✅ Admin panel at `/admin` works
- ✅ No errors in logs

Check logs:
```bash
tail -f ~/public_html/storage/logs/laravel.log
```

---

## ✅ You're Done!

Your Laravel app is now live on Namecheap cPanel!

---

## Common Issues

| Issue | Fix |
|-------|-----|
| 500 Error | Check logs: `tail -f storage/logs/laravel.log` |
| Can't connect DB | Verify DB credentials in `.env` match cPanel |
| Assets not loading | Ensure Document Root is `/public_html/public` |
| HTTPS not working | Run AutoSSL in cPanel |
| "No APP_KEY" | Run `php artisan key:generate` |

---

## Optional: Set Up Scheduler (For Cron Jobs)

In cPanel → **Advanced → Cron Jobs**:

```
/usr/bin/php /home/yourname/public_html/artisan schedule:run >> /dev/null 2>&1
```

Frequency: **Every 5 Minutes** (or Every Minute)

---

## Optional: Enable Email

Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.namecheap.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

---

## Next Updates

When you update code:

```bash
# Pull latest
git pull origin main

# Install new dependencies
composer install --no-dev --optimize-autoloader

# Run new migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Optimize
php artisan optimize
```

---

## Quick Reference

```bash
# Essential Commands
php artisan migrate --force          # Run migrations
php artisan cache:clear              # Clear cache
php artisan tinker                   # Debug shell
tail -f storage/logs/laravel.log     # View logs
php artisan optimize                 # Optimize app

# File Permissions
chmod -R 775 storage bootstrap/cache
chown -R nobody:nobody storage bootstrap/cache

# Check Status
php artisan migrate:status
php artisan config:show app.name
```

---

## Support

- **Full Guide:** See `NAMECHEAP_CPANEL_DEPLOYMENT.md`
- **cPanel Help:** https://support.namecheap.com
- **Laravel Help:** https://laravel.com/docs/11

---

**Total Setup Time:** 15-20 minutes

You're all set! 🎉