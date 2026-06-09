# Changelog

All notable changes to `laravel-chronicle` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/).

Semantic versioning applies from **v1.0.0** onwards. Pre-1.0 releases may contain
breaking changes between any two versions — see upgrade notes per version.

## [Unreleased]

### Added
- Installed `laravel-chronicle/core ^1.11`; published config + migrations and migrated the ledger tables.
- Enabled the read-only Chronicle UI (`CHRONICLE_UI_ENABLED=true`) with `web`-only middleware (no auth in the demo).
- Generated a dev Ed25519 signing keypair wired via `CHRONICLE_PRIVATE_KEY` / `CHRONICLE_PUBLIC_KEY`.
