# X Ads — Paid social (Media Buying)

X (Twitter) Ads playbook for Super Ninety Four awareness, event pushes, and Apply traffic.

## Account placeholders

| Item | Value |
|------|-------|
| X Ads account | |
| Pixel / conversion events | |
| Brand @handle | |
| Default UTM | `?utm_source=x&utm_medium=paid&utm_campaign=` |

## Creative → landing alignment

| Ad promise | Must land on |
|------------|--------------|
| Specific event | That event’s public URL |
| Membership / apply | `/apply/` or current Apply template |
| Expo | `/expo/` |
| Brand story | Home or About |

Card image / video should match the page hero when possible so people don’t bounce.

## Pre-flight before spend

- [ ] Destination URL opens logged-out (no forced login for the pitch)
- [ ] Mobile Safari + X in-app browser: CTA and form usable
- [ ] HTTPS + no mixed-content blocks on assets
- [ ] Tracking params don’t break WordPress query vars (test Apply deep links)

## Daily X Ads recovery

1. Check delivery (learning / limited / rejected ads).
2. Open the live destination from the ad preview — confirm 200 + correct offer.
3. If CTR high / CVR low → landing or audience mismatch; fix page or tighten targeting.
4. If CTR low → creative fatigue; new hook, image, or first line; keep URL stable if converting.
5. If URL or event date changed → update website card URL **and** calendar listings same day.

## Critical site changes that break X Ads

Log these immediately:

- Event slug / permalink change
- Apply URL or query param change (`?event=`)
- Homepage redesign that moves/removes CTA
- robots or auth wall accidentally covering a public landing

## Notes for this WordPress build

- Event CPT registers `event` as a query var — Apply deep links were specially handled in the theme. Don’t “simplify” query params without re-testing ads that use them.
- After deploy: smoke-test one X in-app browser session on iPhone if event ads are live.
