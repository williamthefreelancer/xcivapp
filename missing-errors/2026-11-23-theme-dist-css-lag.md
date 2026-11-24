# Theme `dist/main.css` lag behind source

| Item | Detail |
|------|--------|
| What | WP front loads `dist/main.css` (Vite build). Edits to `assets/css/main.css` alone do not appear until `npm run build` in the theme. |
| Paths | `wp-content/themes/super-ninety-four/assets/css/main.css` → imported by `resources/css/app.css` → built to `dist/main.css` via `Assets.php` |
| Impact | Home newsletter section shipped unstyled (invisible inputs, no panel) while source CSS looked correct |
| Status | mitigated — rebuilt after newsletter redesign; remember to rebuild after theme CSS changes |

## Guard

After changing `assets/css/main.css` (or anything imported into `resources/css/app.css`):

```bash
cd wp-content/themes/super-ninety-four && npm run build
```
