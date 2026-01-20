# Copilot / AI Agent Instructions

Purpose: Quickly orient an AI coding agent to the LINE OA Manager codebase and provide precise, actionable guidance for edits.

- **Big picture:** This is a monolithic PHP application with many page endpoints living at the repository root (e.g. `index.php`, `webhook.php`, `admin-users.php`). Business logic is implemented in PHP classes under `classes/`, `app/` or `modules/`. A separate Node.js WebSocket service (real-time inbox) lives in the repo root and is managed via `package.json`.

- **Where to look first:**
  - Project overview: [README.md](README.md)
  - Entrypoints: [webhook.php](webhook.php), [index.php](index.php), `install/`
  - Core classes: [classes/LineAPI.php](classes/LineAPI.php) (example of external API wrapper)
  - Configuration: [config/config.php](config/config.php)
  - Composer autoload / scripts: [composer.json](composer.json)
  - Node service: [package.json](package.json) and `websocket-server.js`
  - Tests config: [phpunit.xml](phpunit.xml) and `tests/bootstrap.php`

- **How to run & test (concrete commands):**
  - Install PHP deps: `composer install` from project root.
  - Run PHP unit tests: `composer test` or `vendor/bin/phpunit -c phpunit.xml`.
  - Start local PHP dev server (quick manual testing): `php -S localhost:8000` from project root.
  - Node websocket server: `npm install` then `npm run dev` (development) or `npm run start` (production). Use `npm run pm2:start` to run under `pm2` per `package.json`.
  - Deployment scripts (examples in repo): run `./deploy-to-cny.sh` or `./deploy-to-emp.sh` to mirror existing deployments; note both target SSH on port `9922` and perform `scp`/`git pull` + migration steps.

- **Project-specific conventions / patterns:**
  - Many HTTP endpoints are implemented as single PHP files in repo root — treat these as controllers/views. Small changes to UI or route wiring often happen directly in these files.
  - Reusable logic is placed in `classes/` and namespaced via PSR-4 (see `composer.json` mapping: `Classes\\` -> `classes/`). Prefer adding helpers there for reuse.
  - Configuration is file-based (`config/config.php`) using `define()` constants rather than env-first patterns.
  - Database migrations and SQL are in `database/` (schema and ad-hoc SQL imports). Tests live under `tests/` and phpunit suites are declared in [phpunit.xml](phpunit.xml).
  - Webhook usage: webhook URLs include an `account` query param (example in [README.md](README.md)). When changing webhook behavior, check both `webhook.php` and the `LineAPI` wrapper.

- **Integration points & external dependencies:**
  - LINE Messaging API: wrappers in `classes/` and webhook handler at `webhook.php`.
  - Node WebSocket service connects to MySQL/Redis (`package.json` dependencies) and is used by inbox/real-time components.
  - Deploy scripts use SSH+SCP (port 9922) and run on remote servers; changes to front-end LIFF files often deploy via `deploy-to-cny.sh`.

- **Editing guidance for AI agents:**
  - Small feature/fix: prefer adding or updating methods under `classes/` and then make minimal changes to the corresponding root PHP page.
  - Large refactor: avoid moving many root files at once — the app assumes file-per-page patterns and global config constants.
  - Tests: run `composer test` after changes and update `tests/` alongside behavior modifications. PHPUnit bootstrap is `tests/bootstrap.php`.
  - When modifying webhook flows, include example request/response and mention `account` parameter handling.

- **Examples to cite in PRs or commits:**
  - Adding a new API helper: update `classes/` and `composer.json` autoload if adding a new namespace.
  - Fixing LIFF JS: update files under `liff/` and use `./deploy-to-cny.sh` to upload specific files (script enumerates the exact files it sends).

If any section is unclear or you want more detail (e.g., common DB tables, exact class to modify for a feature), tell me which area to expand. I'll iterate on this file.
