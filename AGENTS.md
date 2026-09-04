# AGENTS.md

Portal Terpadu Dinas Kesehatan Kabupaten Sukoharjo — plain PHP + vanilla JS/CSS portal, no framework, no build step, no Composer/npm.

## Environment & run

- LAMP via **Laragon** on Windows.
- Serve from `http://localhost/portal_dkk`.
- MySQL via `mysqli`.
- Database name: `portal_dkk`.
- Database user: `root`.
- Database password: empty.
- Database configuration is currently in `config/database.php`.

- No test/lint/typecheck commands exist for the whole project.
- PHP syntax can be checked with:
  `php -l path/to/file.php`
- JavaScript syntax can be checked with:
  `node --check path/to/file.js`
- Verify application behavior manually in a browser or through JSON endpoints.

- Frontend entry point:
  `index.php`
- Frontend uses vanilla JavaScript under:
  `assets/js/`
- JavaScript load order matters. Inspect `index.php` around the existing script loading section before changing script dependencies.
- There is no bundler or frontend build process.

## Case-sensitive paths gotcha

The real directories use capital letters:

- `App/Core`
- `App/Services`

Some older files currently reference lowercase paths such as:

- `../app/...`
- `app/core/loader.php`

These may work on Windows because the filesystem is case-insensitive, but can break on Linux/CI.

When touching these files, use the actual project casing consistently:

- `App/Core/Loader.php`
- `App/Services/...`

Do not perform a broad case-only rename unless explicitly requested.

## Two parallel core loaders — don't mix

### Live loader

`App/Core/Loader.php`

This is the active application loader.

It loads/references:

- `config/*.php`
- `Database`
- `Session`
- `Auth`
- `Permission`
- `Module`
- `Services/LauncherService`

It also defines:

`ROOT_PATH`

### Older loader

`config/loader.php`

This is an older/different loader that references:

- `Helpers/*`
- `config/Database.php`
- `dirname(__DIR__,2)/config`

The latter path is incorrect for the current project structure.

Treat `config/loader.php` as stale unless the existing code clearly requires it.

Do not mix the two loader architectures.

## App registry

The application registry is currently inconsistent.

### Static registry

`config/apps.php`

Contains a static array of applications with fields such as:

- id
- nama
- icon
- warna
- kategori
- url

### Dynamic module registry

`modules/<id>/module.php`

Consumed by:

- `App/Core/Module.php`
- `App/Services/LauncherService.php`

Current module registry contains:

`modules/siku/`

Important:

When adding or modifying an application, determine whether the requested feature belongs to:

- the static registry in `config/apps.php`
- the dynamic module system in `modules/`

Do not introduce a third registry.

The landing page currently renders application categories through:

`LauncherService::categories()`

Inspect the existing data flow before modifying launcher behavior.

## Data flow / DB conventions

- The DB connection is the global `$config` created by the current database configuration.
- Existing application code generally uses `$config`.
- Some broken/stale files incorrectly use `$koneksi`.

Known stale/broken examples include:

- `get_running.php`
- `get_jadwal.php`

Do not copy the `$koneksi` convention into new code.

- `install/index.php` is currently empty.
- Do not assume it is part of the active installation flow.

### Database records

Data tables generally use soft-delete semantics:

```php
aktif = 'Y'