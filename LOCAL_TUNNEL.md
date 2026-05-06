# Local HTTPS Tunnel for Mobile Testing (Cloudflared)

Test the IEEPIS app on a real phone — including the **camera QR scanner** — without deploying anywhere. The phone needs HTTPS to expose `navigator.mediaDevices`; cloudflared gives you a free, throwaway `https://*.trycloudflare.com` URL pointing at your local Docker stack.

> One-shot tunnel. URL changes every run. Only use for testing.

---

## Prerequisites (one-time)

```bash
# Install cloudflared (Ubuntu / Debian / MX Linux)
curl -L --output cloudflared.deb \
  https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared.deb
rm cloudflared.deb

# Verify
cloudflared --version
```

You also need:

- Docker + Docker Compose (already used by `docker-compose.prod.yml`)
- An `.env` at the project root with at least `APP_KEY` set
  (generate once: `php artisan key:generate --show` and paste the value into `.env`)

---

## Step 1 — Bring up the production stack locally

The same `docker-compose.prod.yml` you'll deploy to Dokploy runs locally. nginx publishes on host port `8080`.

```bash
cd ~/Downloads/projects/ieepis

# First time only — builds the multi-stage image
docker compose -f docker-compose.prod.yml build

# Start everything
docker compose -f docker-compose.prod.yml up -d

# Confirm 6 services healthy
docker compose -f docker-compose.prod.yml ps
```

Expected:

```
NAME               STATUS
ieepis-app         Up (healthy)
ieepis-db          Up (healthy)
ieepis-nginx       Up (healthy)
ieepis-redis       Up (healthy)
ieepis-scheduler   Up
ieepis-worker      Up (healthy)
```

Smoke test:

```bash
curl -fsS http://localhost:8080/up
# should print {"status":"OK"}
```

---

## Step 2 — Open the cloudflared quick tunnel

In a **separate terminal** (keep it running while you test):

```bash
cloudflared tunnel --url http://localhost:8080 --no-autoupdate
```

After ~5 seconds cloudflared prints a banner like:

```
2026-05-06T08:02:59Z INF |  https://random-words-here.trycloudflare.com  |
```

Copy that URL. That's your phone-accessible HTTPS endpoint.

> **Important:** The URL changes every time you start the tunnel. Re-run Step 3 whenever you restart cloudflared.

---

## Step 3 — Tell Laravel about the tunnel URL

Filament generates absolute URLs for assets, redirects, and Livewire AJAX. If `APP_URL` doesn't match the URL you're loading, the UI loads broken (mixed-content blocks CSS/JS) and the login redirects break.

Fix: recreate the PHP services with `APP_URL` overridden to the tunnel URL:

```bash
# Replace the URL with whatever cloudflared printed
TUNNEL_URL="https://random-words-here.trycloudflare.com"

APP_KEY="$(grep -E '^APP_KEY=' .env | cut -d= -f2-)" \
APP_URL="$TUNNEL_URL" \
docker compose -f docker-compose.prod.yml up -d --force-recreate app worker scheduler nginx
```

This takes ~10 seconds. Verify the new env applied:

```bash
docker compose -f docker-compose.prod.yml exec app sh -c 'echo $APP_URL'
# should print the tunnel URL
```

Verify the rendered HTML now references HTTPS for everything:

```bash
curl -sL "$TUNNEL_URL/admin/login" | grep -oE 'http://[^"'\'' ]*' | grep -v "w3.org"
# should print nothing (no stray http:// URLs)
```

---

## Step 4 — Open the URL on your phone

1. Open `https://random-words-here.trycloudflare.com/admin/login` on your phone (any modern browser).
2. Sign in with your seeded super-admin (`admin@deped.gov.ph` / `P@ssw0rd123` if you ran `php artisan db:seed` locally — change in production).
3. Navigate to **QR Scanner** in the sidebar.
4. Tap **Open Camera**. The browser prompts for camera permission. Grant it.
5. Scan an equipment QR. You should be redirected to the equipment edit page.

If the camera still says "not supported", you've either:

- Loaded the page over `http://...` instead of the tunnel `https://...` URL, or
- Opened it inside the Facebook / Instagram in-app browser (use Chrome / Safari instead).

---

## Step 5 — Tear down

When you're done:

```bash
# In the cloudflared terminal: Ctrl-C
# Then optionally stop the Docker stack
docker compose -f docker-compose.prod.yml down

# Or keep the stack running and only kill the tunnel
pkill -f "cloudflared tunnel --url"
```

The `db-data`, `redis-data`, `app-storage`, and `app-bootstrap-cache` volumes persist between runs. To wipe them too:

```bash
docker compose -f docker-compose.prod.yml down -v
```

---

## Troubleshooting

| Symptom | Cause | Fix |
| --- | --- | --- |
| `502 Bad Gateway` on the tunnel URL | Docker stack not running, or nginx not on `8080` | `docker compose -f docker-compose.prod.yml ps` — make sure all services are healthy |
| UI loads but CSS / icons missing | `APP_URL` still points at `localhost`, not the tunnel | Re-run Step 3 |
| `419 PAGE EXPIRED` after login | Session cookie domain mismatch | Already handled by `SESSION_SECURE_COOKIE=true` in compose; re-run Step 3 |
| Camera page says **not supported** | Page is HTTP, not HTTPS | Use the `https://*.trycloudflare.com` URL exactly. Localhost over USB also works (see below). |
| "You do not have permission to view this record" after scanning | Logged-in user is `school-admin` whose employee record was wiped — `school_id` is null | Log in as the seeded super-admin (`admin@deped.gov.ph`); the policy now grants full access via `Gate::before`. Re-link the school-admin's employee if you need that account. |
| Service worker keeps serving stale JS on the phone | Old SW cache | On the phone: clear site data, or change `CACHE_NAME` in [public/sw.js](public/sw.js) |
| Tunnel works but login loops | Container env not refreshed | Run `docker compose -f docker-compose.prod.yml restart app` after Step 3 |
| Worker container is unhealthy | `pgrep` missing | Already fixed — rebuild with `docker compose -f docker-compose.prod.yml build app` |

---

## Alternative: USB port forwarding (Android only, no internet)

If you don't want a public URL, Chrome's USB port forwarding lets your phone hit `http://localhost:8080` over the cable, which counts as a secure context:

1. Plug the phone in via USB. Enable **USB debugging** in Developer Options.
2. On the laptop open `chrome://inspect/#devices` → click **Port forwarding**.
3. Add `8080` ↔ `localhost:8080`. Check **Enable port forwarding**.
4. On the phone open `http://localhost:8080`. Camera works because `localhost` is treated as secure.

No `APP_URL` change needed — the phone literally sees `localhost`.

---

## Why HTTPS is required for the camera

Mobile browsers hide `navigator.mediaDevices` (the API the QR scanner library uses) on every non-secure origin. Rules are identical across Chrome, Safari, Firefox, Edge:

| Origin | `mediaDevices` exposed? |
| --- | --- |
| `https://anything` | yes |
| `http://localhost`, `http://127.0.0.1` | yes (special exemption) |
| `http://192.168.x.x:8080` | **no** |
| `http://your-domain.com` | **no** |
| Inside Facebook / Messenger / Instagram in-app browser | usually **no** |

There's no flag a normal user can flip. The fix is always: serve the page over HTTPS or `localhost`.
