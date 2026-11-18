# Super Ninety Four — WordPress

Local WordPress site backed by MySQL 8.4. Custom theme: `wp-content/themes/super-ninety-four`.

## Theme architecture (MVC-style)

WordPress stays the CMS/runtime. The custom theme is organized as:

| Layer | Path | Role |
|-------|------|------|
| Controllers | `app/Controllers/` | Thin request orchestration |
| Models | `app/Models/` | CPT helpers |
| Services | `app/Services/` | Domain logic (Stripe, media, Woo, etc.) |
| Support | `app/Support/` | Bootstrap + Vite asset loader |
| Views | `page-templates/`, `template-parts/` | PHP templates |
| Front-end | `resources/` → `dist/` | Vite + Tailwind + Vue (shadcn-vue) islands |

Brand colors/fonts stay the existing `--snf-*` tokens (Tailwind maps the same palette).

### Front-end build

```powershell
cd wp-content\themes\super-ninety-four
npm ci
npm run build
```

This writes `dist/main.css` and `dist/main.js`. Deploy CI runs the same build before rsync. Local fallback: if `dist/` is missing, the theme still loads `assets/css` + `assets/js`.

## Prerequisites

- PHP 8.3+ with `mysqli` enabled
- MySQL 8.4 service `MySQL84` (installed and configured for this project)
- Node 20+ (theme asset builds)

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

Or set Mapbox / Supabase keys in **WP Admin → WooCommerce → SNF Stripe** (includes an **API health** panel for Stripe / Supabase / Mapbox probes).

### PHP libraries (Composer)

The theme ships `stripe/stripe-php` and PSR-4 autoload for `SNF\` under `wp-content/themes/super-ninety-four/`. After cloning or when `vendor/` is missing:

```powershell
cd wp-content\themes\super-ninety-four
composer install --no-dev
```

Deploy CI runs `composer install --no-dev` and `npm ci && npm run build`, then rsyncs the theme (including generated `vendor/` and `dist/`). Legacy `inc/*.php` paths are thin shims to `app/Services/`.

### Stripe webhook

Point Stripe Dashboard → Developers → Webhooks at:

`https://YOUR-DOMAIN/wp-json/snf/v1/stripe/webhook`

Events: `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `payment_intent.succeeded`, `charge.refunded`.

### Marketing plugins (forms / CRM / automation)

Use the installed plugins — do not rebuild these in the theme:

- **SureForms** — Contact / Newsletter embeds on Blank Canvas pages (Videos & Audio may still host optional forms above the theme library)
- **SureContact** — CRM inbox
- **OttoKit** (`suretriggers`) — automations; theme fires `snf_*` actions for Apply → Stripe, workshop reservations, and Stripe webhooks

### After deploy

1. Confirm `SNF_STRIPE_*` (and optional Supabase/Mapbox) exist in DreamHost `wp-config.php`.
2. Confirm theme `vendor/autoload.php` exists (or run `composer install` on the theme path).
3. Visit **Settings → Permalinks** and Save once (or load any page so the theme flushes Woo endpoints).
4. Smoke-test Apply → Checkout, `/contact/` (SureForms), and a member page (`/messages/`, `/my-account/`).

### Videos & Audio library

Theme-owned media stack (`inc/media-library.php`):

- **CPTs:** `snf_video` (YouTube library), `snf_audio` (channels / episodes)
- **Taxonomies:** `snf_media_topic`, `snf_media_series`, `snf_audio_channel`, `snf_broadcast_format`
- **Hub:** `/videos-audio/` → `page-templates/videos-audio.php`
- **Archives:** `/library/videos/`, `/library/audio/`
- **Event products:** Attendance mode (in person / virtual / hybrid / audio), live status, provider (YouTube Live, external webinar, HLS audio, WebRTC room), venue fields
- **Settings:** WP Admin → Videos & Audio → Settings (ICE servers, defaults, privacy-enhanced YouTube)
- **REST:** `snf/v1/media/videos`, `snf/v1/media/audio`, `snf/v1/media/live/{id}`, `snf/v1/media/room/{room}/signal`

After deploy, visit **Settings → Permalinks** and Save once so media rewrite rules flush.

### Auto-managed pages

On `init`, the theme creates/updates: Home, About, Apply, Under Consideration, Member Login, Messages, Billing Details, Select Events (members/non-members), Expo (`/expo/`), Videos & Audio hub, service inquiry landings, and the Reciprocal hub. Reciprocal clubs themselves are WooCommerce products.

## Notes

- `wp-config.php` is gitignored — keep local credentials out of git.
- Custom themes/plugins belong in `wp-content/themes` and `wp-content/plugins`.
- WP-CLI is available as `php wp-cli.phar ...` in the project root.
- Theme deploy: `.github/workflows/deploy.yml` → DreamHost `super-ninety-four` theme path only.
