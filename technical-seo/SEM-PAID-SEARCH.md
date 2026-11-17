# SEM — Paid Search Engine Marketing (Media Buying)

Playbook for Google Ads / Microsoft Advertising campaigns driving to Super Ninety Four.

## Account placeholders

| Item | Value |
|------|-------|
| Google Ads account ID | |
| Microsoft Ads account ID | |
| Conversion tag / gtag ID | |
| Primary conversion action | Apply start / Apply submit / Purchase |
| Default UTM template | `?utm_source=google&utm_medium=cpc&utm_campaign={campaign}&utm_content={adgroup}` |

## Landing page rules

- Final URL must be HTTPS, 200, mobile-friendly
- Message match: ad headline ↔ H1 ↔ first sentence
- One primary CTA (Apply / Select Events / Expo) — don’t compete with three equal buttons
- Fast LCP on the landing hero (compressed image, width/height set)
- No interstitial that blocks the first click from ads

## Campaign types that fit this site

| Intent | Example keywords / angles | Landing |
|--------|---------------------------|---------|
| Brand | “super ninety four”, brand + event | Home or event |
| Event | event name, city + date modifiers | Event single |
| Membership / club | lifestyle concierge, reciprocal club | Apply / About / Reciprocal |
| Expo / marketplace | expo-related queries | `/expo/` |

Negative out irrelevant nightlife / generic party terms that waste spend if they don’t match positioning.

## Daily paid-search recovery (ads not working)

Run this same day — see also [DAILY-MARKETING-RECOVERY.md](./DAILY-MARKETING-RECOVERY.md).

1. **Spend ≠ clicks** → auction/bid/budget or approval hold; check Ads UI notifications.
2. **Clicks ≠ landing** → broken URL, redirect loop, DNS, or SSL — fix site first.
3. **Landing ≠ convert** → message mismatch, slow mobile form, wrong audience, CTA buried.
4. **Convert ≠ quality** → offer/creative vs page promise; tighten keywords or landing copy.
5. **Quality Score drop** → improve relevance + expected CTR + landing experience (mobile especially).

### Same-day levers (pick 1–2, don’t thrash)

- Pause bleeding keywords / placements
- Swap ad copy to match live page offer
- Point traffic to a stronger event or Apply URL
- Raise bid only on proven converters
- Fix form errors / page speed before raising budget

## After site changes that affect ads

- [ ] Update final URLs in all affected ads/assets
- [ ] Re-test conversion tag (Tag Assistant / Ads conversion diagnostics)
- [ ] Refresh sitelinks if nav labels/URLs changed
- [ ] Log change in [CRITICAL-CHANGES-LOG.md](./CRITICAL-CHANGES-LOG.md)

## Tracking hygiene

- Prefer first-party conversions from Apply / checkout server or thank-you page
- Keep UTM naming consistent so reports stay comparable week to week
- Don’t wipe campaign history by renaming recklessly — duplicate + pause when restructuring
