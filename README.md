# Super Ninety Four — WordPress

Local WordPress site backed by MySQL 8.4.

## Prerequisites

- PHP 8.3+ with `mysqli` enabled
- MySQL 8.4 service `MySQL84` (installed and configured for this project)

## Start the site

```powershell
.\scripts\start-server.ps1
```

Then open [http://localhost:8080](http://localhost:8080).

## Database (local)

| Setting  | Value        |
|----------|--------------|
| Host     | `127.0.0.1`  |
| Database | `wordpress`  |
| User     | `wpuser`     |
| Password | see `.mysql-local.json` / `wp-config.php` |

Credentials are also stored in `.mysql-local.json` (gitignored).

## Admin login

- URL: [http://localhost:8080/wp-admin](http://localhost:8080/wp-admin)
- User: `admin`
- Password: `admin123!`

Change this password after first login.

## Notes

- `wp-config.php` is gitignored — keep local credentials out of git.
- Custom themes/plugins belong in `wp-content/themes` and `wp-content/plugins`.
- WP-CLI is available as `php wp-cli.phar ...` in the project root.
