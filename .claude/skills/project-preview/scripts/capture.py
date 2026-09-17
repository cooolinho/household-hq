#!/usr/bin/env python3
"""Playwright (Chromium) helper of the project-preview skill.

    capture.py check    --url URL [--timeout 180]
    capture.py discover --plan PLAN.json [--out /work/discover.json] [--visit] [--max-pages 40]
    capture.py capture  --plan PLAN.json [--out /work/candidates] [--only slug,slug]

The plan format is documented in references/capture-plan.schema.md. Login credentials are only read from the
environment (PREVIEW_LOGIN_EMAIL / PREVIEW_LOGIN_PASSWORD) and never written anywhere.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
import sys
import time
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urljoin, urlparse

from playwright.sync_api import Error as PlaywrightError
from playwright.sync_api import TimeoutError as PlaywrightTimeout
from playwright.sync_api import sync_playwright

sys.path.insert(0, str(Path(__file__).resolve().parent))
from scan_sensitive import scan_text  # noqa: E402

FREEZE_CSS = """
*, *::before, *::after {
    animation-duration: 0s !important; animation-delay: 0s !important;
    transition-duration: 0s !important; transition-delay: 0s !important;
    caret-color: transparent !important;
}
html { scrollbar-width: none !important; }
::-webkit-scrollbar { display: none !important; }
"""

# Debug/dev overlays that must never appear in a public screenshot.
DEBUG_OVERLAYS = {
    ".phpdebugbar": "Laravel Debugbar",
    "#__laravel-telescope, .telescope": "Laravel Telescope",
    ".sf-toolbar": "Symfony profiler toolbar",
    "#clockwork-toolbar": "Clockwork",
    "vite-error-overlay": "Vite error overlay",
    "nextjs-portal": "Next.js dev overlay",
    "#webpack-dev-server-client-overlay": "Webpack overlay",
    "[data-ignition], #app > .ignition, .ignition-app": "Ignition error page",
}

ERROR_TITLE = re.compile(r"\b(server error|not found|page expired|forbidden|unauthorized|exception|error \d{3})\b", re.I)
SKIP_LINK = re.compile(r"logout|log-out|signout|sign-out|delete|destroy|remove|impersonate|/export|download", re.I)

DEFAULT_LINK_SELECTOR = "nav a[href], aside a[href], header a[href], [role=navigation] a[href]"


def log(message: str) -> None:
    print(message, file=sys.stderr, flush=True)


def load_plan(path: str) -> dict:
    plan = json.loads(Path(path).read_text(encoding="utf-8"))
    if not plan.get("base_url"):
        raise SystemExit("plan: base_url is required")
    plan["base_url"] = plan["base_url"].rstrip("/") + "/"
    return plan


def absolute(plan: dict, path: str) -> str:
    return urljoin(plan["base_url"], path.lstrip("/"))


def strip_query(url: str) -> str:
    parsed = urlparse(url)
    return f"{parsed.scheme}://{parsed.netloc}{parsed.path}"


def new_context(browser, plan: dict):
    viewport = plan.get("viewport") or {"width": 1440, "height": 900}
    if viewport["width"] < 1440 or viewport["height"] < 900:
        raise SystemExit("plan: viewport must be at least 1440x900")
    return browser.new_context(
        viewport=viewport,
        device_scale_factor=plan.get("device_scale_factor", 1),
        color_scheme=plan.get("color_scheme", "light"),
        locale=plan.get("locale", "en-US"),
        timezone_id=plan.get("timezone", "UTC"),
        reduced_motion="reduce",
        ignore_https_errors=True,
        extra_http_headers=plan.get("extra_http_headers") or None,
    )


def settle_network(page, timeout: int = 15000) -> bool:
    try:
        page.wait_for_load_state("networkidle", timeout=timeout)
        return True
    except PlaywrightTimeout:
        return False


def goto(page, url: str, timeout: int = 30000):
    """Navigate with networkidle; fall back to load + explicit idle wait if the page keeps the network busy."""
    try:
        return page.goto(url, wait_until="networkidle", timeout=timeout), True
    except PlaywrightTimeout:
        response = page.goto(url, wait_until="load", timeout=timeout)
        return response, settle_network(page, 10000)


def login(page, plan: dict) -> None:
    cfg = plan.get("login")
    if not cfg:
        return
    email = os.environ.get("PREVIEW_LOGIN_EMAIL")
    password = os.environ.get("PREVIEW_LOGIN_PASSWORD")
    if not email or not password:
        raise SystemExit("plan has a login section, but PREVIEW_LOGIN_EMAIL / PREVIEW_LOGIN_PASSWORD are not set")

    login_url = absolute(plan, cfg["url"])
    goto(page, login_url)
    page.locator(cfg.get("username_selector", "input[type=email], input[name=email], input[name=username]")).first.fill(email)
    page.locator(cfg.get("password_selector", "input[type=password]")).first.fill(password)
    page.locator(cfg.get("submit_selector", "button[type=submit]")).first.click()

    timeout = cfg.get("timeout_ms", 20000)
    if cfg.get("success_selector"):
        page.wait_for_selector(cfg["success_selector"], state="visible", timeout=timeout)
    else:
        login_path = urlparse(login_url).path
        page.wait_for_url(lambda url: urlparse(url).path != login_path, timeout=timeout)
    settle_network(page)
    log(f"logged in, landed on {urlparse(page.url).path}")


def wait_fonts(page) -> None:
    try:
        page.evaluate("() => document.fonts ? document.fonts.ready.then(() => true) : true")
    except PlaywrightError:
        pass


def wait_visually_stable(page, timeout_ms: int = 8000, interval_ms: int = 400) -> bool:
    """Wait until two consecutive viewport screenshots are identical (charts finished animating, skeletons gone)."""
    deadline = time.monotonic() + timeout_ms / 1000
    previous = None
    while time.monotonic() < deadline:
        digest = hashlib.sha1(page.screenshot(animations="disabled")).hexdigest()
        if digest == previous:
            return True
        previous = digest
        page.wait_for_timeout(interval_ms)
    return False


def run_actions(page, actions: list[dict]) -> None:
    for action in actions or []:
        if "click" in action:
            page.locator(action["click"]).first.click()
        elif "hover" in action:
            page.locator(action["hover"]).first.hover()
        elif "fill" in action:
            page.locator(action["fill"]).first.fill(action.get("value", ""))
        elif "press" in action:
            page.keyboard.press(action["press"])
        elif "select" in action:
            page.locator(action["select"]).first.select_option(action.get("value"))
        elif "wait_for" in action:
            page.wait_for_selector(action["wait_for"], state=action.get("state", "visible"),
                                   timeout=action.get("timeout_ms", 15000))
        elif "wait_ms" in action:
            page.wait_for_timeout(action["wait_ms"])
        elif "scroll_to" in action:
            page.locator(action["scroll_to"]).first.scroll_into_view_if_needed()
        elif "remove" in action:
            # Only for transient UI noise (toasts, cookie banners) - never to conceal sensitive data.
            page.evaluate("(sel) => document.querySelectorAll(sel).forEach(el => el.remove())", action["remove"])
        else:
            raise SystemExit(f"unknown action: {action}")
        settle_network(page, 8000)


def trigger_lazy_content(page) -> None:
    """Scroll through the whole document so lazy widgets load (needed for full-page screenshots)."""
    height = page.evaluate("() => document.documentElement.scrollHeight")
    step = page.viewport_size["height"] // 2
    for y in range(0, height, step):
        page.evaluate("(y) => window.scrollTo(0, y)", y)
        page.wait_for_timeout(150)
    settle_network(page, 10000)
    page.evaluate("() => window.scrollTo(0, 0)")


def detect_overlays(page) -> list[str]:
    found = []
    for selector, name in DEBUG_OVERLAYS.items():
        try:
            if page.locator(selector).count():
                found.append(name)
        except PlaywrightError:
            pass
    return found


def attach_recorders(page, bucket: dict) -> None:
    def on_console(msg):
        if msg.type == "error":
            bucket["console_errors"].append(msg.text[:300])

    def on_failed(request):
        bucket["failed_requests"].append({"url": strip_query(request.url), "error": request.failure})

    def on_response(response):
        if response.status >= 400 and response.request.resource_type in ("document", "stylesheet", "script", "image", "font", "xhr", "fetch"):
            bucket["failed_requests"].append({"url": strip_query(response.url), "status": response.status})

    page.on("console", on_console)
    page.on("requestfailed", on_failed)
    page.on("response", on_response)


# --- commands ---------------------------------------------------------------------------------------------------

def cmd_check(args) -> int:
    deadline = time.monotonic() + args.timeout
    last_error = ""
    with sync_playwright() as pw:
        browser = pw.chromium.launch()
        page = browser.new_page()
        while time.monotonic() < deadline:
            try:
                response = page.goto(args.url, wait_until="load", timeout=15000)
                status = response.status if response else None
                if status and status < 400:
                    chain, request = [], response.request
                    while request.redirected_from:
                        request = request.redirected_from
                        chain.insert(0, strip_query(request.url))
                    print(json.dumps({"reachable": True, "status": status, "final_url": strip_query(page.url),
                                      "redirects": chain, "title": page.title()}, indent=2))
                    return 0
                last_error = f"HTTP {status}"
            except PlaywrightError as error:
                last_error = str(error).splitlines()[0]
            log(f"not reachable yet ({last_error}), retrying ...")
            time.sleep(3)
        browser.close()
    print(json.dumps({"reachable": False, "error": last_error}, indent=2))
    return 1


def cmd_discover(args) -> int:
    plan = load_plan(args.plan)
    discover_cfg = plan.get("discover") or {}
    selector = discover_cfg.get("link_selector", DEFAULT_LINK_SELECTOR)
    start_paths = discover_cfg.get("start_paths") or ["/"]
    origin = urlparse(plan["base_url"]).netloc

    with sync_playwright() as pw:
        browser = pw.chromium.launch()
        context = new_context(browser, plan)
        page = context.new_page()
        login(page, plan)

        links: dict[str, dict] = {}
        for start in start_paths:
            goto(page, absolute(plan, start))
            for link in page.eval_on_selector_all(selector, """els => els.map(a => ({
                href: a.href,
                label: (a.innerText || a.textContent || a.getAttribute('aria-label') || a.title || '').trim().replace(/\\s+/g, ' '),
                group: (a.closest('[data-group-label]')?.getAttribute('data-group-label')
                        || a.closest('li[class*=group], section, details')?.querySelector('span, summary, h3, h4')?.textContent || '')
                        .trim().replace(/\\s+/g, ' ').slice(0, 60),
            }))"""):
                parsed = urlparse(link["href"])
                if parsed.netloc != origin or not parsed.path or SKIP_LINK.search(parsed.path):
                    continue
                key = parsed.path.rstrip("/") or "/"
                links.setdefault(key, {"path": key, "label": link["label"], "group": link["group"]})

        pages = sorted(links.values(), key=lambda item: item["path"])
        if args.visit:
            for item in pages[: args.max_pages]:
                bucket = {"console_errors": [], "failed_requests": []}
                try:
                    response, idle = goto(page, absolute(plan, item["path"]))
                    wait_fonts(page)
                    item.update(page.evaluate("""() => ({
                        title: document.title,
                        heading: (document.querySelector('h1')?.innerText || '').trim(),
                        tables: document.querySelectorAll('table').length,
                        table_rows: document.querySelectorAll('table tbody tr').length,
                        charts: document.querySelectorAll('canvas, svg.recharts-surface, .apexcharts-canvas').length,
                        forms: document.querySelectorAll('form').length,
                        inputs: document.querySelectorAll('input, select, textarea').length,
                        text_length: document.body.innerText.length,
                    })"""))
                    item["status"] = response.status if response else None
                    item["network_idle"] = idle
                    item["final_path"] = urlparse(page.url).path
                    item["sensitive_hints"] = sorted({f["type"] for f in scan_text(page.evaluate("() => document.body.innerText"))})
                    item["debug_overlays"] = detect_overlays(page)
                except PlaywrightError as error:
                    item["error"] = str(error).splitlines()[0]
                log(f"visited {item['path']} -> {item.get('status')}")
        browser.close()

    result = {"base_url": plan["base_url"], "count": len(pages), "pages": pages}
    Path(args.out).write_text(json.dumps(result, indent=2, ensure_ascii=False), encoding="utf-8")
    print(json.dumps(result, indent=2, ensure_ascii=False))
    return 0


def cmd_capture(args) -> int:
    plan = load_plan(args.plan)
    out_dir = Path(args.out)
    out_dir.mkdir(parents=True, exist_ok=True)
    only = set(filter(None, (args.only or "").split(",")))
    viewport = plan.get("viewport") or {"width": 1440, "height": 900}
    report = {
        "captured_at": datetime.now(timezone.utc).isoformat(timespec="seconds"),
        "base_url": plan["base_url"],
        "viewport": viewport,
        "color_scheme": plan.get("color_scheme", "light"),
        "pages": [],
    }

    with sync_playwright() as pw:
        browser = pw.chromium.launch()
        context = new_context(browser, plan)
        page = context.new_page()
        bucket = {"console_errors": [], "failed_requests": []}
        attach_recorders(page, bucket)
        login(page, plan)

        for index, spec in enumerate(plan.get("pages", []), start=1):
            slug = spec["slug"]
            if only and slug not in only:
                continue
            bucket["console_errors"].clear()
            bucket["failed_requests"].clear()
            entry = {"slug": slug, "path": spec["path"], "file": None}
            try:
                response, idle = goto(page, absolute(plan, spec["path"]), spec.get("timeout_ms", 30000))
                page.add_style_tag(content=FREEZE_CSS)
                wait_fonts(page)
                run_actions(page, spec.get("actions"))
                if spec.get("wait_for"):
                    page.wait_for_selector(spec["wait_for"], state="visible", timeout=spec.get("timeout_ms", 30000))
                if spec.get("full_page"):
                    trigger_lazy_content(page)
                page.mouse.move(viewport["width"] - 4, viewport["height"] - 4)
                page.wait_for_timeout(spec.get("settle_ms", 800))
                settle_network(page, 8000)
                stable = wait_visually_stable(page)

                file = out_dir / f"{index:02d}-{slug}.png"
                if spec.get("element"):
                    page.locator(spec["element"]).first.screenshot(path=str(file), animations="disabled")
                elif spec.get("clip"):
                    page.screenshot(path=str(file), clip=spec["clip"], animations="disabled")
                else:
                    page.screenshot(path=str(file), full_page=bool(spec.get("full_page")), animations="disabled")

                title = page.title()
                status = response.status if response else None
                entry.update({
                    "file": str(file),
                    "status": status,
                    "final_path": urlparse(page.url).path,
                    "title": title,
                    "network_idle": idle,
                    "visually_stable": stable,
                    "error_page_suspected": bool((status and status >= 400) or ERROR_TITLE.search(title)),
                    "debug_overlays": detect_overlays(page),
                    "sensitive": scan_text(page.evaluate("() => document.body.innerText")),
                })
            except (PlaywrightError, SystemExit) as error:
                entry["error"] = str(error).splitlines()[0]
            entry["console_errors"] = list(bucket["console_errors"])
            entry["failed_requests"] = list(bucket["failed_requests"])
            report["pages"].append(entry)
            log(f"{slug}: {'ERROR ' + entry['error'] if entry.get('error') else entry['file']}")
        browser.close()

    report_file = out_dir / "report.json"
    if only and report_file.is_file():
        # Partial re-capture: keep the entries of all other pages from the previous run.
        previous = json.loads(report_file.read_text(encoding="utf-8"))
        fresh = {p["slug"]: p for p in report["pages"]}
        merged = [fresh.pop(p["slug"], p) for p in previous.get("pages", [])]
        report["pages"] = merged + list(fresh.values())
    report_file.write_text(json.dumps(report, indent=2, ensure_ascii=False), encoding="utf-8")
    summary = [{
        "slug": p["slug"], "file": p.get("file"), "status": p.get("status"), "error": p.get("error"),
        "error_page_suspected": p.get("error_page_suspected"), "visually_stable": p.get("visually_stable"),
        "console_errors": len(p["console_errors"]), "failed_requests": len(p["failed_requests"]),
        "debug_overlays": p.get("debug_overlays"), "sensitive_types": sorted({f["type"] for f in p.get("sensitive", [])}),
    } for p in report["pages"]]
    print(json.dumps({"report": str(report_file), "pages": summary}, indent=2, ensure_ascii=False))
    return 0 if all(not p.get("error") for p in report["pages"]) else 1


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    sub = parser.add_subparsers(dest="command", required=True)

    check = sub.add_parser("check")
    check.add_argument("--url", required=True)
    check.add_argument("--timeout", type=int, default=180)

    discover = sub.add_parser("discover")
    discover.add_argument("--plan", required=True)
    discover.add_argument("--out", default="/work/discover.json")
    discover.add_argument("--visit", action="store_true", help="open every link and collect page metrics")
    discover.add_argument("--max-pages", type=int, default=40)

    capture = sub.add_parser("capture")
    capture.add_argument("--plan", required=True)
    capture.add_argument("--out", default="/work/candidates")
    capture.add_argument("--only", help="comma separated slugs")

    args = parser.parse_args()
    return {"check": cmd_check, "discover": cmd_discover, "capture": cmd_capture}[args.command](args)


if __name__ == "__main__":
    sys.exit(main())
