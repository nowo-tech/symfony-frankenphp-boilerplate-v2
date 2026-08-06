# Architecture

`symfony-frankenphp-boilerplate-v2` (brand: **Nowo Shell**) is a platform starter. Domains:

| Domain | Responsibility |
|--------|----------------|
| `Identity` | Users, groups, account prefs, AuthKit host wiring, demo seed |
| `Shared` | Appearance, settings (mailer/instance), legal, locale, menu, navigation, audit bridge, health, HTTP helpers, Twig |
| `Setup` | SiteBackup gate, platform catalog seeders (menus, breadcrumbs, cookie consent) |

Product apps clone this tree and add their own domains under `src/`. Do not reintroduce product-only domains (ingest, issues, projects, …) into this platform boilerplate.
