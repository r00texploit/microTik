# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

PHPNuxBill: a PHP/MySQL billing system for Mikrotik Hotspot/PPPoE and FreeRADIUS (vouchers, customer self-registration, balance, auto-renewal, payment gateways, SMS/WhatsApp/Telegram notifications). Plain procedural PHP + static classes, Smarty templates, Idiorm ORM (`system/orm.php`). No framework, no build step, no test suite, no linter config. README states PHP 8.2 minimum (the `Dockerfile` still uses `php:7.4-apache`).

## Commands

- Syntax check (the only automated verification available): `php -l path/to/file.php`
- Run the expiry/renewal cron: `php system/cron.php` (uses a lock file in `system/cache/router_monitor.lock`); reminders: `php system/cron_reminder.php`
- Docker: `Dockerfile` + `docker-compose.example.yml` (copy to `docker-compose.yml`, which is gitignored)
- Fresh install: browse to `/install/` (schema in `install/phpnuxbill.sql`, `install/radius.sql`); it writes `config.php` (gitignored — template is `config.sample.php`)

## Request lifecycle

1. `index.php` → captures `nux-*` query params (mac/ip/router from the Mikrotik captive portal) into session → `system/boot.php`.
2. `init.php` (shared by web, API and cron): registers the autoloader (`Foo_Bar` → `system/autoload/Foo/Bar.php`), loads `config.php`, configures ORM (plus a second `'radius'` connection when RADIUS is enabled), **includes every `system/plugin/*.php`** (errors silently swallowed), loads `tbl_appconfig` rows into `$config`/`$_c`, loads the language JSON, and defines global helpers (`_post`, `_get`, `_auth`, `_admin`, `r2` redirect-with-flash, `_alert`, `_log`, `showResult`, `getUrl`).
3. `boot.php` sets up Smarty, then routes `?_route=a/b/c` (or pretty path) by splitting on `/`: `$routes[0]` selects `system/controllers/<name>.php`, which is `include`d in global scope. Controllers conventionally `switch ($routes[1])` for actions and call `_admin()`/`_auth()` themselves for access control; role checks use `$admin['user_type']` (`SuperAdmin`, `Admin`, `Report`, `Agent`, `Sales`).
4. `system/api.php` reuses the **same controllers** as a JSON API: it sets `$isApi = true` and swaps `$ui` for a dummy object whose `display()` dumps all assigned vars via `showResult()`. `r2()`/`_alert()` also short-circuit to JSON when `$isApi`. Keep controllers compatible with both paths. Tokens: `config['api_key']` or `a|c.<id>.<time>.<sha1(id.time.api_secret)>`.

## Templates

Smarty template dir lookup order: `ui/ui_custom/` → `ui/themes/<theme>/` → `ui/ui/` (default). Admin views in `ui/ui/admin/`, customer views in `ui/ui/customer/`, widgets in `ui/ui/widget/`. Payment gateway and plugin templates are addressable as `[pg]file.tpl` / `[plugin]file.tpl`. Compiled templates go to `ui/compiled/`.

## Extension points (most live outside this repo and are gitignored)

- **Devices** (`system/devices/*.php`): one class per device type implementing `add_customer`, `remove_customer`, `add_plan`, `update_plan`, `remove_plan`, `online_customer`, `connect_customer`, `disconnect_customer`, `change_username`, `description` — see `system/devices/readme.md`. `Package::getDevice($plan)` picks the file from `tbl_plans.device`, falling back to Radius / MikrotikPppoe / MikrotikHotspot. All router-side provisioning goes through these classes.
- **Payment gateways** (`system/paymentgateway/<name>.php`): function-name convention, not classes — `<name>_validate_config`, `<name>_create_transaction`, `<name>_get_status`, `<name>_payment_notification` (webhook hit via `?_route=callback/<name>`), invoked with `call_user_func` from `controllers/order.php` and `controllers/callback.php`.
- **Plugins** (`system/plugin/*.php`): register UI via `register_menu()` and behaviour via `register_hook($action, $fn)` (`system/autoload/Hookers.php`); core calls `run_hook('<action>')` at points marked `#HOOK`. Plugin pages route through `?_route=plugin/<function>`.
- **Dashboard widgets** (`system/widgets/*.php`): class named after the file with `getWidget()` returning HTML; enabled/ordered per role in `tbl_widgets`.

Core business logic for activating/renewing a service lives in `Package::rechargeUser()` (`system/autoload/Package.php`); it writes `tbl_user_recharges`/`tbl_transactions` and calls the device class.

## Schema changes

There are no migration files. Add SQL to `system/updates.json` under a new version key (`"YYYY.M.D": [queries...]`). `update.php` step 4 runs every version not listed in `system/cache/updates.done.json`, **ignoring all errors**, so queries must be idempotent-safe. Also update `install/phpnuxbill.sql` for fresh installs and bump `version.json` / `CHANGELOG.md`.

## Gotchas

- Translations: `Lang::T('Some text')` uses the English text as the key. A missing key is **written back** into `system/lan/<language>.json` at runtime (and may be machine-translated for non-English languages). Only a few language files are tracked (see `.gitignore`).
- CSRF: the controller assigns `$ui->assign('csrf_token', Csrf::generateAndStoreToken())` when rendering a form, the form submits it as `token`, and the handling action checks `Csrf::check(_req('token'))`.
- Uncaught exceptions in `boot.php` are sent to Telegram (`Message::sendTelegram`) and render `admin/error.tpl` with a stack trace for logged-in admins.
- `update.php` self-updates by downloading the upstream GitHub master zip and overwriting files — local modifications to core files are lost on update.
