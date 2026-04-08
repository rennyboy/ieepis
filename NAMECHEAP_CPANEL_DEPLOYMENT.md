# Namecheap cPanel Deployment Guide for Laravel IEEPIS

## Overview

This guide walks you through deploying your Laravel IEEPIS application on Namecheap using cPanel. Unlike containerized solutions, cPanel provides traditional shared/dedicated hosting with full file access, SSH terminal, and built-in database management.

## Prerequisites

- ✅ Namecheap hosting account (Shared Hosting, VPS, or Dedicated)
- ✅ cPanel access (provided with most Namecheap plans)
- ✅ SSH access enabled
- ✅ PHP 8.4+ with required extensions
- ✅ MySQL/MariaDB database
- ✅ Composer installed on server
- ✅ Git installed on server (for cloning)
- ✅ Domain pointed to your hosting

## Part 1: Prepare Your Hosting Account

### Step 1: Verify PHP Version

1. Log in to **cPanel** (usually at `your-domain.com:2083`)
2. Go to **Software → Select PHP Version**
3. Verify **PHP 8.4** is available
4. If not available, open a **support ticket** to request upgrade
5. Click on your domain and select **PHP 8.4**
6. Click **Set as Current**

### Step 2: Enable Required PHP Extensions

1. In **Select PHP Version** page
2. Click **Switch to PHP Options**
3. Ensure these are **enabled** (checked):
   ```
   ✅ bcmath
   ✅ curl
   ✅ exif
   ✅ gd
   ✅ intl
   ✅ mbstring
   ✅ mysqli (or pdo_mysql)
   ✅ openssl
   ✅ pcntl
   ✅ pdo
   ✅ pdo_mysql
   ✅ zip
   ```

4. Click **Save** if you made changes

### Step 3: Enable SSH Access

1. In cPanel, go to **Security → SSH Access**
2. Click **Manage SSH Keys**
3. If SSH is disabled, click **Manage SSH Keys** to enable
4. You'll need SSH to run Artisan commands

### Step 4: Increase PHP Limits (if needed)

1. Go to **Software → Select PHP Version**
2. Click **Switch to PHP Options**
3. Set appropriate values:
   ```
   max_execution_time: 300 (seconds)
   max_input_time: 300 (seconds)
   memory_limit: 256M (or higher)
   post_max_size: 100M
   upload_max_filesize: 100M
   ```
4. Click **Save**

## Part 2: Create Database

### Step 1: Create MySQL Database

1. Go to **Databases → MySQL Databases**
2. Under "Create New Database":
   - **Database Name**: `ieepis_db` (or your preference)
   - Click **Create Database**
3. You'll see: `youraccountname_ieepis_db`

### Step 2: Create Database User

1. In same section, under "MySQL Users":
   - **Username**: `ieepis_user` (or your preference)
   - **Password**: Use **Generate Password** for strong password
   - **Copy the password** to a safe place
   - Click **Create User**

### Step 3: Add User to Database

1. Under "Add User to Database":
   - **User**: Select `ieepis_user`
   - **Database**: Select `ieepis_db`
   - Click **Add**
2. When prompted for permissions:
   - Check **ALL PRIVILEGES**
   - Click **Make Changes**

### Step 4: Note Your Database Details

Save these for later:
```
Database Host: localhost (or your-ip if remote)
Database Name: youraccountname_ieepis_db
Database User: youraccountname_ieepis_user
Database Password: [your-strong-password]
```

## Part 3: Upload Application

### Option A: Upload via FTP/SFTP (Easier for beginners)

1. Use an FTP client (Filezilla, WinSCP, Cyberduck):
   - **Host**: your domain or cPanel host
   - **Username**: cPanel username
   - **Password**: cPanel password
   - **Port**: 21 (FTP) or 22 (SFTP)

2. Navigate to `public_html` folder

