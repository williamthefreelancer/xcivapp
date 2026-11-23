# Missing errors

Track gaps found when updating existing project folders (theme, plugins, deploy scripts, etc.).

## When to add a report

Create or update a file here whenever code changes uncover:

- Missing files, assets, Composer `vendor/`, or Vite `dist/` fallbacks
- Undefined function / class / template references
- Plugin coupling that can fatal if the plugin or mode is wrong
- Destructive sync / data-loss footguns
- Incomplete ports (legacy vs Vite islands, FSE vs classic)

## Naming

```text
YYYY-MM-DD-short-slug.md
```

Example: `2026-11-22-theme-critical-risks.md`

## Status values

Use one of: `open` · `mitigated` · `wontfix` · `fixed`

Keep each report short: what was missing, where, impact, and current status.
