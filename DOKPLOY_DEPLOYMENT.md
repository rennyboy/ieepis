# Dokploy Deployment Guide for IEEPIS

This guide deploys IEEPIS (Laravel 12, PHP 8.4, Filament v3, PostgreSQL 16, Redis 7) to a self-hosted Ubuntu server using [Dokploy](https://dokploy.com/) — a free, open-source PaaS alternative to Forge/Vercel.

The repository ships with a **production-ready `docker-compose.prod.yml`** that defines the full stack: nginx + PHP-FPM + queue worker + scheduler + PostgreSQL + Redis. Dokploy's Docker Compose application type uses this file directly, so you do **not** need Nixpacks or Buildpacks.

---

## Prerequisites

1. **VPS / dedicated server** running **Ubuntu 22.04 / 24.04 LTS** (≥ 2 GB RAM, 2 vCPU recommended).
2. **Domain name** with an A record pointing to the server (e.g. `ieepis.yourdomain.com`).
3. **SSH access** as `root` or a sudo-enabled user.
4. The IEEPIS repo on GitHub / GitLab / Gitea / Bitbucket.

---

## Step 1 — Install Dokploy

SSH into the server and run the official installer (it provisions Docker, Traefik with automatic Let's Encrypt SSL, and the Dokploy control panel):

```bash
ssh root@your_server_ip
curl -sSL https://dokploy.com/install.sh | sh
```

When the script finishes, open `http://your_server_ip:3000` and create the admin account.

---

## Step 2 — Create the Project and a Compose Application

1. In Dokploy, click **Create Project** → name it **IEEPIS**.
2. Inside the project click **Create Service** → **Application** → **Docker Compose**.
3. **Source tab**:
   - Provider: GitHub / GitLab / Git
   - Repository: your IEEPIS repo
   - Branch: `main` (or `feature-core` while developing)
   - **Compose file path**: `docker-compose.prod.yml`
4. **Build tab**: leave the default. Dokploy will run `docker compose -f docker-compose.prod.yml build` against the repo.

> The compose file builds two images from the same `Dockerfile` via separate targets: `app` (php-fpm + composer deps + assets) and `web` (nginx serving the baked-in `public/` directory).

---

## Step 3 — Configure environment variables

Under the **Environment** tab of the Compose service, paste the production env. Adjust values to match your secrets:

```env
# App
APP_NAME=IEEPIS
APP_KEY=base64:GENERATE_WITH_php_artisan_key_generate
APP_URL=https://ieepis.yourdomain.com
LOG_CHANNEL=stderr
LOG_LEVEL=warning

# Database (matches the `db` service inside the compose network)
DB_DATABASE=ieepis_db
DB_USERNAME=ieepis_user
DB_PASSWORD=replace_with_a_long_random_password

# Sessions / mail / filesystem
SESSION_LIFETIME=120
FILESYSTEM_DISK=public
MAIL_MAILER=smtp           # or "log" while smoke-testing
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Optional tuning
NGINX_PORT=80              # internal — Traefik handles 443
REDIS_MAXMEMORY=256mb
```

Generate `APP_KEY` locally with `php artisan key:generate --show` and paste the value (including the `base64:` prefix).

---

## Step 4 — Wire up the domain and SSL

1. Open the **Domains** tab on the `nginx` service inside the compose stack.
2. Add `ieepis.yourdomain.com`.
3. Set the container port to **80** (the port `nginx` listens on inside the network).
4. Enable **HTTPS** + **Let's Encrypt**. Dokploy's Traefik will provision the certificate and redirect HTTP → HTTPS automatically.

> The compose file also exposes `${NGINX_PORT:-8080}:80` for direct host access; you can remove that mapping in production if you only want Traefik to reach nginx via the internal network.

---

## Step 5 — Deploy

Hit **Deploy**. Dokploy will:

1. Clone the repo.
2. Run `docker compose build` (Node stage compiles Vite assets, PHP stage installs Composer deps and bakes the source).
3. Start the six services (`nginx`, `app`, `worker`, `scheduler`, `db`, `redis`).
4. The `app` container runs `docker/entrypoint.sh`, which:
   - waits for PostgreSQL,
   - runs `php artisan migrate --force` (fails the deploy if migrations error),
   - refreshes the storage symlink,
   - rebuilds `config:cache`, `route:cache`, `view:cache`.

Tail the logs from the Dokploy UI (or `docker compose -f docker-compose.prod.yml logs -f app` on the server) to confirm a clean boot.

---

## Step 6 — First-time bootstrap

Open the **Terminal** tab for the `app` service and run:

```bash
# Seed the database (only if you have seeders for production data)
php artisan db:seed --force

# Create the first super-admin
php artisan make:filament-user
```

Storage and Filament caches are already handled by the entrypoint, so nothing else is required.

---

## Step 7 — Verify the stack

| Check | How |
| --- | --- |
| App responds | `curl -fsS https://ieepis.yourdomain.com/up` returns `OK` |
| Migrations applied | `php artisan migrate:status` inside the `app` container |
| Worker is running | `docker compose -f docker-compose.prod.yml ps worker` shows `(healthy)` |
| Scheduler is running | `docker compose logs scheduler` prints `Running scheduled command` lines every minute |
| Redis | `docker compose exec redis redis-cli ping` → `PONG` |
| Postgres | `docker compose exec db pg_isready` → `accepting connections` |

---

## Pushing local changes to production

Because the production stack is built from your Git repo, the cycle is:

| Change | Action |
| --- | --- |
| PHP / Blade / Filament code | `git push` → Dokploy redeploys → `app`, `worker`, `scheduler` rebuild from the new `app` target |
| `composer.json` / `composer.lock` | Same as above; the composer install layer is invalidated by the lockfile change |
| Front-end (`resources/`, JS, CSS) | Same as above; the Node `assets` stage re-runs `npm run build` |
| `Dockerfile` | Push, then trigger **Rebuild** in Dokploy (use **Force rebuild** to invalidate cache) |
| `docker-compose.prod.yml` | Push, then **Redeploy**; only changed services restart |
| `.env` only | Edit in Dokploy → **Restart** (no rebuild required) |
| Database migration | Push the migration; the entrypoint runs `migrate --force` automatically on the next boot |
| nginx config (`docker/nginx/prod.conf`) | Push, then **Redeploy** (it's baked into the `web` image) |

Local development does **not** use this Compose stack — run `php artisan serve` and `npm run dev` directly per the project's Decision 014.

---

## Step 8 — Backups

### Database

Dokploy's **Backups** tab on the `db` service supports scheduled `pg_dump` to S3-compatible storage. Recommended cadence: daily, retain 14 days.

Manual snapshot:

```bash
docker compose -f docker-compose.prod.yml exec db \
  pg_dump -U "$DB_USERNAME" "$DB_DATABASE" | gzip > ieepis-$(date +%F).sql.gz
```

### Uploaded files

The `app-storage` named volume holds everything under `storage/`. Back it up with:

```bash
docker run --rm -v ieepis_app-storage:/data -v "$PWD":/backup alpine \
  tar czf /backup/storage-$(date +%F).tgz -C /data .
```

---

## Troubleshooting

| Symptom | Likely cause |
| --- | --- |
| `502 Bad Gateway` from nginx | `app` container failed health check. `docker compose logs app` — usually a missing env var or migration error |
| Filament icons / CSS missing | Assets stage didn't run. Force-rebuild without cache |
| `SQLSTATE[08006]` or `connection refused` | `DB_HOST` must be `db`, not `localhost` or `127.0.0.1` |
| Queue jobs stuck | `docker compose logs worker`. Restart with `docker compose restart worker` |
| Storage uploads disappear after redeploy | You're writing into the image instead of the `app-storage` volume — check `FILESYSTEM_DISK=public` and that `storage/` is the named volume |
| `Class "Redis" not found` | The PHP redis extension wasn't built. Confirm the `Dockerfile` ran `pecl install redis && docker-php-ext-enable redis` |
| Permission denied on `storage/` | Run `docker compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache` |

---

## What this stack does **not** include

- **Octane / RoadRunner** — IEEPIS uses standard PHP-FPM. If you need persistent workers, swap the `app` command to `php artisan octane:start --server=swoole --host=0.0.0.0 --port=9000` and add the swoole extension to the Dockerfile.
- **Horizon** — the `worker` service runs `queue:work` directly. Switch to Horizon by changing the worker command and exposing its dashboard.
- **CDN / object storage** — files are served by nginx from the `app-storage` volume. For large media, reconfigure `FILESYSTEM_DISK=s3` and add S3 credentials.