3. **Delete** any existing files (if it's a new domain)

4. Upload your entire Laravel application

### Option B: Clone from GitHub via SSH (Recommended)

1. Connect via SSH:
   ```bash
   ssh your-cpanel-user@your-domain.com
   ```

2. Navigate to public_html:
   ```bash
   cd ~/public_html
   ```

3. Remove default files:
   ```bash
   rm -rf *
   rm -rf .*
   ```

4. Clone your repository:
   ```bash
   git clone https://github.com/your-repo/ieepis.git .
   ```
   (Note the `.` at the end - it clones into current directory)

5. If private repo, you'll need to:
   - Generate SSH key on server
   - Add public key to GitHub
   - Use SSH URL for clone

### Step 4: Install Dependencies

Via SSH:

1. Make sure you're in the right directory:
   ```bash
   pwd  # Should show /home/youruser/public_html
   ```

2. Install Composer dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. Wait for installation to complete (2-5 minutes depending on connection)

## Part 4: Configure Environment

### Step 1: Copy Environment File

Via SSH or file manager:

```bash
cp .env.example .env
```

### Step 2: Generate Application Key

Via SSH:

```bash
php artisan key:generate
```

This generates a unique key and updates `.env`

### Step 3: Update .env File

Edit `.env` with your favorite editor (nano, vi, or cPanel file manager):

```bash
nano .env
```

Update these values:

```env
APP_NAME=IEEPIS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:xxxx... (already set from key:generate)

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=youraccountname_ieepis_db
DB_USERNAME=youraccountname_ieepis_user
DB_PASSWORD=your-strong-password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=smtp.namecheap.com
MAIL_PORT=587
MAIL_USERNAME=your-email@your-domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="IEEPIS"

LOG_CHANNEL=stack
LOG_LEVEL=warning
```

To save in nano:
- Press `Ctrl + X`
- Press `Y` for yes
- Press `Enter` to confirm filename

## Part 5: Set Up Public Folder

### Critical: Point Domain to /public

Your web server must serve from `/public` folder, not root.

#### Option A: Via .htaccess (Easiest)

1. Create `.htaccess` in `public_html` root:
   ```bash
   cat > .htaccess << 'EOF'
   <IfModule mod_rewrite.c>
       RewriteEngine on
       RewriteRule ^(.*)$ public/$1 [L]
   </IfModule>
   EOF
   ```

2. Create `.htaccess` in `public` folder (if not exists):
   ```bash
   cp public/.htaccess .htaccess
   ```

#### Option B: Via cPanel Document Root (More Secure)

1. Go to **Addon Domains** (or **Domains** if main domain)
2. Edit the domain settings
3. Change **Document Root** from `/public_html` to `/public_html/public`
4. Save changes

**Note:** Option B is more secure and recommended.

## Part 6: Set File Permissions

Via SSH:

```bash
# Give write permission to storage and bootstrap
chmod -R 775 storage bootstrap/cache

# Make storage directories:
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/app/uploads

# Set ownership (important for web server)
chown -R nobody:nobody storage bootstrap/cache
```

## Part 7: Run Database Migrations

Via SSH:

```bash
php artisan migrate --force
```

This creates all necessary database tables.

### Optional: Seed Database

If you have seeders:

```bash
php artisan db:seed --force
```

### Check Migration Status

```bash
php artisan migrate:status
```

## Part 8: Configure SSL Certificate

### Free SSL with AutoSSL

1. Go to **Security → AutoSSL**
2. Look for your domain
3. Click **Check and Install**
4. Wait for installation (usually instant)
5. Go back to **Domains**
6. Verify SSL is showing as "Auto" or "✓"

### Force HTTPS

Edit `app/Http/Middleware/TrustProxies.php` or add to `.env`:

```env
APP_URL=https://your-domain.com
FORCE_HTTPS=true
```

Or edit `config/app.php`:
```php
'url' => env('APP_URL', 'https://your-domain.com'),
```

## Part 9: Configure Cron for Scheduler (Optional)

If you use Laravel Scheduler:

1. Go to **Advanced → Cron Jobs**
2. Add new cron job:
   ```
   /usr/bin/php /home/your-user/public_html/artisan schedule:run >> /dev/null 2>&1
   ```
3. Frequency: **Every 5 minutes** (or Every 1 minute)

This runs Laravel's scheduled tasks.

## Part 10: Configure Queue Worker (Optional)

If using queues (Redis not available in most cPanel setups):

1. Change in `.env`:
   ```env
   QUEUE_CONNECTION=database
   ```

2. Create jobs table:
   ```bash
   php artisan queue:table
   php artisan migrate
   ```

3. Via cPanel Cron:
   ```
   /usr/bin/php /home/your-user/public_html/artisan queue:work --once >> /dev/null 2>&1
   ```

## Part 11: Optimize for Production

Via SSH:

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Clear compiled classes
php artisan optimize:clear

# Then re-optimize
php artisan optimize
```

## Part 12: Set Up File Uploads

If using file uploads:

```bash
# Create uploads directory
mkdir -p storage/app/public/uploads

# Create symbolic link
php artisan storage:link

# Or manually create link
ln -s /home/your-user/public_html/storage/app/public /home/your-user/public_html/public/storage
```

## Part 13: Configure Email (Optional)

### Using Namecheap Email

1. Get your email credentials from cPanel → **Email Accounts**
2. Update `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.namecheap.com
   MAIL_PORT=587
   MAIL_USERNAME=your-full-email@your-domain.com
   MAIL_PASSWORD=your-email-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@your-domain.com
   MAIL_FROM_NAME="IEEPIS"
   ```

3. Test in Tinker:
   ```bash
   php artisan tinker
   >>> Mail::to('test@example.com')->send(new \Illuminate\Mail\Mailable());
   ```

## Part 14: Test Your Application

### Via Browser

1. Visit `https://your-domain.com`
2. You should see your Laravel app homepage
3. Test key features:
   - Navigation
   - Login/Registration
   - Admin panel at `/admin`
   - Database operations

### Via SSH

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()
>>> exit()

# Test Artisan commands
php artisan config:show app.name
php artisan route:list
php artisan migrate:status
```

### Check Logs

```bash
# View application logs
tail -f storage/logs/laravel.log

# View error logs
tail -f /home/your-user/public_html/storage/logs/laravel.log
```

## Part 15: Set Up Backups

### Via cPanel

1. Go to **Backup Wizard**
2. Click **Backup**
3. Select what to backup:
   - ✅ Home Directory (includes app)
   - ✅ MySQL Databases
   - ✅ Email Accounts (optional)
4. Click **Backup**

### Manual Backup

Via SSH:

```bash
# Backup database
mysqldump -u your-user -p your-password your-database > db-backup.sql

# Backup files
tar -czf app-backup.tar.gz /home/your-user/public_html

# Download from cPanel File Manager
```

## Part 16: Enable Development Tools (For Debugging)

### Only for Development, Disable in Production!

**Never enable these in production:**

```env
APP_DEBUG=false  # MUST be false
APP_ENV=production  # MUST be production
```

If debugging is needed:

1. Temporarily enable:
   ```env
   APP_DEBUG=true
   ```

2. Check logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Disable again immediately after debugging

## Troubleshooting

### Problem: 500 Internal Server Error

**Diagnosis:**
```bash
# Check Laravel error log
tail -f storage/logs/laravel.log

# Check web server error log
tail -f /usr/local/apache/logs/error_log

# Check permissions
ls -la storage/ bootstrap/
```

**Solutions:**
- Verify file permissions are correct
- Check .env file exists and readable
- Verify database connection
- Check APP_KEY is set in .env

### Problem: Can't Connect to Database

**Diagnosis:**
```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo()
```

**Solutions:**
- Verify database name in .env: `youraccountname_ieepis_db`
- Verify database user: `youraccountname_ieepis_user`
- Verify password is correct
- Check database host is `localhost`
- Verify user has privileges on database

### Problem: "No application encryption key has been generated"

```bash
php artisan key:generate
```

### Problem: "Composer not found"

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Or ask hosting support to install it
```

### Problem: Migration Errors

**Solutions:**
```bash
# Check migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback

# Run migrations again
php artisan migrate --force

# For fresh database (careful!)
php artisan migrate:fresh --seed --force
```

### Problem: Permissions Denied on Files

```bash
# Fix ownership
chown -R nobody:nobody /home/your-user/public_html/storage
chown -R nobody:nobody /home/your-user/public_html/bootstrap/cache

# Fix permissions
chmod -R 755 /home/your-user/public_html
chmod -R 775 /home/your-user/public_html/storage
chmod -R 775 /home/your-user/public_html/bootstrap/cache
```

### Problem: "HTTPS Not Working"

1. Check AutoSSL is installed (Part 8)
2. Force HTTPS in `.env`:
   ```env
   APP_URL=https://your-domain.com
   ```
3. Update `config/app.php`:
   ```php
   'url' => env('APP_URL', 'https://your-domain.com'),
   ```
4. Clear cache:
   ```bash
   php artisan cache:clear
   php artisan config:cache
   ```

### Problem: "Assets Not Loading (CSS/JS)"

**Solutions:**
```bash
# Rebuild assets (if using npm)
npm run build

# Or clear Vite cache
php artisan view:cache
php artisan route:cache
```

If assets still not loading:
1. Check `.htaccess` in `public/` folder
2. Check Document Root is set to `/public`
3. Check file permissions on `public/` folder

### Problem: "Mail Not Sending"

```bash
# Test mail
php artisan tinker
>>> Mail::mailer('smtp')->raw('Test', function($m) { $m->to('your-email@example.com'); });
```

Check:
1. MAIL_MAILER is `smtp`
2. MAIL_HOST is `smtp.namecheap.com`
3. MAIL_PORT is `587`
4. MAIL_USERNAME and MAIL_PASSWORD are correct
5. Email account is created in cPanel

## Best Practices

### Security

1. **Keep APP_DEBUG=false in production**
2. **Use strong database passwords**
3. **Regularly backup your database**
4. **Keep Laravel and dependencies updated**
5. **Set up HTTPS (AutoSSL)**
6. **Limit file upload sizes**
7. **Don't commit `.env` to git**

### Performance

1. **Enable caching:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Set up cron for scheduler** (Part 9)

3. **Use database queries efficiently** (avoid N+1)

4. **Compress assets** (`npm run build`)

5. **Monitor error logs regularly**

### Maintenance

1. **Regular backups** (daily if possible)
2. **Update dependencies** (`composer update`)
3. **Check error logs** (`tail -f storage/logs/laravel.log`)
4. **Monitor disk space** in cPanel
5. **Test critical features** regularly

## Useful SSH Commands

```bash
# Navigate to app directory
cd ~/public_html

# List files
ls -la

# Edit .env
nano .env

# Run Artisan commands
php artisan tinker
php artisan migrate
php artisan db:seed
php artisan cache:clear

# Check Laravel logs
tail -f storage/logs/laravel.log

# View disk usage
du -sh .

# Check PHP version
php -v

# Check installed extensions
php -m

# Run composer
composer install
composer update

# Git operations
git status
git pull origin main
git log

# Check database
mysql -u user -p -e "USE database; SHOW TABLES;"
```

## Deployment Checklist

**Before Going Live:**
- [ ] PHP 8.4 installed and set as default
- [ ] Required PHP extensions enabled
- [ ] Database created and user assigned
- [ ] Application cloned/uploaded
- [ ] Composer dependencies installed
- [ ] `.env` file configured
- [ ] `php artisan key:generate` run
- [ ] Database migrations completed
- [ ] Storage/bootstrap permissions set correctly
- [ ] Public folder configured properly
- [ ] SSL certificate installed
- [ ] Domain pointing to correct nameservers
- [ ] Tested all key features
- [ ] Backups configured

**In Production:**
- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] Config cached
- [ ] Routes cached
- [ ] Views cached
- [ ] Error logs monitored
- [ ] Backups scheduled
- [ ] Email working
- [ ] HTTPS enforced

