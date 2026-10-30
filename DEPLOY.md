# Deploying MedLedger

MedLedger is a single FrankenPHP container with a SQLite database on a persistent volume. The hourly `demo:reset` runs inside the container via `schedule:work`. Because SQLite is single-writer, the app **must stay at one replica** - never scale it horizontally.

CI (`.github/workflows/deploy.yml`) builds the image and pushes it to GHCR (`ghcr.io/laravel-chronicle/demo`) on every push to `main` and on `v*` tags. The server pulls new images itself on a timer; CI never logs in to the server.

## Prerequisites

- A Docker host set up like [quietsysadmin/stacks `base/`](https://github.com/quietsysadmin/stacks/tree/main/base): SSH keys only, ufw, unattended upgrades, Docker Engine with the Compose plugin.
- **Traefik** as the only container that publishes ports (80/443), with:
    - an external Docker network named `proxy`,
    - a `websecure` entrypoint,
    - a Let's Encrypt cert resolver named `le`.

  If yours differ, edit the network and labels in `docker-compose.yml`.
- DNS: an `A` record for `demo.laravel-chronicle.dev` pointing at the server.
- The GHCR package is public, so pulling needs no login.

## 1. Generate secrets locally

Run these on your machine and keep the output for the next step. Generate **fresh** values for production; never reuse development keys.

```bash
# Laravel app key
php artisan key:generate --show

# Two Chronicle signing keypairs (Ed25519): the demo rotates keys, so it uses two.
# Run twice and keep each printed private + public key.
php artisan chronicle:key:generate
php artisan chronicle:key:generate

# Payload-encryption key for crypto-shredding (32 bytes, base64). NOT the app key.
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Store them in a password manager. The demo data resets every hour, so the secrets are the only thing worth keeping.

## 2. Create `.env.production` on the server

In `/srv/medledger`, next to `docker-compose.yml` (never commit it, keep it `chmod 600`):

```dotenv
APP_NAME=MedLedger
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demo.laravel-chronicle.dev
APP_KEY=base64:...            # from `php artisan key:generate --show`

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=sqlite
DB_DATABASE=/data/database.sqlite

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

CHRONICLE_UI_ENABLED=true

# Signing key ring: keypair 1 is the retired key, keypair 2 signs new checkpoints.
# Without CHRONICLE_ACTIVE_KEY the config falls back to a dev key name.
CHRONICLE_PRIVATE_KEY=...
CHRONICLE_PUBLIC_KEY=...
CHRONICLE_KEY2_PRIVATE_KEY=...
CHRONICLE_KEY2_PUBLIC_KEY=...
CHRONICLE_ACTIVE_KEY=chronicle-key-2

# Crypto-shredding (GDPR erasure panel). Off unless set.
CHRONICLE_ENCRYPTION_ENABLED=true
CHRONICLE_ENCRYPTION_KEY=...

# External anchoring with a real RFC 3161 TSA (panel 4e). Off unless set.
CHRONICLE_ANCHORING_ENABLED=true
CHRONICLE_TSA_URL=https://freetsa.org/tsr

# Matomo analytics: cookieless and honours Do Not Track. Leave blank to disable.
MATOMO_URL=
MATOMO_SITE_ID=
```

`DB_DATABASE` points at `/data`, the persistent volume, so the ledger survives redeploys. The entrypoint creates, migrates and seeds it on first boot.

## 3. Deploy

```bash
sudo install -d -o ops -g ops /srv/medledger && cd /srv/medledger
curl -fsSLO https://raw.githubusercontent.com/laravel-chronicle/demo/main/docker-compose.yml
# create .env.production (step 2), then:
docker compose pull
docker compose up -d
docker compose logs -f app
```

On first boot the entrypoint caches config, runs `migrate --force` and seeds the deterministic demo dataset; then supervisord starts FrankenPHP and the scheduler. Traefik picks up the router labels and requests the certificate.

```bash
curl -sI https://demo.laravel-chronicle.dev | head -1
```

## 4. Behind Cloudflare

The demo runs with Cloudflare's proxy (orange cloud) in front, SSL mode **Full (strict)**:

- Get the first certificate with the record set to **DNS only**, then switch the proxy on.
- Traefik renews with the HTTP challenge through the proxy. That works as long as Cloudflare doesn't redirect `http://demo.laravel-chronicle.dev/.well-known/acme-challenge/*` to HTTPS (Always Use HTTPS off for this host, or a rule that skips that path). Traefik already redirects everything else to HTTPS itself.

## 5. Updates: the server pulls

A systemd timer on the server runs `docker compose pull` and `docker compose up -d` every 15 minutes. `up -d` only recreates the container when the image changed.

`/etc/systemd/system/medledger-update.service`:

```ini
[Unit]
Description=Pull and restart MedLedger if a new image is out
Wants=network-online.target
After=network-online.target docker.service

[Service]
Type=oneshot
User=ops
WorkingDirectory=/srv/medledger
ExecStart=/usr/bin/docker compose pull --quiet
ExecStart=/usr/bin/docker compose up -d
ExecStartPost=/usr/bin/docker image prune --force
```

`/etc/systemd/system/medledger-update.timer`:

```ini
[Unit]
Description=Check for a new MedLedger image every 15 minutes

[Timer]
OnBootSec=5min
OnUnitActiveSec=15min

[Install]
WantedBy=timers.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now medledger-update.timer
journalctl -u medledger-update -n 20
```

To ship a change: merge to `main`, wait for CI, and the server picks it up within 15 minutes. Or run `sudo systemctl start medledger-update` to pull now.

## Operating notes

- **Health check:** the container reports at `GET /up`; the compose healthcheck polls it.
- **Hourly reset:** `schedule:work` triggers `demo:reset` on the hour. Reset on demand:

  ```bash
  docker compose exec app php artisan demo:reset
  ```

- **Reset throttle:** the manual "Reset demo" button and the Integrity Lab throttles use the file cache, which is per-container and resets on redeploy - expected for a public sandbox.
- **Logs:** `docker compose logs -f app`.
- **Data:** the SQLite ledger lives in the `medledger_data` Docker volume and resets hourly, so it isn't backed up. Back up the secrets (step 1) instead.
