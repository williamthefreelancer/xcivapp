# Super Ninety Four — WooNuxt storefront

Nuxt 4 + [WooNuxt](https://github.com/scottyzen/woonuxt) child layer is the **public front end**. WordPress / WooCommerce remain CMS, GraphQL, Store API, Stripe webhooks, and `wp-admin`.

| Layer | Path | Role |
|-------|------|------|
| Child (SNF) | `pages/`, `layouts/`, `components/`, `composables/` | Full theme UI — home, club, events, member, funnel |
| Parent | `woonuxt_base/` | WooNuxt commerce (GraphQL cart/checkout/products) |
| Bridge | `server/api/wp/*` + theme `snf/v1` | REST for club/events/auth while GraphQL covers shop |

## Setup

```powershell
copy .env.example .env
npm ci
npm run graphql:codegen   # once WPGraphQL is live
npm run dev
```

### Env

**WooNuxt (required for Nuxt to boot):**

- `GQL_HOST` — WordPress GraphQL endpoint  
- `NUXT_IMAGE_DOMAINS` — image hostnames (comma-separated)

**SNF REST bridge:**

- `NUXT_PUBLIC_WP_URL` / `NUXT_WP_URL` — WordPress origin for `/api/wp/*`

### WordPress

1. Install **Settings for WooNuxt** (pulls WPGraphQL, WooGraphQL, Headless Login).  
2. Keep theme `super-ninety-four` + plugin `snf-payments` + WooCommerce.  
3. In `wp-config.php`:

```php
define( 'SNF_NUXT_BRIDGE', true );
define( 'SNF_NUXT_APP_URL', 'http://localhost:3000' );
```

Until GraphQL codegen succeeds, SNF pages that use `snf/v1` and Store API still work; WooNuxt GraphQL calls use a placeholder SDK.

## Scripts

- `npm run dev` — http://localhost:3000  
- `npm run graphql:codegen` — regenerate `woonuxt_base/app/gql/*` from `GQL_HOST`  
- `npm run build` — production Nitro server in `.output/`  

## Production

Host Node (`node .output/server/index.mjs`) and reverse-proxy public paths to Nitro. Keep WordPress on `/wp-admin`, `/wp-json`, `/graphql`, and Stripe webhooks.
