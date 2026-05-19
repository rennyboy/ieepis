# Docker Hybrid Dev Workflow

> Hybrid model: Docker runs the app stack; the host runs `php artisan`, `composer`, `npm`, `psql` directly. The Docker DB port `5432` is published to the host so both runtimes hit the same Postgres. Never prefix commands with `vendor/bin/sail`.

## Stack management

```bash
docker compose up -d              # Start nginx + app + worker + scheduler + db + redis
docker compose down               # Stop and remove containers (volumes keep data)
docker compose logs -f app        # Tail app logs
docker compose restart app        # After .env.docker changes
docker compose exec app bash      # Shell into app container
```

## Host artisan (DB at 127.0.0.1:5432, served by Docker `db` service)

```bash
php artisan migrate
php artisan tinker
php artisan test
composer install                  # Host vendor (separate from container vendor-data volume)
```

## Cache hygiene

`bootstrap/cache/` and `storage/framework/` are container-private named volumes — host writes never collide with container writes. In dev, the entrypoint skips `config:cache`/`route:cache`/`view:cache`/`event:cache` (gated on `APP_ENV != local`).

```bash
php artisan optimize:clear        # Host bootstrap/cache/ only
docker compose exec app php artisan optimize:clear  # Container app-cache volume only
```

## Database

```bash
psql -h 127.0.0.1 -U laravel -d ieepis_db          # Via published port
docker compose exec db psql -U laravel ieepis_db   # Direct from container
```

## Asset building (always on host — no container rebuild needed)

```bash
npm run dev
npm run build
```

## Env files

- `.env` — host runtime (gitignored). `DB_HOST=127.0.0.1`.
- `.env.docker` — container runtime (committed). `DB_HOST=db`. Loaded via `env_file:` in compose.
- `APP_KEY` lives in `.env`; compose interpolates it into the container so encrypted DB fields stay decryptable in both runtimes.
