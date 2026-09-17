---
name: project-preview
description: Creates a professional project preview image for the README from REAL screenshots of the running app. Analyzes the project and Docker Compose setup, starts or reaches the app, captures screenshots with Playwright/Chromium (1440x900) inside a temporary Playwright container, picks the best ones, has OpenAI generate only a text- and UI-free background, composes real screenshots and typography with Pillow, checks everything for secrets/personal data, and embeds docs/project-preview.png in README.md. Use for "README preview", "project presentation", "screenshots for the README", "project showcase image", "hero image for GitHub", "README-Vorschau", "Projektpräsentation", "Screenshots für die README", "Vorschaubild erstellen".
---

# Project Preview: real screenshots → README presentation

Binding priorities, in this order: **1. authenticity of the app, 2. correctness of the screenshots, 3. security /
no secrets, 4. expressiveness, 5. design.** A nicer image never justifies a misrepresentation.

Result:
```
docs/screenshots/01-<slug>.png …   real screenshots (PNG, 1440×900)
docs/project-preview.png           final presentation (PNG, 1920×1080)
README.md                          + ![Project Preview](docs/project-preview.png)
```
If the project already has a different docs structure (e.g. `documentation/`, `.github/assets/`), use that instead.

## Ground rules

- Work autonomously. Only ask if the app cannot be shown safely any other way (e.g. no demo/test data available
  at all and only real personal data visible).
- Determine everything from the project (files, `docker inspect`) instead of assuming it.
- **No new permanent infrastructure**: browser and image processing run in a temporary
  `mcr.microsoft.com/playwright/python` container (`--rm`). The host only needs `docker` and `python3`.
- Never touch production compose files, never run `docker compose down`, never destructive DB commands
  (`migrate:fresh`, `db:wipe`, reset/truncate commands). Demo data only via documented, idempotent seeders.
- Credentials of demo users only via environment variables, never in files. The OpenAI key is never printed.
- Do not commit. Changes stay in the working tree for review.

Variables used below:
```bash
SKILL=<directory of this SKILL.md>          # e.g. .claude/skills/project-preview
RUN="$SKILL/scripts/playwright-run.sh"
WORK=<session scratchpad>/project-preview   # otherwise: WORK=$(mktemp -d -t project-preview-XXXX); never inside the repo
```
`$RUN --workdir "$WORK" [--network NET] [--add-host H:IP] [--openai] SCRIPT ARGS` mounts the project as `/project`,
the skill as `/skill` and `$WORK` as `/work`. Paths in plan/spec files are container paths. On the first run the
wrapper installs `playwright` (matching the image version), `pillow` and `openai` into `$WORK/.pydeps-<version>`.

---

## Phase 1 – Project analysis

Read (if present): `README.md`, `AGENTS.md`/`CLAUDE.md`, compose files, `Dockerfile`s, `package.json`,
`composer.json`, `pyproject.toml`/`requirements.txt`, `.env.example` (**never** print secret values from `.env`,
at most check whether keys exist and read `APP_NAME`/`APP_URL`/`APP_DEBUG`-like values).

Routing and UI by framework, for example:
- Laravel/Filament: `routes/*.php`, `app/Providers/Filament/*PanelProvider.php` (path, login, dark mode, SPA),
  `app/Filament/**/{Resources,Pages,Widgets,Clusters}`; `php artisan route:list` inside the app container.
  Watch for resources hidden from the navigation (`shouldRegisterNavigation = false`): they are often core features.
- Symfony: `config/routes*`, `#[Route]` attributes, `templates/`. Next/Nuxt/SvelteKit: `app/`, `pages/`, `routes/`.
- SPA: router configuration (`router.ts`, `routes.tsx`), `src/views`, `src/components`.

Determine and note in `$WORK/analysis.md`:
framework + version, startup process, relevant containers, web server, internal/external port, URL, login
mechanism, **demo/test data and demo logins** (seeders, fixtures, README), core features (from README/docs, not
invented), key UI areas, **risky pages** (profile, user management, logs, mail, settings with credentials,
bank/payment details, queue/debug dashboards), language of the README.

## Phase 2 – Analyze Docker

Compose file lookup order in the project root: `compose.yaml`, `compose.yml`, `docker-compose.yaml`,
`docker-compose.yml`. Only read `*.prod.*`, `*.override.*` and variants for context; do not start them.

```bash
docker ps
docker compose ps
docker inspect <app-container> --format '{{json .NetworkSettings.Networks}}'
docker inspect <app-container> --format '{{json .Config.Labels}}'   # compose project, Traefik/proxy labels
docker network ls
```
Identify the actual web container (web server/PHP/Node, not DB/cache/mail). Determine the browser URL:
1. **Reverse proxy** (Traefik/nginx/Caddy container in the project) is preferred. Join the proxy network and
   resolve the host name via `--add-host <domain>:<proxy-ip>` or `extra_http_headers.Host`.
