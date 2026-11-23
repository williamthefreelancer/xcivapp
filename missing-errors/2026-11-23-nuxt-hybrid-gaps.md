# Nuxt hybrid gaps (superseded by headless)

Superseded by [2026-11-23-nuxt-headless-gaps.md](./2026-11-23-nuxt-headless-gaps.md). Stack is now full headless Nuxt + WP API.

| Missing | Path / area | Impact | Status |
|---------|-------------|--------|--------|
| Account address mutations | `snf/v1/account/addresses/update` | Addressed in headless pass | fixed |
| Edit-account profile form | `snf/v1/account/profile` + `/app/account` | Addressed in headless pass | fixed |
| Funnel accept/decline JSON | `snf/v1/funnel/{step}/action` | Addressed in headless pass | fixed |
| Cross-origin auth | `snf_auth` + `/login` | Mitigated; prefer same-site proxy in prod | mitigated |
| Production Node reverse-proxy | `frontend/` deploy | Still open ops | open |
| Club desks beyond thin UI | `/club/newsletter`, `/rfq`, `/addons` | Partial Nuxt desks; CPT edit in wp-admin | mitigated |
