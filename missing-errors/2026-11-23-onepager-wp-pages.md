# One-pager theme: leftover WP pages

| Item | Path / detail | Impact | Status |
|------|---------------|--------|--------|
| Theme home was old multi-section layout | `front-page.php` still rendered Figma icons/search/spotlight while `snf_get_home_content()` was already one-pager | WP home missing Help + Gatherings; dead section markup | fixed |
| Primary nav still linked earlier marketing pages | `app/Services/theme-menus.php` Expo / Events / Inquiries / About / Videos / Contact / Newsletter | Deleted-looking pages stayed visible in header | fixed (nav → one-pager anchors + Apply) |
| `snf_ensure_site_pages()` recreates WP pages | `functions.php` → `snf_get_site_pages()` | Pages removed in WP admin come back on `init` | mitigated — About/Contact/Expo removed from ensure + obsolete list |
| About / Contact / Expo WP pages | Deleted from DB; events / reciprocal / inquiries kept | Old `/about` `/contact` `/expo` URLs 404 | fixed |
