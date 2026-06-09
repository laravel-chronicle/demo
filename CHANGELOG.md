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

### Removed

- Removed the starter-kit `welcome` view.
- Removed the starter-kit example tests.
