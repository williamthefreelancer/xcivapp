# Event card + membership plan images

| What | Path / area | Impact | Status |
|------|-------------|--------|--------|
| Public Select Events cards had empty media | `snf_get_dashboard_event_card_by_slug()` without fallback | Blank cards on select-events / reciprocal club lists | fixed |
| Event → plan map omitted City Club / invite-only | `snf_get_event_membership_plan_map()` | Apply/event plan step could show wrong plans | fixed |
| Membership plan radios had no club imagery | `snf_get_membership_plan_definitions()` + apply/event templates | Plan picker lacked visual club cue | fixed |
| Featured images missing on public event products | `snf_ensure_event_product_images()` | Cards/landings fell back inconsistently | fixed |

Plan images: Back Creek → `the-clubs-at-back-creek.jpg`, Regions NE → `the-clubs-at-wyoma-square.jpg`, City to City → `the-city-clubs-at-super-ninety-four.jpg`.
