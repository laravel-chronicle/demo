# MedLedger - a Laravel Chronicle demo

MedLedger is a fictional clinic that shows what
[Laravel Chronicle](https://laravel-chronicle.github.io/docs/overview) does: a
**tamper-evident audit ledger**. Every clinical action is recorded in a hash-linked,
signed ledger, and the Integrity Lab lets you *try to break it* - then watch
verification localize the damage.

> **All data is synthetic** (planet-named patients, fake MRNs) and the demo **resets
> hourly**. It's a public sandbox: go ahead and click "tamper".

**▶ Live demo:** https://medledger-demo.fly.dev

<!-- TODO: record docs/tamper-panel.gif of panel 4a (alter an entry -> Verify localizes the break). -->

## What it shows

- **Patients** - view/edit/prescribe/amend records; each action is an audited Chronicle entry with before/after diffs and the acting clinician.
- **Ledger Explorer** - the live hash-linked ledger with a one-click integrity **Verify**.
- **Integrity Lab**
    - **4a Tampering simulator** - scrub or alter a real ledger entry with a raw write, then Verify localizes the break to the exact entry and names the reason.
    - **4b Full lifecycle** - generate activity -> checkpoint -> anchor -> export -> verify-export, each artifact shown.
    - **4c Auditor view** - a signed, downloadable compliance report plus an independently verifiable export bundle.
    - **4d Key rotation** - rotate the signing key and confirm pre-rotation checkpoints still verify under the retired key.
    - **4e Full-compromise demo** - with a real RFC 3161 TSA anchor, a full attacker compromise passes *offline* verification but **fails `--anchors`**, because the timestamp token binds the original digest.

## Run locally (two commands)

```bash
composer install && cp .env.example .env && php artisan key:generate
php artisan demo:reset && php artisan serve
```

Then open http://localhost:8000. `demo:reset` builds the deterministic synthetic
dataset and a verifiable ledger so every screen has substance immediately.

> Frontend assets: `npm install && npm run build` (or `npm run dev` while developing).

## Deploy

See [DEPLOY.md](DEPLOY.md) for a one-command Fly.io deploy (`fly deploy`) and notes
for Laravel Forge / Laravel Cloud.

## Add a showcase

The Integrity Lab is meant to grow. To add a panel:

1. Create a Livewire component under `app/Livewire/Lab/` (one panel per component:
   *explain -> run -> show artifacts -> reset*). Avoid naming a public property the same
   as an action method - see `tests/Feature/Lab/LivewireActionCollisionTest.php`.
2. Render it from the Integrity Lab host view and add a short "what this proves" intro.
3. Rate-limit any destructive action (reuse the `ThrottlesDestructiveActions` concern)
   and make it reversible via a reset path.
4. Cover the new flow with a Pest test and keep the suite green.

Use only the published `laravel-chronicle/core` API - Chronicle is a dependency here,
not a fork.

## License

MIT.
