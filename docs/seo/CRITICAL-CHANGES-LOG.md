# Critical changes log

Record site, schema, ads, and calendar changes that can break discovery or paid performance later. Newest entries at the top.

## Template

```
### YYYY-MM-DD — short title
- Owner:
- Pages / URLs:
- What changed:
- Why (marketing or technical):
- Schema updated? (file names):
- Ads updated? (Google / Bing / X):
- Calendars updated? (platforms):
- Indexing requested? (GSC / Bing):
- Mobile checked?
- Follow-up date:
- Result / notes:
```

---

### 2026-11-17 — Theme JSON-LD wired + ops folders
- Owner: William / ops
- Pages / URLs: all public theme views via `wp_head`
- What changed: Added `inc/schema.php` (Organization, WebSite, Event, BreadcrumbList, Product); ops folders now `docs/seo/` + `docs/schema/`; theme version 2.18.0
- Why: Keep indexing, AEO, SEM, X Ads, event calendars, and mobile optimization consistent on every update; emit structured data automatically
- Schema updated?: live emitter + templates under `schema/templates/`
- Ads updated?: n/a
- Calendars updated?: n/a
- Indexing requested?: re-test Rich Results after deploy
- Mobile checked?: n/a (schema-only)
- Follow-up date: fill production domain + ad account IDs; add FAQ schema when Figma FAQ ships
- Result / notes: FAQPage not auto-emitted until visible FAQ UI exists
