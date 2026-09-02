# AGENTS.md

Portal Terpadu Dinas Kesehatan Kabupaten Sukoharjo — plain PHP + vanilla JS/CSS portal, no framework, no build step, no Composer/npm.

## Environment & run
- LAMP via **Laragon** on Windows. Serve from `http://localhost/portal_dkk`. MySQL via `mysqli`; DB name `portal_dkk` (root, empty password) — hardcoded in `config/database.php`.
- No test/lint/typecheck commands exist. Verify PHP files with `php -l file.php`; check output manually in a browser or via the JSON endpoints.
- Frontend (`index.php`) is a video/starfield landing page; vanilla JS under `assets/js/` (load order matters, see `index.php:676`). No bundler.

## Case-sensitive paths gotcha (Windows hides this)
The real dirs are `App/Core`, `App/Services` (capital `A`), but `launcher/index.php`, `landing/index.php`, `api/launcher.php` `require` lowercase `../app/...` / `app/core/loader.php`. Works on Windows/macOS (case-insensitive) but **breaks on Linux/CI**. Use `App/Core/Loader.php` consistently when touching these.

## Two parallel core loaders — don't mix
- `App/Core/Loader.php` — the live one (loads `config/*.php`, `Database`, `Session`, `Auth`, `Permission`, `Module`, `Services/LauncherService`; defines `ROOT_PATH`).
- `config/loader.php` — older/different loader that pulls in `Helpers/*`, `config/Database.php`, and `dirname(__DIR__,2)/config` (wrong path). Likely stale; don't rely on it.

## App registry is inconsistent
- `config/apps.php` — static array of apps (id, nama, icon, warna, kategori, url) used by `config/apps.php` consumers.
- `modules/<id>/module.php` — dynamic registry consumed by `App/Core/Module.php` + `App/Services/LauncherService.php` (`Module::enabled()`, `categories()`, `orbit()`). Only `modules/siku/` currently exists.
When adding an app decide which registry you're extending; the landing page renders from `LauncherService::categories()`.

## Data flow / DB conventions
- DB connection is the global `$config` (from `config/database.php`).
- **Broken/stale files use `$koneksi`** instead of `$config` (get_running.php, get_jadwal.php) — these are broken; match the `$config` convention and don't copy them. `install/index.php` is empty.
- Data tables use soft-delete via `aktif='Y'` filter + `urutan` ordering (see `admin/crud/*.php`).
- `api/*.php` return JSON (`Content-Type: application/json`) against the same `tbl_*` tables.

## Admin
- Login at `admin/login.php`; auth uses session flag `admin_logged_in` and compares **MD5(password)** against `tbl_admin` (admin/login.php:9-11). Include `admin/config.php` (defines `requireLogin()`) — it's the canonical helper; `admin/auth.php` is a duplicate.
- CRUD pages under `admin/crud/` (`fasyankes.php`, `sdm.php`, `penyakit.php`, `kecamatan.php`, `portal_info.php`, `sdm_kecamatan.php`, `penyakit_kecamatan.php`).
- SDM has per-kecamatan breakdown: item defs in `tbl_sdm_items`, per-kecamatan counts in `tbl_sdm_kecamatan` (see `api/get_sdm.php` with optional `?kecamatan=`).

## Maps
- Interactive SVG map: `assets/svg/peta_sukoharjo_satelit_interaktif.svg` (referenced by `index.php:357`), loaded via `<object>`.
- `api/map.php` parses `<path class="district" data-name>` entries from `assets/svg/sukoharjo_interactive.svg` using `App/Services/MapService.php`. Note the SVG filename it reads is not the one the page displays — don't assume they're the same file.
- `tools/mapbuilder/` is a standalone SVG path editor (builder.js/districts.js/save.php) — debug/interactive map data tool.

## Styling conventions
- CSS/HTML self-contained, regenders UI in the `.php` files. Colors/theming use CSS custom patterns around `#00d4ff`/`#061426`. Keep edits inline-consistent with each file rather than introducing shared CSS unless already linked.
