# Paid Event Submission Listing Calendars

Track every paid (and important free) calendar / directory where Super Ninety Four events are listed. When the site event changes, these listings must change the same day or ads and SEO fight each other.

## Master inventory

Add a row per platform. Keep login emails in a password manager — not in git.

| Platform | Event(s) listed | Listing URL | Login / account | Paid? | Renews | Last verified | Notes |
|----------|-----------------|-------------|-----------------|-------|--------|---------------|-------|
| _(example)_ Eventbrite / Timeout / local city calendar / Meetup / Fever / etc. | | | | Y/N | | YYYY-MM-DD | |
| | | | | | | | |
| | | | | | | | |
| | | | | | | | |
| | | | | | | | |

## Required fields (keep identical to site + schema)

For each listing, mirror:

- Event title (exact public name)
- Start / end date & time + timezone
- City / venue / “location TBA” language
- Ticket / apply URL (canonical HTTPS)
- Short description (first paragraph of event page)
- Hero image (same crop as site when possible)
- Organizer: Super Ninety Four
- Price / members-only wording (don’t invent public prices)

Use [`../schema/templates/event.json`](../schema/templates/event.json) as the source of truth for structured fields.

## When to update listings (non-negotiable)

Update **all** rows for that event if any of these change on the site:

- [ ] Title
- [ ] Date / time / timezone
- [ ] Venue or city
- [ ] Apply / ticket URL or slug
- [ ] Cancelled / postponed / sold out
- [ ] Primary image
- [ ] Members-only vs public positioning

Then log in [CRITICAL-CHANGES-LOG.md](./CRITICAL-CHANGES-LOG.md).

## Submission workflow (new event)

1. Publish event on Super Ninety Four (stable permalink).
2. Fill Event schema template; validate.
3. Submit / update each paid calendar from the inventory table.
4. Paste listing URLs back into this table.
5. Request Google indexing of the event page.
6. Point SEM / X Ads final URLs at the same canonical URL.

## Weekly audit (15 minutes)

- [ ] Click every active listing URL — still live?
- [ ] Date and apply URL still match WordPress?
- [ ] Any expired paid placement due for renewal?
- [ ] Any duplicate listings with conflicting info?

## Conflict rule

If a calendar and the website disagree, **website + schema win**. Fix the calendar the same day; don’t temporarily “match the calendar” on the site unless the calendar is the only public source of truth (rare).
