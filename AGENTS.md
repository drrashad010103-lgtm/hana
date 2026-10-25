# RS_Own (النسخة الحرة)

PHP 8.2 + MySQL (`rs_own`) app under `app/`. No composer/git; tests run via `tests/run_all.ps1` and `tools/phpunit.phar`.

## Run

- Dev server: `start_rsown.bat` (port 8082) or `C:\phplocal\php\php.exe -S 0.0.0.0:8082 -t D:\RS_Own\app`, then open `/pages/main.php`.
- PHP binary: `C:\phplocal\php\php.exe`.

## Setup

- Copy `app/config.sample.php` → `app/config.php` (DB host/name/user/pass; charset utf8mb4). Secrets (EasyOrders key) live in repo-root `secrets.php`.
- Import a dump into MySQL `rs_own`. `LOAD DATA` (load.sql) does not work on shared hosting — rely on dumps.
- No `app/setup.php` is present; use an existing account from the imported database.

## Architecture

- Web root is `app/` (not repo root). Entry pages are direct files in `app/pages/*.php` and `app/index.php`.
- `app/lib/db.php` connects to `rs_own` only and (non-CLI) loads `guard.php`; `app/lib/auth.php` handles login (bcrypt + legacy plaintext with auto-upgrade); `app/lib/cfg.php` holds settings/perm map/module registry; `app/lib/seq.php` safe sequences.
- Guard runs automatically via `app/router.php` (built-in server) or `db.php` (Apache). Pages `require` db.php (not `_once`) then `require_once` cfg/auth; lib functions are `function_exists`-guarded — keep that pattern when adding libs.
- Router at `app/router.php` forces guard before any PHP file; do not bypass.

## Languages / i18n

- Supported UI languages: `ar` (default), `en`, `ur`, `hi`, `bn`. `ur` and `ar` are RTL; the others LTR.
- `app/lib/i18n.php` stores the per-user choice (`lang_user_<uid>`) and the global default (`ui_lang`) in `app_settings`; `app/pages/lang.php` changes/saves it; `app/assets/i18n.js` translates client-side after load.
- `app/lib/guard.php` injects `assets/i18n.css` and `assets/i18n.js` into authenticated HTML pages. Login loads them manually. API output and non-HTML output must not be translated.
- Curated UI strings live in `app/assets/i18n/source.tsv` (5 tab-separated columns: ar/en/ur/hi/bn). After editing it, run `C:\phplocal\php\php.exe D:\RS_Own\tools\build_i18n.php` to rebuild `en.json`, `ur.json`, `hi.json`, `bn.json`.
- `auto_source.json` / `auto_<lang>.json` are machine-seeded translations for the remaining static UI strings; curated TSV values always override them. Prefer adding/fixing important labels in `source.tsv`.
- `i18n_t($arabic, $lang)` provides server-side lookup for pages such as `print_inv.php` where mixed labels/numbers make client-side replacement unreliable. Dictionary JSON files are local; no runtime translation network calls.
- `print_inv.php` prints Arabic by default regardless of UI language; use `?lang=en|ur|hi|bn` or the print-toolbar selector to override. Keep this separation so switching the dashboard does not silently change receipt language.
- Language UI is available from the floating globe selector on every page and from the Program Language card in `pages/settings.php` (admin can set the global default).

## Hard rules

- `RS_RS` writes are allowed only through `app/pages/bridge.php` / `app/lib/bridge.php` using the restricted `rs_bridge` SQL login. Never delete invoices, items, or customers; only `Inv_Sub` line replacement is allowed. All other code stays read-only toward `RS_RS`.
- Table names must stay lowercase (`inv`, `currencies`, …) for Linux case-sensitivity.
- Do not delete/modify `app/.htaccess` protections (config/lib/sql denied).
- `admin` user is always fully permitted; any other user with no `user_perms` rows is denied (fail-closed) — grant at least one `m_*` perm.
- Sensitive invoice flags (`cfg_invoice_flags_from_row`) are fail-closed: missing row, read error, or NULL flag = 0 (deny); only explicit `1` allows (admin overrides).

## Sync bridge

- `tools/sync_rs_to_own.php` pulls from `RS_RS` via sqlcmd (read-only) into `rs_own`; invoked by `tools/run_sync_daily.bat` (scheduled daily 07:30). The `rs_last_proc` watermark must not advance past failed invoice imports; `--dry` must not write sync markers or create the sync table.
- `--invoices-only` skips the full item refresh for the manual pull.
- `pages/bridge.php` is the manual UI: an import button pulls new remote items/customers and invoices; an export button pushes branch 99 invoices plus local-only items/customers. No background loop or Startup launcher is used.
- Imported remote invoices stay `branch=1` and are read-only in `sale_rs.php` / `buy_rs.php` (view + print only). Invoice search/load includes branches `1` and `99`; never let a local save/delete target branch 1, and never re-export branch 1 as if it were local.
- `bridge_run_import()` calls `sync_rs_to_own.php --invoices-only --sql-auth`; `--sql-auth` makes the CLI use the `rs_bridge` SQL login so it works under the web server instead of relying on Windows auth.
- `RS_RS` is the master. Local branch 99 invoice numbers are reserved at `10000000+` to avoid colliding with remote numbering.
- First remote-write backup: `D:\RS_Own\backups\RS_RS_20261021_182533.bak`. Dedicated login is `rs_bridge` (INSERT/UPDATE, SELECT, `Inv_Sub` DELETE only).

## Verification

- Lint changed files: `C:\phplocal\php\php.exe -l <file>`. Full gate: `powershell -File tests/run_all.ps1` (lint + `*_test.php` + restore latest backup to temp DB `rs_own_test_<guid>` + server on :8099 + smoke all pages + E2E sale/buy + CSRF; drops only the temp DB).
- PHPUnit (unit + read-only DB): `C:\phplocal\php\php.exe tools/phpunit.phar -c phpunit.xml` from repo root (bootstrap `tests/bootstrap.php`; DB suite needs MySQL `rs_own`).
- Reproduce redeclare errors with `php -r "require '...'; require '...';"` when touching lib files.