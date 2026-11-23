# Theme critical risks (Super Ninety Four)

- **Date:** 2026-11-22
- **Scope:** `wp-content/themes/super-ninety-four`
- **Status:** mitigated

## Findings

| ID | Issue | Impact | Status |
|----|--------|--------|--------|
| ME-001 | Obsolete pages hard-deleted on every `init` via `snf_ensure_site_pages()` | Recreating listed slugs could delete content again | mitigated — moved to one-time `snf_delete_obsolete_site_pages()` |
| ME-002 | BuddyPress overrides called unguarded `bp_nouveau_*` APIs | Fatal if BP active without Nouveau | mitigated — `function_exists` guards + classic fallbacks |
| ME-003 | Deploy without Composer `vendor/autoload.php` | Bootstrap never runs; hub/php-view pages break | mitigated — admin notice, admin-bar node, front-end admin banner |
| ME-004 | Legacy `assets/js` fallback missing Community, RFQ, ticket-commerce | Broken UI when `dist/` absent | mitigated — added `community.js` / `rfq.js`, ticket commerce in `workshop.js`, wired in `Assets.php` |
| ME-005 | `style.css` Version `2.38.1` vs `SNF_VERSION` `2.38.2` | Version drift only | fixed — synced to `2.38.2` |

## Notes

- Local tree had `vendor/` + `dist/`; PHP lint clean; FSE view map complete; WooCommerce active; BuddyPress not installed.
- `debug.log` was empty at audit time.