2. Otherwise **compose network + container name/alias + internal port**: `--network <network>` and
   `base_url: http://<alias>:<internal-port>` (port 80 can be omitted).
3. Only if absolute URLs in the app are hard-wired to `localhost:<port>` (asset 404s in `report.json`): use
   `--add-host host.docker.internal:host-gateway` and `http://host.docker.internal:<host-port>`.
   Avoid `--network host`, it is unreliable on Docker Desktop.

If a Playwright/Chromium container is already running in the project, `docker exec` into it may be used instead of
the wrapper (the scripts are then passed in via `docker cp`).

## Phase 3 – Start the application

Only if the web container is not running:
```bash
docker compose -f <dev-compose-file> up -d
docker compose ps
docker logs --tail 200 <container>        # on problems
```
Then wait until it is actually reachable (polls up to 180 s, follows redirects):
```bash
$RUN --workdir "$WORK" --network <net> capture.py check --url <base_url>/
```
`reachable: false` → evaluate `docker logs`, fix the cause if it is obvious and non-destructive (e.g. container
still booting), otherwise document it in the final report and stop.

Check demo data: are the demo users and data referenced in the project present? If not, run the documented
**idempotent, non-interactive** seeder (e.g. `php artisan db:seed`, `npm run seed`). Interactive commands are
unsuitable.

## Phase 4 – Screenshots with Playwright

1. Create `$WORK/plan.json` (format: `references/capture-plan.schema.md`): `base_url`, viewport **1440×900**,
   explicit `color_scheme`, the app's `locale`/`timezone`, `login`, `discover`.
2. Log in as the **demo/test user** that owns the demo data (not an admin with real accounts):
   ```bash
   export PREVIEW_LOGIN_EMAIL=... PREVIEW_LOGIN_PASSWORD=...
   $RUN --workdir "$WORK" --network <net> capture.py discover --plan /work/plan.json --visit
   ```
   `discover.json` lists all navigation targets with status, headings, tables/charts/forms and sensitivity hints.
3. Choose candidates. Base the choice on phase 1 (core features, including pages hidden from the navigation) and the
   metrics (charts/tables with content > empty lists, forms and settings). Skip risky pages or check them especially
   carefully later. Enter them as `pages[]` with suitable `wait_for` (charts: `canvas`, tables, lazy widgets) and
   `settle_ms`. Use `actions` for detail views/tabs.
4. Capture:
   ```bash
   $RUN --workdir "$WORK" --network <net> capture.py capture --plan /work/plan.json --out /work/candidates
   ```
   The script waits for `networkidle` (fallback `load` + idle), fonts and visual stability, disables animations and
   records per page: status, error-page suspicion, debug overlays, console errors, failed requests, sensitivity
   hints. Capture failed or unsuitable pages again with `--only slug1,slug2` (the report is merged).

Do not force a number of screenshots: 3–6 good ones are better than 10 mediocre ones.

## Phase 5 – Selection

Look at **every** candidate with the Read tool and cross-check it against `report.json`. Reject immediately if:
error page, `error_page_suspected`, spinner/skeleton/half-loaded charts, failed asset requests with visible impact,
empty lists or mostly blank space, debug overlays, open dropdowns/toasts, sensitive data (phase 8).

Rate the rest from 1–5 per criterion and show the table in the chat:

| Candidate | Expressiveness | Visual quality | Relevance | Core feature | GitHub suitability | Σ |
|---|---|---|---|---|---|---|

Adopt:
```bash
# only replace files previously produced by this skill
find docs/screenshots -maxdepth 1 -regextype posix-extended -regex '.*/[0-9]{2}-[a-z0-9-]+\.png' -delete 2>/dev/null
mkdir -p docs/screenshots && cp "$WORK/candidates/<nn>-<slug>.png" docs/screenshots/01-<slug>.png   # numbered by relevance
```
For the presentation: **1 hero** (highest score, ideally the dashboard/overview) + **1–2 secondary images** that
show *other* core features.

## Phase 6 – Generate the presentation

### 6.1 Texts
Only facts from the README/docs/analysis and what is visible in the screenshots. Language = language of the README.
- `title`: project/app name exactly as the project uses it (e.g. `APP_NAME`, README H1). No new names or logos.
- `eyebrow` (optional): real tech stack, e.g. `Laravel 13 · Filament 5 · Docker`
- `tagline`: one objective sentence, no marketing superlatives
- `features`: 3–5 short lines, each backed by a screenshot or navigation item

### 6.2 AI background (only if an OpenAI key is available)
Requirement: the screenshots **passed the phase 8 check** (they leave the machine as reference images).
```bash
$RUN --workdir "$WORK" --openai generate_background.py \
     --screenshots /project/docs/screenshots/01-….png /project/docs/screenshots/02-….png --out /work/background.png
```
The wrapper resolves the key from `OPENAI_API_KEY`, or else from `.claude/settings.local.json`
(`env.OPENAI_API_KEY`, then `environment.variables.OPENAI_API_KEY`) and warns if that file is not git-ignored. Model
chain `gpt-image-2` → `gpt-image-1.5` → `gpt-image-1` (override: `OPENAI_IMAGE_MODEL`). The prompt
(`references/background-prompt.md`) only allows an abstract stage: **no text, no UI, no devices, no logos.**
Exit code ≠ 0 or `key: not found` → go straight to the deterministic background.

