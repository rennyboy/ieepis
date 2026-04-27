# Render Quick Start - Deploy IEEPIS in 10 Minutes

## 1️⃣ Generate APP_KEY (Do This First!)

```bash
php artisan key:generate
# Copy the key value displayed (base64:...)
```

## 2️⃣ Push to GitHub

```bash
git add .
git commit -m "Add Render deployment configuration"
git push origin main
```

## 3️⃣ Create Web Service on Render

1. Go to https://dashboard.render.com
2. Click **"New +"** → **"Web Service"**
3. Select your GitHub repo
4. Fill in:
   - **Name**: `ieepis-app`
   - **Runtime**: Docker
   - **Branch**: main
   - **Region**: Choose closest to you

## 4️⃣ Add Environment Variables

In the Render dashboard, add these in **Advanced** section:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_KEY_FROM_STEP_1
APP_URL=https://ieepis-app.onrender.com

DB_HOST=db
DB_DATABASE=ieepis_db
DB_USERNAME=ieepis_user
DB_PASSWORD=choose_a_strong_password_here
DB_ROOT_PASSWORD=choose_another_strong_password

CACHE_DRIVER=redis
CACHE_HOST=redis
CACHE_PORT=6379
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
SESSION_DRIVER=redis
BROADCAST_DRIVER=redis

LOG_CHANNEL=stderr
LOG_LEVEL=warning
```

## 5️⃣ Create MySQL Database Service

1. Go back to services list
2. Click **"New +"** → **"Private Service"**
3. Choose **"Docker"** runtime
4. Fill in:
   - **Name**: `db`
   - **Image**: `mysql:8.0`
   - **Auto-deploy**: Off

5. Add environment variables:
```
MYSQL_ROOT_PASSWORD=choose_another_strong_password
MYSQL_DATABASE=ieepis_db
MYSQL_USER=ieepis_user
MYSQL_PASSWORD=choose_a_strong_password_here
MYSQL_INITDB_SKIP_TZINFO=1
```

6. Click **"Advanced"** → **"Add Disk"**:
   - **Mount Path**: `/var/lib/mysql`
   - **Size**: 20 GB

7. Click **"Create Private Service"**

## 6️⃣ Create Redis Service

1. Click **"New +"** → **"Private Service"**
2. Choose **"Docker"** runtime
3. Fill in:
   - **Name**: `redis`
   - **Image**: `redis:7-alpine`
   - **Auto-deploy**: Off

4. Click **"Advanced"** → **"Add Disk"**:
   - **Mount Path**: `/data`
   - **Size**: 5 GB

5. Click **"Create Private Service"**

## 7️⃣ Wait for Deployment

- Web service will start deploying automatically
- Watch the **Logs** tab
- Look for: "Application setup complete. Starting services..."
- Wait 2-3 minutes for database to initialize

## 8️⃣ Verify It's Working

1. Check **Web Service Logs** for any errors
2. Visit: `https://ieepis-app.onrender.com/health`
3. Should see: "healthy"
4. Visit: `https://ieepis-app.onrender.com`
5. Application should load!

## ✅ You're Done!

Your Laravel application is now live on Render!

---

## Troubleshooting

### "Database connection refused"
- Wait 2+ minutes, MySQL is slow to start
- Check database service logs

### "Can't find APP_KEY"
- Make sure you copied the exact key from `php artisan key:generate`
- Make sure it starts with `base64:`

### "Vite manifest not found"
- Run locally: `vendor/bin/sail npm run build`
- Commit and push to GitHub
- Redeploy

### "Permission denied on storage"
- SSH into web service → Shell
- Run: `chmod -R 775 storage bootstrap/cache`

---

## Next Steps

1. **Test your application**: Make sure everything works
2. **Access admin**: https://ieepis-app.onrender.com/admin
3. **Backup database**: Database → Backups → Create
4. **Monitor logs**: Check regularly for errors
5. **Scale if needed**: Upgrade instance type if slow

---

## Useful Render Shell Commands

In Web Service → Shell tab:

```bash
# Check migrations ran
php artisan migrate:status

# Run migrations manually
php artisan migrate --force

# Seed database
php artisan db:seed --force

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# View live logs
tail -f storage/logs/laravel.log
```

---

## Important: Environment Variable Names

| Your App Env | Render Env |
|-------------|-----------|
| `DB_HOST` | Must be `db` (not localhost) |
| `CACHE_HOST` | Must be `redis` (not localhost) |
| `REDIS_HOST` | Must be `redis` (not localhost) |
| `DB_PASSWORD` | Must match MySQL MYSQL_PASSWORD |
| `APP_KEY` | Must start with `base64:` |

---

## Need More Help?

- Full guide: See `RENDER_DEPLOYMENT.md`
- Render docs: https://render.com/docs
- Laravel docs: https://laravel.com/docs/11
- Check application logs first!