## Post-Deployment Tasks

1. **Create admin user** (if not seeded):
   ```bash
   php artisan tinker
   >>> App\Models\User::create(['name' => 'Admin', 'email' => 'admin@domain.com', 'password' => Hash::make('password')])
   ```

2. **Test email:**
   - Send test email from admin panel
   - Verify it arrives

3. **Configure storage links:**
   ```bash
   php artisan storage:link
   ```

4. **Set up monitoring:**
   - Monitor error logs daily
   - Check disk usage weekly
   - Verify backups are running

5. **Announce deployment:**
   - Notify users app is live
   - Gather feedback
   - Monitor for issues

## Support & Resources

- **Namecheap Support:** https://support.namecheap.com
- **cPanel Documentation:** https://docs.cpanel.net
- **Laravel Docs:** https://laravel.com/docs/11
- **Filament Docs:** https://filamentphp.com/docs
- **PHP Documentation:** https://www.php.net/docs.php

## Quick Reference

### Useful Links (after deployment)

```
Admin Panel:      https://your-domain.com/admin
cPanel:           https://your-domain.com:2083
File Manager:     Via cPanel → File Manager
SSH:              ssh your-user@your-domain.com
FTP:              your-domain.com (port 21)
```

### Critical Files

```
.env                     - Configuration (keep secret)
public/index.php         - Entry point
storage/logs/            - Application logs
bootstrap/cache/         - Cache files
config/                  - Configuration files
app/Models/              - Database models
app/Http/Controllers/    - Request handlers
database/migrations/     - Schema definitions
```

### Important Commands

```bash
php artisan migrate              # Run migrations
php artisan tinker              # Interactive shell
php artisan cache:clear         # Clear cache
php artisan config:cache        # Cache config
tail -f storage/logs/laravel.log # View logs
composer update                 # Update dependencies
```

## Final Notes

- **Namecheap cPanel is more stable** than containerized solutions for this setup
- **You have plenty of storage** and don't need to worry about 512MB limits
- **Traditional hosting is easier** for maintenance and updates
- **Support for issues is more straightforward** than container debugging
- **Cost is usually lower** than managed container services

## Deployment Success Indicators

✅ App loads at https://your-domain.com
✅ Admin panel accessible at https://your-domain.com/admin
✅ Can log in with admin credentials
✅ Database operations work
✅ Error log shows no critical errors
✅ SSL certificate is valid
✅ All resources (CSS, JS, images) load correctly
✅ Email sends successfully
✅ Backups are running

---

**Status:** ✅ Ready for cPanel Deployment
**Guide Version:** 1.0
**Last Updated:** 2024
**Laravel Version:** 11.x
**PHP Version:** 8.4+

Good luck with your deployment! For questions, refer to the troubleshooting section or contact Namecheap support.