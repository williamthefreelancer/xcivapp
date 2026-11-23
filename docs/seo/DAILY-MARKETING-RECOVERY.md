# Daily marketing recovery

Use this when organic, AEO, paid search, X Ads, or calendar listings are underperforming. Goal: find the broken link in the chain in under an hour, fix one critical thing, then decide whether to change creative or the site.

## 15-minute triage

Answer in order:

1. **Is the destination alive?** Open the exact URL from the ad / listing / Search result. Expect 200, correct offer, mobile OK.
2. **Did something change in the last 72 hours?** Check [CRITICAL-CHANGES-LOG.md](./CRITICAL-CHANGES-LOG.md) + recent deploys.
3. **Is tracking lying?** Compare ad platform clicks vs analytics / server Apply starts.
4. **Is the offer mismatched?** Ad/calendar says A; page says B.
5. **Is the audience wrong?** Traffic OK, zero intent — pause and rewrite targeting before rewriting the whole site.

## Decision tree

```
Traffic problem?
  ├─ No impressions → bid / budget / approval / keywords / creative rejection
  ├─ Impressions, no clicks → creative / hook / social proof
  ├─ Clicks, bounce → landing URL, speed, mobile, message match
  └─ Engaged, no convert → form friction, trust, price/membership clarity, CTA

Organic / AI answer wrong?
  └─ Fix page facts + schema + calendar listings (see AEO.md + schema/)

Calendar traffic wrong date/URL?
  └─ EVENT-CALENDAR-SUBMISSIONS.md same day
```

## Same-day site levers (high impact)

Only change what the triage points to:

| Lever | Example |
|-------|---------|
| Title / H1 / first paragraph | Clearer offer for SEM + AEO |
| CTA label / placement | Stronger Apply path on mobile |
| Event date / URL consistency | Site ↔ schema ↔ calendars ↔ ads |
| Form error / broken deep link | Restore Apply `?event=` paths |
| Redirect | Fix 404 from old ad URL |
| Speed | Compress hero; remove blocking assets |

Avoid redesigning the whole theme during a spend emergency.

## Same-day media levers

- Pause losers; keep winners
- Clone ad with new hook → same proven URL
- Shift budget to best event landing
- Update X card + search sitelinks if nav changed

## End-of-day log (required)

Add one entry to [CRITICAL-CHANGES-LOG.md](./CRITICAL-CHANGES-LOG.md):

- Symptom
- Root cause
- What you changed (site / ads / calendars / schema)
- What you’ll check tomorrow

## Tomorrow check

- [ ] Destination still 200
- [ ] Conversion or Apply starts moved in the right direction
- [ ] No new Search Console coverage errors on money pages
- [ ] Calendar listings still match
