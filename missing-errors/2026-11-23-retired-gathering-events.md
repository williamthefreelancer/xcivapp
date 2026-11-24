# Retired / invite-only gathering events

| What | Path / slug | Impact | Status |
|------|-------------|--------|--------|
| Badlands Bra private gathering products may still exist as draft | `the-badlands-bra-private-gathering`, `the-badlands-bra-party-bus-gathering` | Hidden from catalogs; reciprocal club entry kept but excluded from public hub | mitigated |
| Township + Eight Hundred restored as invite-only | `the-township-adventures-event`, `the-eight-hundred-plus-journeys-event` | Republished via `snf_ensure_invite_only_event_products()`; Figma `event-single` landing | fixed |
| Public immerse events used shared township/journeys placeholders | `snf_get_event_default_image_file()` | Mapped to club/route imagery + featured attach; dedicated art still optional | mitigated |
| New reverse routes need live traffic QA | `wyoma-square-to-harris-houston`, `lynn-to-harrisburg` | Created by public catalog ensure; plan filter + club links wired | mitigated |

Retirement for Badlands runs via `snf_retire_removed_gathering_event_products()` (v2). Woo products are not deleted.
