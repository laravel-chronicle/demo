# MedLedger - a Laravel Chronicle demo

MedLedger is a fictional clinic that shows what [Laravel Chronicle](https://laravel-chronicle.github.io/docs/overview) does: a **tamper-evident audit ledger**. Every clinical action is recorded in a hash-linked, signed ledger, and the Integrity Lab lets you *try to break it* - then watch verification localize the damage.

> **All data is synthetic** (planet-named patients, fake MRNs) and the demo **resets hourly**. It's a public sandbox: go ahead and click "tamper".

**▶ Live demo:** https://demo.laravel-chronicle.dev

<!-- TODO: record docs/tamper-panel.gif of panel 4a (alter an entry -> Verify localizes the break). -->

## What it shows

- **Patients** - view/edit/prescribe/amend records; each action is an audited Chronicle entry with before/after diffs and the acting clinician.
- **Ledger Explorer** - the live hash-linked ledger with a one-click integrity **Verify**.
- **Audit panel** (`/audit`) - the [`laravel-chronicle/filament`](https://github.com/laravel-chronicle/filament) plugin: browse and cryptographically verify the ledger, inspect external anchors, see which signing key signed each entry (Active vs Retired), track crypto-shredding state, and generate verifiable exports and signed compliance reports. Read-only by construction.
    - **GDPR erasure** - switch to the **Admin Vega** persona to unlock the *Erase subject* and *Export* actions. Erasure destroys the subject's key and appends an audited `subject.erased` proof; existing entries are never altered and the chain still verifies.
- **Integrity Lab**
    - **4a Tampering simulator** - scrub or alter a real ledger entry with a raw write, then Verify localizes the break to the exact entry and names the reason.
    - **4b Full lifecycle** - generate activity -> checkpoint -> anchor -> export -> verify-export, each artifact shown.
    - **4c Auditor view** - a signed, downloadable compliance report plus an independently verifiable export bundle.
    - **4d Key rotation** - rotate the signing key and confirm pre-rotation checkpoints still verify under the retired key.
    - **4e Full-compromise demo** - with a real RFC 3161 TSA anchor, a full attacker compromise passes *offline* verification but **fails `--anchors`**, because the timestamp token binds the original digest.

## Run locally

> This repository is the source for the hosted demo at **demo.laravel-chronicle.dev**. It is not intended as a template to clone and deploy - but you can absolutely run it locally to explore. Because Chronicle **signs** and **encrypts** real cryptographic material, `.env.example` deliberately ships those secrets **empty**: you generate your own. Skipping step 2 means seeding will fail.

**1. Install**

```bash
composer install && cp .env.example .env && php artisan key:generate
npm install && npm run build
```

**2. Generate the Chronicle secrets** (required - `php artisan key:generate` only sets `APP_KEY`)

You need three secrets: two Ed25519 signing keypairs (the demo rotates keys, so it uses
two) and one payload-encryption key.

```bash
# Two signing keypairs - run twice, and copy each printed base64 keypair:
php artisan chronicle:key:generate
php artisan chronicle:key:generate

# One 32-byte payload-encryption key (crypto-shredding; NOT the app key):
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Fill them into `.env`:

```dotenv
CHRONICLE_PRIVATE_KEY=        # keypair 1, private (base64)
CHRONICLE_PUBLIC_KEY=         # keypair 1, public  (base64)
CHRONICLE_KEY2_PRIVATE_KEY=   # keypair 2, private (base64)
CHRONICLE_KEY2_PUBLIC_KEY=    # keypair 2, public  (base64)
CHRONICLE_ENCRYPTION_KEY=     # the 32-byte base64 key from above
```

`CHRONICLE_ACTIVE_KEY=chronicle-key-2` is already set: the demo signs new checkpoints with keypair 2 and keeps keypair 1 in the ring as the **retired** key, so the audit panel's key-rotation surfaces have both states to show.

**3. Seed and serve**

```bash
php artisan demo:reset && php artisan serve
```

Then open http://localhost:8000, and the audit panel at http://localhost:8000/audit. `demo:reset` builds the deterministic synthetic dataset and a verifiable ledger so every screen has substance immediately.

### External anchoring (optional locally)

`.env.example` enables a real RFC 3161 timestamp authority (`CHRONICLE_ANCHORING_ENABLED=true`, `CHRONICLE_TSA_URL=https://freetsa.org/tsr`), which is what the hosted demo runs - panel **4e** needs a genuine anchor to be meaningful, and the seeder **never fakes one**.

That means seeding performs a live HTTP call to the TSA. If you're offline, behind a firewall, or in CI, that call fails and seeding stops. To run without it, disable anchoring in `.env`:

```dotenv
CHRONICLE_ANCHORING_ENABLED=false
CHRONICLE_TSA_URL=
```

Everything else still works - checkpoints are simply, and honestly, **unanchored**, and the panel's anchor surfaces show them as such.

## Deploy

The hosted demo runs on a VPS at **demo.laravel-chronicle.dev**. See
[DEPLOY.md](DEPLOY.md) for deployment notes.

## Add a showcase

The Integrity Lab is meant to grow. To add a panel:

1. Create a Livewire component under `app/Livewire/Lab/` (one panel per component: *explain -> run -> show artifacts -> reset*). Avoid naming a public property the same as an action method - see `tests/Feature/Lab/LivewireActionCollisionTest.php`.
2. Render it from the Integrity Lab host view and add a short "what this proves" intro.
3. Rate-limit any destructive action (reuse the `ThrottlesDestructiveActions` concern) and make it reversible via a reset path.
4. Cover the new flow with a Pest test and keep the suite green.

Use only the published `laravel-chronicle/core` API - Chronicle is a dependency here, not a fork.

## License

MIT.
