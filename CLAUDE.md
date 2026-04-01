# CLAUDE.md — vaskou/autoloader

## Project Overview

`vaskou/autoloader` is a minimal PHP autoloader library (v1.0.1, GPL-2.0-or-later) that provides SPL-based class autoloading using WordPress-style file naming conventions. It is consumed as a Composer dependency; when included, it self-registers so downstream code never needs to manually `require` class files.

**Author:** Vasilis Koutsopoulos  
**No external dependencies.**

---

## Repository Structure

```
autoloader/
├── Autoloader.php          # Core SPL autoloader — maps namespace+class to file path
├── VersionHandler.php      # Singleton version tracker — prevents version downgrade
├── bootstrap_1_0_1.php     # Composer entry point — auto-instantiates on include
└── composer.json           # Package manifest
```

There are no subdirectories, no build system, no test suite, and no CI/CD configuration.

---

## Architecture

The three PHP files work together in a specific order:

```
Composer loads bootstrap_1_0_1.php
    └── bootstrap checks if already loaded (class_exists guard)
        └── includes VersionHandler.php if not already present
            └── VersionHandler singleton tracks current version string
        └── if new version >= current: registers spl_autoload for Vaskou_Autoloader\ namespace
            └── Autoloader.php (and other library classes) are now loaded on demand
```

**Key interactions:**

1. `bootstrap_1_0_1.php` — The Composer `autoload.files` entry point. Uses a global class name guard (`Vaskou_Autoloader_Bootstrap_1_0_1`) so multiple packages depending on this library don't re-register. On construction it:
   - Conditionally includes `VersionHandler.php`
   - Compares version via `VersionHandler` singleton; only registers if `>= current`
   - Calls `spl_autoload_register` prepended (`true, true`) so it takes priority

2. `VersionHandler.php` — Simple singleton storing a version string. Prevents a lower version of the bootstrap from overwriting the SPL registration of a higher version that was loaded first.

3. `Autoloader.php` — The actual autoloader consumers instantiate. Given a `$namespace` and `$base_dir`, it registers itself via `spl_autoload_register` and resolves classes using the WordPress naming convention (see below).

### How consumers use Autoloader

```php
$autoloader = new Vaskou_Autoloader\Autoloader( 'My_Plugin\\', __DIR__ . '/src' );
$autoloader->register();
```

After this, a class `My_Plugin\Controllers\Settings` is looked up as:
`{base_dir}/controllers/class-settings.php`

---

## File Naming Convention

`Autoloader::load_mapped_file()` transforms a class name to a file path:

1. Take the relative class name (after namespace prefix)
2. Lowercase it
3. Replace `_` with `-`
4. Replace `\` (namespace separator) with `/`
5. Prepend `class-` to the final filename segment
6. Append `.php`

**Example:**

| Class | File |
|---|---|
| `My_Plugin\Settings` | `{base_dir}/class-settings.php` |
| `My_Plugin\Controllers\Admin_Page` | `{base_dir}/controllers/class-admin-page.php` |

This follows the WordPress PHP file naming standard.

---

## Code Style

- **Indentation:** Tabs (not spaces)
- **Braces:** Opening brace on same line as class/function declaration
- **Spacing:** Spaces inside parentheses for conditions and function calls (WordPress style): `if ( $condition )`, `substr( $str, 0, $pos )`
- **Comments:** JavaDoc-style `/** @param $name */` for method parameters
- **PHPCS:** The codebase is PHPCS-aware; suppressions use inline `// phpcs:ignore` comments
- **PHP opening tag:** All files start with `<?php` only (no closing `?>`)

---

## Design Patterns

- **Singleton** — Both `VersionHandler` and `Vaskou_Autoloader_Bootstrap_1_0_1` use a classic `private static $_instance` / `public static instance()` singleton pattern.
- **SPL Autoloader** — `Autoloader::register()` calls `spl_autoload_register`. The bootstrap's internal loader is registered prepended (`true, true`).
- **Version guard** — Bootstrap uses `class_exists()` + `VersionHandler` version comparison to safely handle multiple includes of the same library at different versions; the highest version wins.

---

## Development Workflow

### Branching Strategy

The project uses **git-flow**:

| Branch type | Pattern | Purpose |
|---|---|---|
| Main | `main` / `master` | Production-ready tagged releases |
| Integration | `develop` | Ongoing development |
| Release | `release/vX.Y.Z` | Stabilization before tagging |
| Hotfix | `hotfix/vX.Y.Z` | Emergency fixes off main |
| Feature | `feature/*` | New features off develop |

Tagged releases are merged into `develop` after tagging (`Merge tag 'vX.Y.Z' into develop`).

### Adding a New Version

When bumping to e.g. `v1.1.0`:

1. Create `bootstrap_1_1_0.php` — copy `bootstrap_1_0_1.php`, update:
   - Class name: `Vaskou_Autoloader_Bootstrap_1_1_0`
   - `const VERSION = '1.1.0';`
2. Update `composer.json`:
   - `"version": "v1.1.0"`
   - `"autoload": { "files": ["bootstrap_1_1_0.php"] }`
3. Update `Autoloader.php` and `VersionHandler.php` if their logic changes.
4. The old bootstrap file (`bootstrap_1_0_1.php`) can remain for packages that pinned the old version.

### Commit Style

Short imperative messages with a prefix:

```
docs: add CLAUDE.md with codebase documentation
Fixed: not loading Autoloader class file
-WIP
```

---

## No Tests / No CI

This repository has **no automated tests** and **no CI/CD pipeline**. There is no PHPUnit configuration, no `tests/` directory, and no GitHub Actions or similar workflow files.

When making changes, verify behavior manually by including the library in a test PHP script and confirming autoloading resolves correctly.

---

## Composer Integration

The `autoload.files` directive in `composer.json` causes Composer to `require` the bootstrap file on every request (before any class is loaded). This means consumers only need:

```bash
composer require vaskou/autoloader
```

No additional bootstrap call is needed — the SPL autoloader is registered automatically when `vendor/autoload.php` is included.
