# WPGraphQL login settings missing `default`

| Field | Detail |
|-------|--------|
| Status | fixed |
| Date | 2026-11-23 |
| Symptom | `PHP Warning: Undefined array key "default" in wp-includes/option.php on line 3249` on most requests |

## What was missing

`wpgraphql_login_settings` was registered twice:

1. **wp-graphql-headless-login** `PluginSettings` (`init` 10) — with `default`
2. **wp-graphql** `SettingsRegistry::init_registry()` (`init` 11) — same option name, no `default`

Step 2 overwrote the registered args but left `filter_default_option` hooked, so `get_option()` warned under PHP 8+.

## Fix

Theme shim `snf_fix_wpgraphql_login_settings_default` in `wp-content/themes/super-ninety-four/app/Services/plugin-integrations.php` (`init` 12) re-registers Headless Login `PluginSettings` (or sets `default` => `[]` as fallback).

## Impact

Noise in Apache/PHP error log on front, login, and admin; no functional breakage by itself.
