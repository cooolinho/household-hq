#!/usr/bin/env python3
"""Generates the text-free, UI-free presentation backdrop with the OpenAI Image API.

    generate_background.py --screenshots A.png [B.png C.png] [--out /work/background.png]
                           [--mode reference|text-only] [--mood "calm, dark, professional"]

The AI only paints the stage. Real screenshots and all typography are added deterministically by compose.py.
  reference  - images.edit with the screenshots as colour/mood reference (default)
  text-only  - images.generate with a palette extracted from the screenshots, no image upload

Exit codes: 0 ok, 2 no OPENAI_API_KEY, 3 authentication failed, 4 API/other error.
"""
from __future__ import annotations

import argparse
import base64
import json
import os
import re
import sys
from contextlib import ExitStack
from pathlib import Path

from PIL import Image

sys.path.insert(0, str(Path(__file__).resolve().parent))
from compose import accent_colors  # noqa: E402

PROMPT_FILE = Path(__file__).resolve().parent.parent / "references" / "background-prompt.md"
MODEL_CHAIN = ["gpt-image-2", "gpt-image-1.5", "gpt-image-1"]


def palette(paths: list[str]) -> dict:
    """Base colours (most frequent), accent colours (characteristic saturated hues) and overall brightness."""
    images = [Image.open(path).convert("RGB") for path in paths]
    counts: dict[tuple, int] = {}
    for image in images:
        small = image.copy()
        small.thumbnail((360, 360))
        quantized = small.quantize(colors=8, method=Image.Quantize.MEDIANCUT)
        pal = quantized.getpalette()
        for count, index in quantized.getcolors():
            rgb = tuple(pal[index * 3:index * 3 + 3])
            counts[rgb] = counts.get(rgb, 0) + count
    total = sum(counts.values())
    brightness = sum(count * sum(rgb) / 765 for rgb, count in counts.items()) / total
    to_hex = lambda rgb: "#%02x%02x%02x" % tuple(rgb)
    return {
        "base": [to_hex(rgb) for rgb, _ in sorted(counts.items(), key=lambda item: -item[1])[:3]],
        "accent": [to_hex(rgb) for rgb in accent_colors(images)],
        "ui_is_light": brightness > 0.6,
    }


def load_prompt(mode: str, colors: dict, mood: str) -> str:
    text = PROMPT_FILE.read_text(encoding="utf-8")
    blocks = dict(re.findall(r"<!-- prompt:(\w[\w-]*) -->\s*```text\n(.*?)```", text, flags=re.S))
    template = blocks.get(mode)
    if not template:
        raise SystemExit(f"prompt block '{mode}' missing in {PROMPT_FILE}")
    contrast = ("The screenshots placed on it later are predominantly light and white, so the stage must be clearly "
                "darker and low-key (deep charcoal, ink or navy tones) to give them contrast. No large white areas."
                if colors["ui_is_light"] else
                "The screenshots placed on it later are predominantly dark, so the stage should be lighter and airy "
                "to give them contrast.")
    return template.format(
        base=", ".join(colors["base"]) or "neutral greys",
        accent=", ".join(colors["accent"]) or "a single subtle cool tone",
        contrast=contrast,
        mood=mood,
    ).strip()


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--screenshots", nargs="+", required=True)
    parser.add_argument("--out", default="/work/background.png")
    parser.add_argument("--mode", choices=["reference", "text-only"], default="reference")
    parser.add_argument("--mood", default="calm, modern, premium software product showcase")
    parser.add_argument("--size", default="1536x1024")
    parser.add_argument("--quality", default="high")
    args = parser.parse_args()

    key = os.environ.get("OPENAI_API_KEY", "")
    if not key:
        print(json.dumps({"ok": False, "reason": "OPENAI_API_KEY not set"}))
        return 2

    import openai

    screenshots = args.screenshots[:3]
    colors = palette(screenshots)
    prompt = load_prompt(args.mode, colors, args.mood)
    models = [os.environ["OPENAI_IMAGE_MODEL"]] if os.environ.get("OPENAI_IMAGE_MODEL") else MODEL_CHAIN
    client = openai.OpenAI()

    def scrub(message: str) -> str:
        return message.replace(key, "***")[:400]

    last_error = ""
    for model in models:
        try:
            if args.mode == "reference":
                with ExitStack() as stack:
                    files = [stack.enter_context(open(path, "rb")) for path in screenshots]
                    response = client.images.edit(model=model, image=files, prompt=prompt, size=args.size,
                                                  quality=args.quality, n=1)
            else:
                response = client.images.generate(model=model, prompt=prompt, size=args.size,
                                                   quality=args.quality, n=1)
        except openai.AuthenticationError as error:
            print(json.dumps({"ok": False, "reason": "authentication failed", "detail": scrub(str(error))}))
            return 3
        except (openai.NotFoundError, openai.BadRequestError, openai.PermissionDeniedError) as error:
            last_error = scrub(str(error))
            print(f"model {model} not usable: {last_error}", file=sys.stderr)
            continue
        except Exception as error:  # network, rate limit, server errors
            print(json.dumps({"ok": False, "reason": type(error).__name__, "detail": scrub(str(error))}))
            return 4

        data = response.data[0]
        if getattr(data, "b64_json", None):
            raw = base64.b64decode(data.b64_json)
        else:
            import urllib.request
            raw = urllib.request.urlopen(data.url, timeout=60).read()
        out = Path(args.out)
        out.parent.mkdir(parents=True, exist_ok=True)
        out.write_bytes(raw)
        with Image.open(out) as image:
            size = image.size
        print(json.dumps({"ok": True, "model": model, "mode": args.mode, "out": str(out), "size": size,
                          "colors": colors}, indent=2))
        return 0

    print(json.dumps({"ok": False, "reason": "no usable image model", "detail": last_error}))
    return 4


if __name__ == "__main__":
    sys.exit(main())
