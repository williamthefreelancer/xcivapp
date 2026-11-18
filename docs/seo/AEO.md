# AEO — Answer Engine Optimization

Make Super Ninety Four easy for AI answers (Google AI Overviews, ChatGPT browsing, Perplexity, Copilot) to cite correctly.

AEO builds on technical SEO: clear entities, factual pages, schema, and quotable answers.

## What answer engines reward

- One clear entity: **Super Ninety Four** (brand, what it is, who it’s for)
- Direct answers in the first screen of content
- Structured facts: dates, locations, membership vs public events, how to apply
- FAQ blocks that match real questions people ask
- Consistent names across site, schema, ads, and calendars

## Page pattern (use on public landings)

1. **What it is** — 1–2 sentences
2. **Who it’s for** — audience
3. **When / where** — if event
4. **How to join / apply** — CTA with plain steps
5. **FAQ** — 3–7 real questions

Then mirror facts in JSON-LD (`../schema/templates/`).

## Entity facts to keep consistent

Update this block whenever branding or positioning changes:

| Field | Canonical value |
|-------|-----------------|
| Brand | Super Ninety Four |
| Short description | _(fill)_ |
| Primary audience | _(fill)_ |
| Primary location / market | _(fill)_ |
| Official site | _(fill)_ |
| Contact / apply path | `/apply/` (confirm live) |
| Social / X handle | _(fill)_ |

## AEO content checklist (per page)

- [ ] Page answers a real query without requiring a login
- [ ] Brand name appears in H1 or first paragraph where natural
- [ ] No contradictory dates/prices vs schema or calendar listings
- [ ] FAQ schema only for FAQs visible on the page
- [ ] Avoid stuffing; prefer crisp facts answer engines can quote

## When marketing is weak (AEO angle)

1. Search your brand + event names in Google / Perplexity / ChatGPT.
2. Note wrong dates, wrong URLs, or missing brand.
3. Fix the **source page** + schema first; then ads/calendars.
4. Add or update FAQ that matches the wrong answer you saw.
5. Log the mismatch in [CRITICAL-CHANGES-LOG.md](./CRITICAL-CHANGES-LOG.md).

## Do not

- Hide critical facts behind membership walls if you want public AI citations for those facts
- Publish conflicting event titles across CPT, product, ads, and calendars
- Use FAQ schema for content that isn’t shown as FAQ on the page
