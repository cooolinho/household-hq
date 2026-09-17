# Capture plan (`plan.json`)

Used by `scripts/capture.py` for `discover` and `capture`. The plan lives in the working directory (never in the
repo) and contains **no credentials**. The login is passed as `PREVIEW_LOGIN_EMAIL` / `PREVIEW_LOGIN_PASSWORD`
environment variables, which `playwright-run.sh` forwards by name only.

## Fields

| Field | Required | Default | Meaning |
|---|---|---|---|
| `base_url` | yes | – | URL as seen **from the browser container**, e.g. `http://app-container` or the reverse-proxy host |
| `viewport` | no | `{"width":1440,"height":900}` | at least 1440×900 |
| `device_scale_factor` | no | `1` | `2` only if the preview needs sharper secondary images (4× file size) |
| `color_scheme` | no | `light` | `light` / `dark`. Always set explicitly if the app follows the system theme |
| `locale` / `timezone` | no | `en-US` / `UTC` | match the app's default locale so dates and numbers look native |
| `extra_http_headers` | no | – | e.g. `{"Host": "app.example.test"}` behind a reverse proxy |
| `login.url` | no | – | login page path. If `login` is missing, no login happens |
| `login.username_selector` | no | `input[type=email], input[name=email], input[name=username]` | |
| `login.password_selector` | no | `input[type=password]` | |
| `login.submit_selector` | no | `button[type=submit]` | |
| `login.success_selector` | no | – | element that proves the login worked; otherwise waits for a URL change |
| `discover.start_paths` | no | `["/"]` | pages whose navigation is collected |
| `discover.link_selector` | no | `nav a[href], aside a[href], header a[href], [role=navigation] a[href]` | |
| `pages[]` | for capture | – | see below |

### `pages[]`

| Field | Meaning |
|---|---|
| `slug` | file name part, kebab-case (`dashboard` → `NN-dashboard.png`) |
| `path` | path relative to `base_url` |
| `wait_for` | selector that must be visible before the screenshot (charts, tables, lazy widgets) |
| `settle_ms` | extra wait after loading (chart animations), default `800` |
| `timeout_ms` | navigation/selector timeout, default `30000` |
| `actions[]` | executed in order: `{"click": sel}`, `{"hover": sel}`, `{"fill": sel, "value": "test data"}`, `{"press": "Escape"}`, `{"select": sel, "value": v}`, `{"wait_for": sel}`, `{"wait_ms": n}`, `{"scroll_to": sel}`, `{"remove": sel}` |
| `full_page` | `true` for a full-page screenshot. Only use it where the page really needs it |
| `clip` | `{"x":0,"y":0,"width":1440,"height":900}`, a fixed section |
| `element` | selector: screenshot of just this element |

Without `full_page`/`clip`/`element`, the screenshot is the viewport section (preferred for presentations).

`remove` is only meant for transient noise (toasts, cookie banners). **Never** use it to hide sensitive data. Instead,
use test data or drop the page.

## Example (Filament panel in Docker)

```json
{
  "base_url": "http://personal-home-portal",
  "viewport": {"width": 1440, "height": 900},
  "color_scheme": "light",
  "locale": "de-DE",
  "timezone": "Europe/Berlin",
  "login": {"url": "/app/login", "success_selector": ".fi-sidebar"},
  "discover": {"start_paths": ["/app"], "link_selector": ".fi-sidebar a[href], .fi-topbar a[href]"},
  "pages": [
    {"slug": "dashboard", "path": "/app", "wait_for": ".fi-wi-chart canvas", "settle_ms": 1500},
    {"slug": "transactions", "path": "/app/financial/transactions", "wait_for": ".fi-ta-table", "settle_ms": 1200},
    {"slug": "device-detail", "path": "/app/energy-tracker/measurement-devices",
     "actions": [{"click": "a:has-text('Details')"}, {"wait_for": ".fi-wi-stats-overview"}], "settle_ms": 1500}
  ]
}
```

## Outputs

- `discover --visit` → `discover.json`: per link `path`, `label`, `group`, `status`, `title`, `heading`, `tables`,
  `table_rows`, `charts`, `forms`, `inputs`, `text_length`, `sensitive_hints`, `debug_overlays`
- `capture` → `candidates/NN-slug.png` + `candidates/report.json`: per page `status`, `final_path`, `title`,
  `network_idle`, `visually_stable`, `error_page_suspected`, `debug_overlays`, `sensitive[]` (masked),
  `console_errors[]`, `failed_requests[]` (without query strings). `--only a,b` captures again and merges the
  report.
