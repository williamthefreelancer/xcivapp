# Implementation notes (WordPress theme)

Templates in `templates/` document the field shapes. Live emission is wired in:

`wp-content/themes/super-ninety-four/inc/schema.php` → `wp_head` (priority 5)

Loaded from `functions.php` (theme `SNF_VERSION` 2.18.0+).

## Current placement

| Template | Where it outputs |
|----------|------------------|
| organization + website | Public pages via `@graph` in `wp_head` |
| event | Singular `event` CPT + Select Events products + workshop |
| breadcrumb | Same event landings |
| faq-page | Not emitted yet — wait for visible FAQ UI from Figma |
| product-membership | Other public Woo products (reciprocal / services) |

Skipped on cart, checkout, My Account, protected member pages, and Select Events (Members).

Prefer one `<script type="application/ld+json">` block (or a `@graph` array) rather than duplicate Organization on every page.

## Example `@graph` pattern

```json
{
  "@context": "https://schema.org",
  "@graph": [
    { "@type": "Organization", "@id": "https://www.example.com/#organization" },
    { "@type": "WebSite", "@id": "https://www.example.com/#website", "publisher": { "@id": "https://www.example.com/#organization" } },
    { "@type": "Event", "@id": "https://www.example.com/event/slug/#event", "organizer": { "@id": "https://www.example.com/#organization" } }
  ]
}
```

## Data sources in this theme

Pull live values from existing helpers where possible:

- Event slug / title / image → `inc/events.php` (`snf_get_event_*`)
- Apply deep links → theme Apply URL helpers (preserve `event` query handling)
- Products → WooCommerce product fields for reciprocal / membership offers

## Validation workflow

1. Update template or PHP emitter.
2. View page source → confirm JSON-LD.
3. Rich Results Test on production URL.
4. Log in `docs/seo/CRITICAL-CHANGES-LOG.md`.
5. If event dates/URLs changed → update paid calendars + ads the same day.

## Do not

- Emit Event schema for private / members-only pages you do not want indexed
- Mark `isAccessibleForFree: true` if application or membership is required
- Leave `example.com` placeholders on production
