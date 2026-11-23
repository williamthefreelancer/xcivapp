# Checklist — after every public update

Copy a row into [CRITICAL-CHANGES-LOG.md](./CRITICAL-CHANGES-LOG.md) when done.

## 1. Indexing & crawl

- [ ] Page is published (not draft / private / `noindex`)
- [ ] Canonical URL is the live URL you want ranked
- [ ] Internal links from Home / Select Events / nav point to the new URL
- [ ] Sitemap includes the URL (or regenerates on next crawl)
- [ ] Request indexing in Google Search Console (URL Inspection)
- [ ] Bing Webmaster Tools URL submit if Bing traffic matters
- [ ] `robots.txt` still allows the path (no accidental block)

## 2. On-page (AEO + classic SEO)

- [ ] Unique `<title>` (~50–60 chars) with primary intent
- [ ] Meta description answers “what / who / where / when”
- [ ] H1 matches the page promise (one H1)
- [ ] First 100 words answer the query in plain language (good for AI snippets)
- [ ] Images have descriptive `alt`; hero has width/height
- [ ] FAQ / clear bullets if the page answers common questions

## 3. Schema (`../schema/`)

- [ ] Correct JSON-LD type for the page (Organization / WebSite / Event / FAQ / Product)
- [ ] Dates, prices, locations, and URLs match the live page
- [ ] Validate with [Rich Results Test](https://search.google.com/test/rich-results) or Schema Markup Validator
- [ ] Note schema file + page URL in the critical changes log

## 4. Mobile / tablet

- [ ] Phone viewport: CTA above the fold, no horizontal scroll
- [ ] Tablet: nav + forms usable; tap targets ≥ 44px
- [ ] Forms (Apply / Admissions) submit on mobile Safari + Chrome
- [ ] See [MOBILE-TABLET.md](./MOBILE-TABLET.md)

## 5. Paid search & X Ads

- [ ] Landing page URL in ads still resolves (no 404 / wrong redirect)
- [ ] UTM / tracking params still fire
- [ ] Headline / offer on page matches ad creative (Quality Score / relevance)
- [ ] If URL or offer changed → update ad final URL + X Ads card URL same day

## 6. Event calendar listings

- [ ] Paid calendars still show correct date, city, ticket/apply URL
- [ ] Update listing if title, date, or URL changed
- [ ] See [EVENT-CALENDAR-SUBMISSIONS.md](./EVENT-CALENDAR-SUBMISSIONS.md)

## 7. Critical-path smoke test

- [ ] Home loads
- [ ] Event single loads
- [ ] Apply / Select Events deep links work
- [ ] Member login / my-account (if membership pages touched)
- [ ] Checkout / Stripe (if commerce touched)
