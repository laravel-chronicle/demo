# Deploying MedLedger

MedLedger is a single FrankenPHP container with a SQLite database on a persistent
volume. The hourly `demo:reset` runs inside the container via `schedule:work`.
Because SQLite is single-writer, the app **must stay at one machine** — never scale
it horizontally.

## Primary path: Fly.io

### 1. Prerequisites

- [Fly CLI](https://fly.io/docs/flyctl/install/) installed and `fly auth login` done.
- This repository checked out locally.

### 2. Launch the app (without deploying yet)

```bash
fly launch --no-deploy --copy-config --name medledger-demo --region ams
```

`--copy-config` keeps the committed `fly.toml`. Adjust `--name`/`--region` to taste; update `app` and `APP_URL` in `fly.toml` if you change the name.

### 3. Create the SQLite volume

```bash
fly volumes create medledger_data --size 1 --region ams
```

The volume mounts at `/data`; `fly.toml` already points `DB_DATABASE` at `/data/database.sqlite`. The entrypoint creates and seeds the database on first boot.

### 4. Set the secrets

These are secrets (not committed env). Generate keys with the artisan commands, then set them:

```bash
# Laravel app key
fly secrets set APP_KEY="$(php artisan key:generate --show)"

# Chronicle signing key ring (Ed25519). Run locally, copy the printed values:
php artisan chronicle:key:generate
fly secrets set \
  CHRONICLE_PRIVATE_KEY="<printed private key>" \
  CHRONICLE_PUBLIC_KEY="<printed public key>"

# Optional second key (enables the key-rotation panel, 4d):
fly secrets set \
  CHRONICLE_KEY2_PRIVATE_KEY="<second private key>" \
  CHRONICLE_KEY2_PUBLIC_KEY="<second public key>"
```

### 5. (Optional) Enable real RFC 3161 anchoring (panel 4e)

Without these, the full-compromise panel shows an honest "configure a TSA" placeholder — never a fake pass. The freeTSA CA chain is committed at `storage/tsa/cacert.pem`.

```bash
fly secrets set \
  CHRONICLE_TSA_URL="https://freetsa.org/tsr" \
  CHRONICLE_TSA_CERTIFICATE="storage/tsa/cacert.pem"
```

### 6. Deploy

```bash
fly deploy
```

The build runs the Dockerfile (asset build → FrankenPHP runtime). On first boot the entrypoint seeds the deterministic demo dataset, then supervisord starts FrankenPHP and the scheduler. Open the live URL:

```bash
fly open
```

### Operating notes

- **Health check:** Fly polls `GET /up`.
- **Hourly reset:** `schedule:work` triggers `demo:reset` on the hour. To reset on
  demand: `fly ssh console -C "php /app/artisan demo:reset"`.
- **Reset throttle:** the manual "Reset demo" button and the Integrity Lab throttles
  use the file cache, which is per-container and resets on redeploy — expected for a
  public sandbox.
- **Logs:** `fly logs`.

## Notes for Laravel Forge / Laravel Cloud

The same environment variables apply. Two things to get right on a non-Fly host:

- **Persistent disk for SQLite.** Point `DB_DATABASE` at a path on a persistent disk
  so the ledger survives deploys.
- **Run the scheduler.** Add a scheduled job / cron entry that runs
  `php artisan schedule:run` every minute (Forge: *Scheduler* tab; Laravel Cloud:
  enable the scheduler) so the hourly `demo:reset` fires.

Generate fresh signing keys for production (`php artisan chronicle:key:generate`) —
never reuse the development keys from `.env.example`.
