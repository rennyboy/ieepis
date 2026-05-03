# Dokploy Deployment Guide for IEEPIS

This guide provides a step-by-step process for deploying the IEEPIS (Laravel 11, PHP 8.4, Filament v3, PostgreSQL) application to an Ubuntu server using [Dokploy](https://dokploy.com/), a free and open-source PaaS alternative to Laravel Forge or Vercel.

Since you are currently on MX Linux (Debian-based), you can run all SSH commands directly from your local terminal.

## Prerequisites

1. **A VPS or Dedicated Server** running **Ubuntu 24.04** or **22.04 LTS**.
2. **A Domain Name** pointing to your server's IP address (e.g., `ieepis.yourdomain.com`).
3. **SSH Access** to your Ubuntu server as the `root` user.
4. Your application code pushed to a Git repository (GitHub, GitLab, or Bitbucket).

---

## Step 1: Install Dokploy on your Ubuntu Server

Dokploy installs Docker, Traefik (for routing/SSL), and its own control panel automatically.

1. Open your MX Linux terminal and SSH into your server:
   ```bash
   ssh root@your_server_ip
   ```

2. Run the official Dokploy installation script:
   ```bash
   curl -sSL https://dokploy.com/install.sh | sh
   ```

3. Wait for the installation to finish (it may take 5-10 minutes).
4. Once completed, the terminal will provide a URL to access the Dokploy dashboard (usually `http://your_server_ip:3000`).
5. Open that URL in your browser and create your Admin account.

---

## Step 2: Configure PostgreSQL Database in Dokploy

Before deploying the Laravel app, we need the database running.

1. In the Dokploy Dashboard, go to your **Project** (create one if necessary, e.g., "IEEPIS").
2. Click **Create Service** and choose **PostgreSQL**.
3. Fill in the details:
   - **Name**: `ieepis-db`
   - **Database Name**: `ieepis`
   - **User**: `postgres` (or custom)
   - **Password**: *Generate a secure password*
   - **Version**: `14` or later
4. Click **Deploy**.
5. Once deployed, note down the internal connection details provided by Dokploy (you will need these for the Laravel `.env` file). The internal host will usually just be the service name, e.g., `ieepis-db`.

---

## Step 3: Deploy the Laravel Application

Now we deploy the actual IEEPIS codebase.

1. In your Dokploy Project, click **Create Service** and choose **Application**.
2. Set the **Name** to `ieepis-app`.
3. Under the **Source** tab:
   - Connect your Git provider (GitHub/GitLab).
   - Select your IEEPIS repository and branch (e.g., `main`).
4. Under the **Build** tab, select **Nixpacks** (Dokploy's default builder, which automatically detects Laravel and PHP).
5. Under the **Environment** tab, copy your local `.env` variables and paste them. Update the database credentials to match the PostgreSQL service you just created:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://ieepis.yourdomain.com

   DB_CONNECTION=pgsql
   DB_HOST=ieepis-db
   DB_PORT=5432
   DB_DATABASE=ieepis
   DB_USERNAME=postgres
   DB_PASSWORD=your_secure_password
   ```
   *(Ensure `APP_KEY` is also set here).*

6. Under the **Domains** tab:
   - Add your domain (`ieepis.yourdomain.com`).
   - Enable **Let's Encrypt** to automatically generate a free SSL certificate.

---

## Step 4: Add Pre/Post Deployment Scripts

Since Laravel requires specific commands to run during deployment (like migrating the database and caching config), we need to instruct Dokploy to run them.

In your Application settings under **Deploy > Commands**, you can set the run command. Nixpacks usually handles `composer install` and `npm run build` automatically.

However, to ensure migrations run, you should update the **Start Command** (or add a custom Nixpacks configuration file `nixpacks.toml` to your repo).

Alternatively, you can add a post-deploy script in Dokploy:
```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan filament:cache-components
```

---

## Step 5: Start the Deployment

1. Click the **Deploy** button on your `ieepis-app` service.
2. Monitor the build logs. Dokploy will pull the code, install PHP extensions, run `composer install`, run `npm run build`, and start the Octane/FPM server.
3. If the build fails because of a missing PHP extension, you may need to add a `nixpacks.toml` file to the root of your MX Linux project and push it:

   **Example `nixpacks.toml`:**
   ```toml
   [phases.setup]
   nixPkgs = ['php84', 'php84Packages.composer']

   [phases.build]
   cmds = [
       'composer install --no-dev --optimize-autoloader',
       'npm ci',
       'npm run build'
   ]

   [start]
   cmd = 'php artisan serve --host=0.0.0.0 --port=$PORT'
   ```
   *(Note: Dokploy usually detects this fine, but this file forces specific behavior).*

---

## Step 6: Create Storage Link & Admin User

Once the app is running successfully:

1. Open the **Terminal** tab inside your `ieepis-app` service in Dokploy.
2. Run the storage link command:
   ```bash
   php artisan storage:link
   ```
3. Run any initial seeders or create your first super-admin:
   ```bash
   php artisan db:seed
   # OR
   php artisan make:filament-user
   ```

---

## Step 7: Queue Workers and Cron Jobs (Optional but Recommended)

If IEEPIS uses queued jobs or scheduled tasks (e.g., for sending emails or checking maintenance schedules):

### 1. Cron / Scheduler
In the Dokploy dashboard for your application, find the **Cron Jobs** or **Advanced** tab and add:
- **Command**: `php artisan schedule:run`
- **Schedule**: `* * * * *` (Every minute)

### 2. Queue Worker
To run the queue worker continuously, you can either:
- Set up a separate **Application Service** in Dokploy pointing to the same repo, but change the **Start Command** to `php artisan queue:work --tries=3`.
- OR use Laravel Horizon if you have Redis installed.

---

## Troubleshooting

- **500 Error after deploy**: Check the **Logs** tab in Dokploy. It's usually a missing `.env` variable or un-migrated database.
- **Assets (CSS/JS) not loading**: Ensure `APP_URL` uses `https://` in the Environment variables, and verify `npm run build` ran successfully during deployment.
- **Filament icons missing**: Run `php artisan filament:cache-components` in the Dokploy terminal for your app.
