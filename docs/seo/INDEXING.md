# Search engine indexing

Keep Super Ninety Four discoverable in Google, Bing, and other crawlers.

## Properties to maintain

| Tool | Purpose |
|------|---------|
| Google Search Console (GSC) | Indexing, sitemaps, Core Web Vitals, spam |
| Bing Webmaster Tools | Bing / Copilot discovery |
| robots.txt | Crawl allow/deny |
| XML sitemap | URL inventory for crawlers |

Fill in live property URLs here when set:

- GSC property: `______________________________`
- Bing property: `______________________________`
- Production domain: `______________________________`
- Sitemap URL: `https://YOUR-DOMAIN/wp-sitemap.xml` (or plugin sitemap)

## Indexation hygiene

1. **Do index:** Home, About, Apply, public event landings, Expo (if public), reciprocal marketing pages you want ranked.
2. **Usually noindex:** Thank-you pages, filter/sort URLs with params, private member-only dashboards, cart/checkout, account screens.
3. **Never soft-404:** Empty event archives that return 200 with “no results” and thin content — either add content or return a clear noindex.

## After a URL change

1. 301 redirect old → new (preserve equity; ads/calendars often lag).
2. Update internal links + schema `url` / `@id`.
3. Update sitemap.
4. GSC → URL Inspection → Request indexing (new URL).
5. Mark old URL as redirected in [CRITICAL-CHANGES-LOG.md](./CRITICAL-CHANGES-LOG.md).

## Daily / weekly indexing triage

When “we’re not showing up”:

| Symptom | Check |
|---------|--------|
| “Discovered – currently not indexed” | Thin content, duplicate, crawl budget, or quality — strengthen unique copy + internal links |
| “Crawled – currently not indexed” | Same; also check soft-404 and duplicate titles |
| “Excluded by ‘noindex’” | Theme/plugin/meta robots — remove noindex if public |
| “Blocked by robots.txt” | Fix robots allow |
| Indexed but wrong snippet | Fix title/description/schema; may take days |

## WordPress notes (this project)

- Theme: `wp-content/themes/super-ninety-four`
- Event CPT + Woo products power Select Events — each public event needs a stable permalink
- After permalink or endpoint changes: **Settings → Permalinks → Save**
- Prefer one canonical URL per event (avoid CPT + product duplicates fighting each other)

## Submit / re-crawl checklist

- [ ] Sitemap submitted in GSC + Bing
- [ ] Homepage + top 5 money pages requested for indexing after major redesign
- [ ] New event published → request indexing within 24h of go-live
- [ ] Confirm live HTML is not blocked by auth, geoblock, or challenge pages for Googlebot
