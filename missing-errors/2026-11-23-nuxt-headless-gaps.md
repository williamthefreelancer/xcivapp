# Nuxt full-headless gaps

| Missing | Path / area | Impact | Status |
|---------|-------------|--------|--------|
| BuddyPress member profiles not ported | `/community` circles only | BP single templates still WP-only if plugin active | open |
| Lead-rider realtime UI not in Nuxt | `snf/v1/lead-rider/*` | API exists; no Nuxt page | open |
| Club CPT editors stay in wp-admin | `/club/*` desks | Newsletter/RFQ/addons desks show counts + WP links; full CRUD not in Nuxt | mitigated |
| Store API nonce / cart token depends on Woo Blocks | `wc/store/v1` via Nitro | Local XAMPP must have WooCommerce Store API available | open |
| Same-host reverse proxy not in DreamHost theme rsync | `frontend/` deploy | Node process + Apache proxy still manual ops | open |
| Media detail routes by slug | `/media` lists only | Singles redirect path mapped in bridge; Nuxt detail pages thin | open |
| Classic PHP templates still ship in theme | `page-templates/`, FSE `templates/` | Not live when bridge redirects; kept for `?classic=1` / editor | mitigated |

## Notes

- Stripe webhooks remain on WordPress by design.
- Design tokens are canonical in `frontend/assets/css/main.css` (mirrors theme `--snf-*`).
