# Schema — Super Ninety Four

JSON-LD templates and notes so search engines and answer engines understand the brand, site, events, and FAQs.

Pair with [`../technical-seo/`](../technical-seo/) on every public update.

## Contents

| Path | Purpose |
|------|---------|
| [templates/organization.json](./templates/organization.json) | Brand / Organization |
| [templates/website.json](./templates/website.json) | WebSite (+ optional SearchAction) |
| [templates/event.json](./templates/event.json) | Single Event (Select Events, workshops) |
| [templates/breadcrumb.json](./templates/breadcrumb.json) | BreadcrumbList |
| [templates/faq-page.json](./templates/faq-page.json) | FAQPage for AEO |
| [templates/product-membership.json](./templates/product-membership.json) | Membership / product offer |
| [IMPLEMENTATION-NOTES.md](./IMPLEMENTATION-NOTES.md) | How to emit these from the WP theme |
| [CHANGELOG.md](./CHANGELOG.md) | Schema field / type changes over time |

## Rules

1. **Visible truth only** — schema must match on-page content.
2. **One canonical URL** per entity — same as ads and calendar listings.
3. **Validate** after edits: [Rich Results Test](https://search.google.com/test/rich-results).
4. **Log** URL + template used in `technical-seo/CRITICAL-CHANGES-LOG.md`.

## Replace placeholders

In every template, replace:

- `https://www.example.com` → production domain
- Brand description, social URLs, geo, prices, dates
- Event-specific fields before publishing that event
