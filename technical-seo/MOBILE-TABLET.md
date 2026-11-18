# Mobile & tablet optimization

Every public update should stay usable on phone and tablet — search rankings, ads Quality Score, and Apply conversions all depend on it.

## Breakpoints to test (this theme)

Use real devices when possible; otherwise DevTools + one real phone.

| Surface | Width targets | Focus |
|---------|---------------|--------|
| Phone | 375, 390, 430 | Header menu, hero, Apply CTA, forms |
| Tablet | 768, 834, 1024 | Nav dropdowns, event layout, two-column collapse |
| Desktop | 1280+ | Don’t break while fixing mobile |

Theme already sets `viewport` in `header.php`. Keep it:

`width=device-width, initial-scale=1`

## After every UI change

- [ ] No horizontal scroll on phone
- [ ] Hamburger / primary nav opens and closes; body scroll locked as designed
- [ ] Logo + CTA not colliding
- [ ] Event single: admissions aside usable on small screens
- [ ] Forms: labels visible; keyboard doesn’t hide submit
- [ ] Tap targets ≥ ~44×44px
- [ ] Images not overflowing; width/height attributes present to reduce CLS
- [ ] In-app browsers (Instagram / X / TikTok): page still usable

## Core Web Vitals (practical)

| Metric | Goal | What usually fixes it here |
|--------|------|----------------------------|
| LCP | Fast hero | Self-hosted fonts; WebP + `fetchpriority` on heroes; compressed theme JPGs |
| CLS | Stable layout | Always set image dimensions; don’t inject late banners above content |
| INP | Responsive UI | Theme `dist/main.js` is a thin island loader; heavy Vue/expo chunks load only when needed |

Theme helpers live in `app/Services/performance.php` (dequeue unused Woo/BP/emoji assets on public pages, preload fonts/CSS). After deploy, re-check [PageSpeed Insights](https://pagespeed.web.dev/) for `superninetyfour.com` (mobile).

## Tablet-specific

- [ ] Dropdown menus don’t require hover-only
- [ ] Select Events cards readable in portrait and landscape
- [ ] Expo / map views (if Mapbox) don’t trap scroll awkwardly

## Forms that matter for revenue

Smoke-test on mobile after any change:

1. Apply
2. Event admissions
3. Member login
4. Checkout / billing (if touched)

## When mobile is why marketing “isn’t working”

1. Open the ad’s landing URL on your phone (same network type as users when possible).
2. Time-to-CTA and time-to-submit — if either is painful, fix UX before raising spend.
3. Fix, redeploy, re-test in-app browser, then resume budget.