### 6.3 Fidelity and suitability gate
Look at `background.png` with the Read tool. **Reject** if text-like shapes, UI fragments, devices, logos or brand
marks are visible, if the left 40% is not calm, **or if the stage gives no contrast to the screenshots** (e.g. a
white stage behind a light UI). At most **two** AI attempts in total (second one optionally `--mode text-only`),
after that the deterministic background.

### 6.4 Composition (deterministic, Pillow)
`$WORK/compose.json`:
```json
{
  "title": "…", "eyebrow": "…", "tagline": "…", "features": ["…"],
  "screenshots": ["/project/docs/screenshots/01-….png", "/project/docs/screenshots/02-….png", "/project/docs/screenshots/03-….png"],
  "background": "/work/background.png",
  "font": "/project/<the app's own font file, if present, e.g. a variable Inter .woff2/.ttf>",
  "output": "/project/docs/project-preview.png",
  "size": [1920, 1080]
}
```
```bash
$RUN --workdir "$WORK" compose.py --spec /work/compose.json            # without an AI background: add --no-ai
```
`compose.py` only scales screenshots proportionally and frames them (rounded corners, 1px border, shadow). It never
changes their pixels. The accent colour comes from the real UI. Fonts: `font` from the spec (the project font),
otherwise `fc-match` (Inter → Ubuntu → Noto Sans → DejaVu Sans → Liberation Sans). `--webp` also writes a `.webp`.

### 6.5 Final check
Look at `docs/project-preview.png` with the Read tool: title/texts correct and readable, hero screenshot
recognisable, no cut-off fragments that look like errors, no AI artefacts with text/UI character. If a problem
comes from the layout, adjust `compose.json` (other screenshots/fewer features) instead of editing the image.

## Phase 7 – Update the README

Insert, with blank lines before and after, directly after the H1 (or after a following description paragraph, or
replacing a clearly marked existing preview/hero image):
```markdown
![Project Preview](docs/project-preview.png)
```
Only a relative path. Idempotent: if the README already references `docs/project-preview.png`, change nothing. Do
not rewrite any other README content.

## Phase 8 – Security check (mandatory, before 6.2 and again at the end)

Must not be visible in `docs/screenshots/*.png` or the preview:
API keys, passwords, tokens, session cookies, real email addresses (`example.com`, `.example`, `.test` are demo
domains), internal IP addresses, personal data (names, addresses, phone numbers, IBAN/card numbers of real people),
debug output/stack traces, database information, `.env` contents.

Procedure:
1. Review `report.json` → `sensitive[]`, `debug_overlays`, `error_page_suspected` per selected screenshot.
   The scan (`scripts/scan_sensitive.py`) only gives hints. Assess every hit visually.
2. View every final screenshot and the preview with the Read tool. Also check the user menu/avatar, notifications,
   breadcrumbs and table cells.
3. On a hit: **capture again with a demo user/demo data**, or **drop the page**. Do not blur or pixelate when a
   clean screenshot with test data is possible.
4. `$WORK` stays outside the repo. `git status` may only show `docs/screenshots/`, `docs/project-preview.png`,
   `README.md` (plus skill changes, if any).

## Final report (short)

```
URL:            <base_url> (via <network / proxy>)
Containers:     <web container> (+ temporary <playwright image>)
Screenshots:    docs/screenshots/01-….png, …  (candidates: n, adopted: m)
Presentation:   docs/project-preview.png (1920×1080, <ai-background model | deterministic>)
README:         <line inserted after H1 | unchanged, already present>
Problems:       <e.g. rejected AI background (reason), skipped pages (reason), none>
```

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| `venv`/`ensurepip` errors | irrelevant: the wrapper uses `pip install --target`, no venv |
| Assets 404 / page unstyled | app generates absolute URLs to `localhost:<port>` → Phase 2, option 3, or a `Host` header |
| `networkidle` never reached | websockets/polling: set `wait_for` + `settle_ms`; the script falls back to `load` |
| Login fails | check selectors in `login`, `success_selector`; demo user missing → run the seeder (phase 3) |
| Charts empty/half drawn | `wait_for: "canvas"` + higher `settle_ms`; lazy widgets need scrolling (`full_page` or `scroll_to`) |
| Screenshots dark although light wanted | app follows the system theme → set `color_scheme` explicitly |
| OpenAI `model not usable` | chain falls through to the next model; set `OPENAI_IMAGE_MODEL` if needed |
| Permission error writing to `/project` | container runs as `$(id -u):$(id -g)`; the target directory must be writable by that user |
