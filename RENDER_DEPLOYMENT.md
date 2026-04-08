# Render Deployment Guide for IEEPIS Laravel Application

## Overview

This guide walks you through deploying the IEEPIS Laravel application on Render using Docker Compose. The application consists of:
- **Web Service**: Laravel app with Nginx + PHP-FPM (consolidated in single container)
- **Database**: MySQL 8.0
- **Cache/Queue**: Redis 7
- **Optional Worker**: Laravel queue worker for background jobs

## Prerequisites

Before deploying to Render:

1. **GitHub Repository**: Your code must be in a public or connected GitHub repository
2. **Render Account**: Sign up at https://render.com
3. **Environment Variables**: Prepare your `.env` values
4. **Docker**: Understand basic Docker concepts (containers, services, volumes)

## Step 1: Prepare Your Repository

Ensure these files exist in your repository root:

```
Dockerfile                          # Docker image definition
docker-compose.yml                  # Multi-container orchestration
docker/nginx/default.conf           # Nginx web server config
docker/supervisor/supervisord.conf  # Process manager config
docker/entrypoint.sh                # Application startup script
render.yaml                         # Render-specific configuration (optional)
```

All these files have been created/updated in your project.

## Step 2: Set Up on Render Dashboard

### Option A: Using Docker Compose (Recommended)

