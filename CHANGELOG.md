# Changelog

All notable changes to `laravel-chronicle/demo` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/).

Semantic versioning applies from **v1.0.0** onwards. Pre-1.0 releases may contain
breaking changes between any two versions — see upgrade notes per version.

## [Unreleased]

### Added

- Installed `laravel-chronicle/core ^1.11`; published config + migrations and migrated the ledger tables.
- Enabled the read-only Chronicle UI (`CHRONICLE_UI_ENABLED=true`) with `web`-only middleware (no auth in the demo).
- Generated a dev Ed25519 signing keypair wired via `CHRONICLE_PRIVATE_KEY` / `CHRONICLE_PUBLIC_KEY`.
- Added no-auth demo personas (Dr. Reyes / Nurse Okoro / Admin Vega) with a session-backed `ResolveDemoPersona` middleware and `/persona` switch route.
- Added the base Tailwind layout with a persistent "fictional data, resets hourly" banner, top nav, and a persona switcher.
- Added stub pages for the Patients, Ledger, Integrity Lab, Auditors, and How-it-works screens.
- Added the home landing page with the one-paragraph pitch and links to the repo and docs.
- Added a scaffold smoke test asserting the app boots and every screen renders.
- Added the `Clinician` model and a seeder mapping the demo personas (Dr. Reyes / Nurse Okoro / Admin Vega) to real audited actors, resolved per-request by `CurrentClinician`.
- Added `Patient`, `Encounter`, and `Prescription` models with automatic Chronicle auditing (create/update/delete + field diffs), attributed to the active clinician, plus deterministic planet-named synthetic seed data.
- Added the Patients list screen (Livewire) linking through to each patient's detail page.
- Added the Patient detail screen that records an explicit `patient.viewed` access event on view and renders the patient's live Chronicle audit trail (with actor, action, time, and diffs).
- Added inline edit, prescribe, and amend actions on the Patient detail screen that produce audited Chronicle entries with correct before/after diffs (amendments record a dedicated `patient.amended` action and never a generic update).
- Extended the scaffold smoke test to cover a seeded patient detail rendering its live audit trail.
- Added a `LedgerVerifier` service that runs Chronicle's integrity verification and returns a view-friendly `VerificationOutcome` (valid/checked count, or the failure code, breaking entry id, and a plain-English reason).
- Added the Ledger Explorer screen: a paginated Livewire table of the Chronicle ledger showing chain position, action, actor, subject, and time.
- Added a Verify button to the Ledger Explorer that runs Chronicle integrity verification and renders a clear ✓ valid (with entry count) or ✗ broken-at-entry banner with the failure reason.
- Linked the Ledger Explorer to Chronicle's canonical read-only Blade UI for the package's native entry browser.
- Extended the scaffold smoke test to cover the Ledger Explorer rendering seeded entries with the Verify control.
- Added the Integrity Lab host screen at `/lab`: a full-page Livewire shell introducing the four interactive panels (tampering simulator, full lifecycle, auditor view, key rotation).
- Added Integrity Lab panel 4a (tampering simulator): pick a real audit entry, scrub it (raw DELETE) or alter it (raw UPDATE), and watch Verify localize the break to the exact entry with a before/after view and a one-click ledger reset.
- Added Integrity Lab panel 4b (full lifecycle): a stepper that generates activity, creates a signed checkpoint, anchors it (NullAnchor, labelled non-production), exports a verifiable dataset, and verifies the export — each step rendering its artifact, with reset.
- Added Integrity Lab panel 4c (auditor view): generate a date-range signed compliance report (with downloadable HTML and a signature-valid badge) plus an export bundle with an independent verify badge.
- Added Integrity Lab panel 4d (key rotation): show the signing key ring, rotate to a second key (creating a boundary checkpoint), and verify that pre-rotation checkpoints still validate under the retired key while new checkpoints use the new key.
- Extended the scaffold smoke test to cover the Integrity Lab rendering all four interactive panels.
- Configured the RFC 3161 TSA anchor (`Rfc3161TimestampAnchor`) against the free public freeTSA.org TSA, reading `CHRONICLE_TSA_URL` / `CHRONICLE_TSA_CERTIFICATE` from the environment, and shipped freeTSA's CA chain at `storage/tsa/cacert.pem` for offline token verification.
- Added an `App\Support\TsaAnchoring` gate that reports anchoring "configured" only when the provider is registered, a TSA URL is set, and the verification certificate exists on disk (so panel 4e shows an honest placeholder instead of a fake pass when no TSA is available). Anchoring is performed explicitly by the lab, not auto-dispatched on every checkpoint.
- Added Integrity Lab panel 4e (full-compromise demo): builds a real RFC 3161 TSA-anchored ledger, then simulates an attacker with database and signing-key access — rewriting an entry's payload, recomputing the whole hash chain, and re-signing every checkpoint with a valid ring key. Offline verification (`--checkpoints-only`) passes the forgery, but verifying `--anchors` fails at the first anchored checkpoint because the TSA token binds the original digest. Shows an honest "configure a TSA" placeholder when no anchor is configured, and resets cleanly.
- Added `LabSandbox::forgetAnchors()` to remove a checkpoint's external-anchor receipts during panel reset.
- Extended the scaffold smoke test to cover the Integrity Lab rendering all five panels, including panel 4e's honest "configure a TSA" placeholder when no anchor is configured.
- Added the `demo:reset` artisan command: rebuilds the schema (`migrate:fresh`), reseeds the deterministic synthetic clinic dataset, creates two signed checkpoints, anchors the latest one when a TSA is configured, then verifies the ledger and prints a stable summary (non-zero exit if the rebuilt ledger does not verify).
- Added a `LedgerCheckpointSeeder` (wired into `DatabaseSeeder`) that builds a small deterministic checkpoint history over the seeded activity so the Ledger explorer and Integrity Lab have substance immediately, anchoring the latest checkpoint only when `TsaAnchoring::configured()` (never a fake anchor).

### Removed

- Removed the starter-kit `welcome` view.
- Removed the starter-kit example tests.
