#!/usr/bin/env python3
"""Heuristic scan of visible page text for data that must not end up in public screenshots.

The findings are hints, not verdicts: every hit has to be judged by looking at the screenshot. Matches are masked in
the output so the report itself does not leak anything.

    scan_sensitive.py FILE [FILE...]      # prints a JSON list of findings per file
"""
from __future__ import annotations

import json
import re
import sys

ALLOWED_EMAIL_DOMAIN = re.compile(
    r"(^|\.)(example\.(com|org|net)|example|test|invalid|localhost|local)$", re.IGNORECASE
)

PATTERNS = [
    ("email", re.compile(r"\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b")),
    ("iban", re.compile(r"\b[A-Z]{2}\d{2}(?: ?[A-Z0-9]{4}){3,7}(?: ?[A-Z0-9]{1,3})?\b")),
    ("card_number", re.compile(r"(?<![\d.,])(?:\d[ -]?){12,18}\d(?![\d.,])")),
    ("private_ip", re.compile(
        r"\b(?:10\.\d{1,3}|127\.\d{1,3}|192\.168|172\.(?:1[6-9]|2\d|3[01]))\.\d{1,3}\.\d{1,3}\b"
    )),
    ("jwt", re.compile(r"\beyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}")),
    ("api_key", re.compile(
        r"\b(?:sk-(?:proj-)?[A-Za-z0-9_-]{20,}|ghp_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{30,}"
        r"|xox[abprs]-[A-Za-z0-9-]{10,}|AKIA[0-9A-Z]{16}|AIza[0-9A-Za-z_-]{35}|glpat-[A-Za-z0-9_-]{20,})"
    )),
    ("app_key", re.compile(r"\bbase64:[A-Za-z0-9+/]{40,}={0,2}")),
    ("secret_assignment", re.compile(
        r"\b(?:APP_KEY|DB_PASSWORD|DB_USERNAME|MAIL_PASSWORD|REDIS_PASSWORD|[A-Z][A-Z0-9_]*(?:SECRET|TOKEN|PASSWORD|API_KEY))"
        r"\s*[=:]\s*\S+"
    )),
    ("env_line", re.compile(r"(?m)^[A-Z][A-Z0-9_]{2,}=\S+$")),
    ("long_token", re.compile(r"\b(?=[A-Za-z0-9_-]*\d)(?=[A-Za-z0-9_-]*[A-Za-z])[A-Za-z0-9_-]{40,}\b|\b[a-f0-9]{32,}\b")),
    ("database_dsn", re.compile(r"\b(?:mysql|mariadb|postgres(?:ql)?|mongodb(?:\+srv)?|redis|amqp)://\S+", re.IGNORECASE)),
    ("debug_output", re.compile(
        r"SQLSTATE\[|Stack trace:|Whoops, looks like something went wrong|Traceback \(most recent call last\)"
        r"|\bIlluminate\\[A-Z]|/vendor/[a-z0-9_-]+/|Call to undefined|Undefined (?:variable|array key|index)"
    )),
    ("phone", re.compile(r"(?<![\w.,/-])(?:\+\d{2,3}|0\d{2,4})[ /-]\d{2,4}[ /-]?\d{3,8}(?![\w.,/-])")),
]

MAX_PER_TYPE = 10


def luhn_ok(digits: str) -> bool:
    total, parity = 0, len(digits) % 2
    for i, ch in enumerate(digits):
        d = int(ch)
        if i % 2 == parity:
            d *= 2
            if d > 9:
                d -= 9
        total += d
    return total % 10 == 0


def mask(value: str) -> str:
    value = value.strip()
    if len(value) <= 6:
        return value[0] + "*" * (len(value) - 1)
    keep = 3 if len(value) < 20 else 4
    return value[:keep] + "*" * min(len(value) - keep - 2, 12) + value[-2:]


def scan_text(text: str) -> list[dict]:
    findings: list[dict] = []
    for kind, pattern in PATTERNS:
        count = 0
        for match in pattern.finditer(text):
            value = match.group(0)
            if kind == "email" and ALLOWED_EMAIL_DOMAIN.search(value.rsplit("@", 1)[1]):
                continue
            if kind == "card_number":
                digits = re.sub(r"\D", "", value)
                if not 13 <= len(digits) <= 19 or not luhn_ok(digits):
                    continue
            start, end = max(0, match.start() - 30), min(len(text), match.end() + 30)
            context = text[start:end].replace(value, mask(value)).replace("\n", " ")
            findings.append({"type": kind, "match": mask(value), "context": context.strip()})
            count += 1
            if count >= MAX_PER_TYPE:
                break
    return findings


def main(paths: list[str]) -> int:
    if not paths:
        print(__doc__, file=sys.stderr)
        return 2
    result = {path: scan_text(open(path, encoding="utf-8", errors="replace").read()) for path in paths}
    print(json.dumps(result, indent=2, ensure_ascii=False))
    return 1 if any(result.values()) else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
