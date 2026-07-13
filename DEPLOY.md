# Deploying MedLedger

MedLedger is a single FrankenPHP container with a SQLite database on a persistent volume. The hourly `demo:reset` runs inside the container via `schedule:work`. Because SQLite is single-writer, the app **must stay at one replica** - never scale it horizontally.

The image is built and pushed to GHCR by CI (`.github/workflows/deploy.yml`) on every push to `main` and on `v*` tags. You deploy by pulling that image on your VPS and running it behind Traefik, which terminates TLS.


## Prerequisites

- A VPS with Docker Engine and the Docker Compose plugin.
- A running **Traefik** container that owns ports 80/443, with:
    - an external Docker network (this guide assumes it is named `traefik`),
    - a Let's Encrypt cert resolver (this guide assumes `letsencrypt`),
    - an HTTPS entrypoint (this guide assumes `websecure`). If yours differ, edit the placeholders in `docker-compose.yml` to match.
- DNS: an `A`/`AAAA` record for `demo.laravel-chronicle.dev` pointing at the VPS.
- This repository checked out on the VPS (for `docker-compose.yml`), or just copy that one file over.

## 1. Generate secrets locally

Run these on your machine and keep the output for the next step.

```bash
# Laravel app key
php artisan key:generate --show

# Chronicle signing key ring (Ed25519) - prints private + public key material
php artisan chronicle:key:generate
```

Generate **fresh** keys for production - never reuse the development keys from `.env.example`.

## 2. Create `.env.production` on the VPS

Next to `docker-compose.yml`, create `.env.production` (never commit it):

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

# Chronicle signing key ring (from `php artisan chronicle:key:generate`)
CHRONICLE_PRIVATE_KEY=...
CHRONICLE_PUBLIC_KEY=...
# Optional second key - enables the key-rotation panel (4d)
CHRONICLE_KEY2_PRIVATE_KEY=...
CHRONICLE_KEY2_PUBLIC_KEY=...

# CHRONICLE_TSA_URL=https://freetsa.org/tsr
# CHRONICLE_TSA_CERTIFICATE=storage/tsa/cacert.pem

# Matomo analytics (self-hosted on the same VPS). Cookieless + honours Do Not
# Track — no consent banner needed. Leave blank to disable tracking entirely.
MATOMO_URL=https://analytics.laravel-chronicle.dev
MATOMO_SITE_ID=1
```

`DB_DATABASE` points at `/data`, which is the persistent volume - the ledger survives redeploys. The entrypoint creates, migrates, and seeds it on first boot.

## 3. Authenticate to GHCR

The GHCR package is private by default, so the VPS must log in before it can pull. Create a GitHub Personal Access Token with the `read:packages` scope, then:

```bash
echo "$GHCR_TOKEN" | docker login ghcr.io -u <your-github-username> --password-stdin
```

Alternatively, make the package public in the repo's *Packages* settings and skip this step.

## 4. Deploy

From the directory containing `docker-compose.yml` and `.env.production`:

```bash
docker compose pull
docker compose up -d
```

On first boot the entrypoint caches config, runs `migrate --force`, and seeds the deterministic demo dataset; then supervisord starts FrankenPHP and the scheduler. Traefik picks up the router labels and issues the TLS certificate. 

Visit:

```
https://demo.laravel-chronicle.dev
```

To ship a new build, let CI push a fresh `latest`, then on the VPS:

```bash
docker compose pull && docker compose up -d
```

## Operating notes

- **Health check:** the container reports at `GET /up`; the compose healthcheck polls it.
- **Hourly reset:** `schedule:work` triggers `demo:reset` on the hour. Reset on demand:

  ```bash
  docker compose exec app php artisan demo:reset
  ```

- **Reset throttle:** the manual "Reset demo" button and the Integrity Lab throttles use the file cache, which is per-container and resets on redeploy - expected for a public sandbox.
- **Logs:** `docker compose logs -f app`.
- **Data:** the SQLite ledger lives in the `medledger_data` Docker volume. Back it up with `docker run --rm -v medledger_data:/data -v "$PWD":/backup alpine tar czf /backup/medledger-data.tgz /data`.
