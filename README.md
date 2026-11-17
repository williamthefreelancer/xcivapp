# Super Ninety Four — WordPress

Local WordPress site backed by MySQL 8.4. Custom theme: `wp-content/themes/super-ninety-four`.

## Prerequisites

- PHP 8.3+ with `mysqli` enabled
- MySQL 8.4 service `MySQL84` (installed and configured for this project)

## Start the site

```powershell
.\scripts\start-server.ps1
```

Then open [http://localhost:8080](http://localhost:8080).

## Database (local)

| Setting  | Value        |
|----------|--------------|
| Host     | `127.0.0.1`  |
| Database | `wordpress`  |
| User     | `wpuser`     |
| Password | see `.mysql-local.json` / `wp-config.php` |

Credentials are also stored in `.mysql-local.json` (gitignored).

## Admin login

- URL: [http://localhost:8080/wp-admin](http://localhost:8080/wp-admin)
- User: `admin`
- Password: `admin123!`

Change this password after first login.

## Theme integrations

Add these to **local and production** `wp-config.php` (gitignored; never commit secrets). Production deploy syncs only the theme folder — secrets stay on the server.

```php
// Stripe (live or test)
define( 'SNF_STRIPE_MODE', 'live' ); // or 'test'
define( 'SNF_STRIPE_PUBLISHABLE_KEY', 'pk_…' );
define( 'SNF_STRIPE_SECRET_KEY', 'sk_…' );
define( 'SNF_STRIPE_WEBHOOK_SECRET', 'whsec_…' );
define( 'SNF_STRIPE_TEST_PUBLISHABLE_KEY', 'pk_test_…' );
define( 'SNF_STRIPE_TEST_SECRET_KEY', 'sk_test_…' );
define( 'SNF_STRIPE_TEST_WEBHOOK_SECRET', 'whsec_…' );

// Supabase Stripe Sync Engine (SUPER NINETY FOUR PAYMENTS)
define( 'SNF_SUPABASE_URL', 'https://….supabase.co' );
define( 'SNF_SUPABASE_ANON_KEY', 'eyJ…' ); // or sb_publishable_…
define( 'SNF_SUPABASE_SERVICE_ROLE_KEY', 'eyJ…' ); // server-only; subscription lookups

// Expo lead rider map
define( 'SNF_MAPBOX_TOKEN', 'pk.…' );
```

Or set Mapbox / Supabase keys in **WP Admin → WooCommerce → SNF Stripe**.

### Stripe webhook

Point Stripe Dashboard → Developers → Webhooks at:

`https://YOUR-DOMAIN/wp-json/snf/v1/stripe/webhook`

Events: `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `payment_intent.succeeded`, `charge.refunded`.

### After deploy

1. Confirm `SNF_STRIPE_*` (and optional Supabase/Mapbox) exist in DreamHost `wp-config.php`.
2. Visit **Settings → Permalinks** and Save once (or load any page so the theme flushes Woo endpoints).
3. Smoke-test Apply → Checkout and a member page (`/messages/`, `/my-account/`).

### Auto-managed pages

On `init`, the theme creates/updates: Home, About, Apply, Under Consideration, Member Login, Messages, Billing Details, Select Events (members/non-members), Expo (`/expo/`), service inquiry landings, and the Reciprocal hub. Reciprocal clubs themselves are WooCommerce products.

## Notes

- `wp-config.php` is gitignored — keep local credentials out of git.
- Custom themes/plugins belong in `wp-content/themes` and `wp-content/plugins`.
- WP-CLI is available as `php wp-cli.phar ...` in the project root.
- Theme deploy: `.github/workflows/deploy.yml` → DreamHost `super-ninety-four` theme path only.