1. Go to [Render Dashboard](https://dashboard.render.com)
2. Click **New** → **Web Service**
3. Select **Docker** as the runtime
4. Choose your GitHub repository
5. Configure settings:
   - **Name**: `ieepis-app` (or your preferred name)
   - **Root Directory**: `/` (if repo is root) or path to app
   - **Dockerfile Path**: `Dockerfile`
   - **Docker Command**: Leave blank (uses CMD from Dockerfile)
   - **Instance Type**: Standard or higher (depends on traffic)
   - **Region**: Select closest to your users
   - **Auto-deploy**: Enable (optional)

6. Click **Advanced** and add the following environment variables:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_URL=https://your-app.onrender.com
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=ieepis_db
DB_USERNAME=ieepis_user
DB_PASSWORD=STRONG_PASSWORD_HERE
DB_ROOT_PASSWORD=ANOTHER_STRONG_PASSWORD
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

7. Click **Create Web Service**

### Step 3: Add Background Services

After the web service is created:

1. Go back to your Web Service page
2. Click **Add Service** → **Private Service**
3. Select **Docker** runtime

**For MySQL Database:**
```
Name: db
Image: mysql:8.0
Auto-deploy: Off

Environment Variables:
MYSQL_ROOT_PASSWORD=ANOTHER_STRONG_PASSWORD
MYSQL_DATABASE=ieepis_db
MYSQL_USER=ieepis_user
MYSQL_PASSWORD=STRONG_PASSWORD_HERE
MYSQL_INITDB_SKIP_TZINFO=1
```

Add a disk volume:
- **Mount Path**: `/var/lib/mysql`
- **Size**: 20 GB

**For Redis Cache/Queue:**
```
Name: redis
Image: redis:7-alpine
Auto-deploy: Off
```

Add a disk volume:
- **Mount Path**: `/data`
- **Size**: 5 GB

4. Both services should be added as **Private Services** (not accessible from internet)

## Step 4: Verify Connectivity

The docker-compose.yml already configures services to communicate via the network name `ieepis-network`. However, on Render:

- Services communicate via **private network** using service names as hostnames
- The `web` service connects to `db` and `redis` services by their names
- Update your `docker-compose.yml` if service names differ

## Step 5: Generate APP_KEY

If you haven't generated your APP_KEY yet:

**Locally:**
```bash
php artisan key:generate
# Copy the generated key (from APP_KEY=base64:xxxx)
```

**In Render Environment Variable**: Set `APP_KEY` to the generated value.

The entrypoint script will also generate one if missing, but it's better to pre-generate.

## Step 6: Deploy and Run Migrations

After all services are created:

1. Your web service should deploy automatically
2. Watch the deploy logs for the first startup
3. The entrypoint script (`docker/entrypoint.sh`) will automatically:
   - Wait for the database to be ready
   - Run migrations with `php artisan migrate --force`
   - Cache configuration and routes
   - Start Nginx and PHP-FPM

4. **Manual Migration** (if needed):
   - Go to Web Service → Shell
   - Run: `php artisan migrate --force`

5. **Seed Database** (if needed):
   - Run: `php artisan db:seed --force`

## Step 7: Monitor Deployment

### Check Service Status
- **Logs**: Web Service → Logs tab
- **Health Check**: Service should show "Live" when healthy
- **Build Status**: Monitor the deployment in real-time

### Common Startup Issues

**"Unable to reach database"**
- Wait 30-60 seconds; MySQL takes time to initialize
- Check database service logs for errors
- Verify environment variables match (host should be `db`)

**"Vite manifest not found"**
- Run: `vendor/bin/sail npm run build` locally or in shell
- Commit built assets to git

**"Permission denied on storage"**
- Already handled in Dockerfile and entrypoint
- If issues persist, run: `php artisan storage:link`

## Step 8: Environment Variables Checklist

| Variable | Value | Notes |
|----------|-------|-------|
| `APP_ENV` | production | Never set to debug in production |
| `APP_DEBUG` | false | Security critical |
| `APP_KEY` | base64:xxx | Generate with `php artisan key:generate` |
| `APP_URL` | https://your-app.onrender.com | Must match your Render domain |
| `DB_HOST` | db | Use private service name |
| `DB_DATABASE` | ieepis_db | Match MySQL service env |
| `DB_USERNAME` | ieepis_user | Match MySQL service env |
| `DB_PASSWORD` | your_strong_password | Match MySQL service env |
| `CACHE_DRIVER` | redis | For performance |
| `CACHE_HOST` | redis | Use private service name |
| `QUEUE_CONNECTION` | redis | For background jobs |
| `REDIS_HOST` | redis | Use private service name |
| `SESSION_DRIVER` | redis | Distribute sessions across instances |
| `LOG_CHANNEL` | stderr | Render captures stderr |

## Step 9: Access Your Application

Once deployed and healthy:

1. Visit: `https://your-app-name.onrender.com`
2. Check logs for any errors: Web Service → Logs
3. Test key pages to ensure everything works

## Database Backups

To backup your MySQL database on Render:

1. Go to your **db** (Private Service) page
2. Click the **Backups** tab
3. Click **Create Backup**
4. Download the `.sql` file when ready

## Scaling and Performance

### For Increased Traffic

1. **Web Service**: Upgrade Instance Type
   - Standard → Standard+ → Pro → Pro+ → Pro RTX

2. **Database**: Increase disk size if needed
   - Private Service → Settings → Disk → Upgrade

3. **Redis**: Upgrade instance type
   - Private Service → Settings → Instance Type

### Optional: Queue Worker

To enable background job processing:

1. Uncomment the `worker` service in `docker-compose.yml`
2. Deploy again
3. Set `QUEUE_CONNECTION=redis` in web service env vars
4. Jobs will be processed automatically

## Troubleshooting

### Application doesn't start

**Check logs:**
```
Web Service → Logs → View build logs and service logs
```

**Common issues:**
- Missing environment variables (check all are set)
- Database not ready (wait 60 seconds)
- Storage permissions (fixed in entrypoint)

### Can't connect to database

**Verify:**
1. Database service is running (check db service page)
2. `DB_HOST` is exactly `db` (not localhost or IP)
3. Credentials match between services
4. Wait at least 2 minutes after db creation

### Redis connection errors

**Verify:**
1. Redis service is running
2. `CACHE_HOST` and `REDIS_HOST` are exactly `redis`
3. `CACHE_PORT` and `REDIS_PORT` are `6379`

### Migrations fail

**Solutions:**
1. Check database is fully initialized (wait 2+ minutes)
2. Run in shell: `php artisan migrate:fresh --force`
3. Check storage permissions: `chmod -R 775 storage bootstrap/cache`

### Application is very slow

**Check:**
1. Instance type (upgrade if needed)
2. Database size (might need optimization)
3. Application logs for N+1 queries
4. Cache is working: `CACHE_DRIVER=redis`

## Useful Commands in Render Shell

Access shell: Web Service → Shell tab

```bash
# Check application status
php artisan config:show database.default

# Run migrations
php artisan migrate --force

# Seed database
php artisan db:seed --force

# Clear caches
php artisan cache:clear
php artisan config:clear

# View logs (on server)
tail -f storage/logs/laravel.log

# Generate sitemap (if using)
php artisan sitemap:generate

# Other Artisan commands
php artisan tinker
```

## Security Considerations

1. **APP_DEBUG=false** in production (never true)
2. **Use HTTPS**: Render provides automatic SSL
3. **Environment Variables**: Use Render's env var editor, never commit to git
4. **Database Backups**: Create regularly via Render dashboard
5. **Monitor Logs**: Check for errors and suspicious activity
6. **Update Dependencies**: Run `composer update` locally and deploy

## Monitoring and Logs

### View Logs
- Go to Web Service → Logs
- Filter by time or search keywords
- Use `tail -f storage/logs/laravel.log` in shell for live view

### Set Up Alerts
- Render Dashboard → Settings → Notifications
- Add email alerts for deployment failures
- Monitor application errors with external service (e.g., Sentry)

## Redeploy Application

To redeploy after code changes:

1. Push to GitHub: `git push origin main`
2. Render automatically deploys (if auto-deploy enabled)
3. **Manual deploy**: Web Service → Manual Deploy → Deploy Latest

## Delete Services

To remove everything (warning: destructive):

1. Delete Web Service → Settings → Delete Service
2. Delete db Private Service → Settings → Delete Service
3. Delete redis Private Service → Settings → Delete Service

## Next Steps

1. **Test thoroughly**: All user workflows
2. **Monitor**: Set up error tracking (Sentry, Rollbar, etc.)
3. **Backup**: Enable automated backups
4. **Scale**: Monitor traffic and upgrade as needed
5. **Update**: Keep Laravel and dependencies current

## Support and Resources

- **Render Docs**: https://render.com/docs
- **Render Support**: dashboard.render.com/support
- **Laravel Docs**: https://laravel.com/docs/11
- **Docker Docs**: https://docs.docker.com

## Deployment Checklist

- [ ] Repository connected to Render
- [ ] Web service created with Docker runtime
- [ ] Database (db) private service created
- [ ] Redis (redis) private service created
- [ ] All environment variables set correctly
- [ ] APP_KEY generated and set
- [ ] Application deployed successfully
- [ ] Migrations ran successfully
- [ ] Application accessible at public URL
- [ ] Logs show no errors
- [ ] Database backups configured
- [ ] SSL/HTTPS working
- [ ] Optional worker service enabled (if needed)

---

**Last Updated**: 2024
**Render Support**: For account or billing issues, contact Render support directly