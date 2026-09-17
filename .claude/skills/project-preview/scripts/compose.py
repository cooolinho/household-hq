#!/usr/bin/env python3
"""Deterministic composition of the README preview image (Pillow).

    compose.py --spec /work/compose.json [--no-ai] [--output PATH] [--webp]

Spec (JSON):
    title        project name
    tagline      one factual sentence
    eyebrow      optional short line above the title, e.g. the tech stack
    features     3-5 short, factual feature lines
    screenshots  real screenshots, first = hero, up to 3 more as secondary images
    background   optional AI stage (generate_background.py); missing or --no-ai -> deterministic background
    accent       optional hex colour; derived from the hero screenshot otherwise
    font         optional font file (the project's own font is preferred); fc-match fallback otherwise
    output       target file, e.g. /project/docs/project-preview.png
    size         [width, height], default [1920, 1080]

Screenshots are only scaled proportionally (LANCZOS) and framed (rounded corners, 1px border, shadow).
Their pixels are never retouched, recoloured, cropped or partially regenerated.
"""
from __future__ import annotations

import argparse
import colorsys
import json
import subprocess
import sys
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageFont, ImageStat

FALLBACK_FAMILIES = ["Inter", "Ubuntu", "Noto Sans", "DejaVu Sans", "Liberation Sans", "FreeSans"]
SS = 2  # supersampling for the typography layer


# --- colours ----------------------------------------------------------------------------------------------------

def hex_to_rgb(value: str) -> tuple[int, int, int]:
    value = value.lstrip("#")
    return tuple(int(value[i:i + 2], 16) for i in (0, 2, 4))


def with_lightness(rgb: tuple[int, int, int], lightness: float) -> tuple[int, int, int]:
    h, _, s = colorsys.rgb_to_hls(*(c / 255 for c in rgb))
    return tuple(round(c * 255) for c in colorsys.hls_to_rgb(h, lightness, s))


def accent_colors(images: list[Image.Image], limit: int = 3) -> list[tuple[int, int, int]]:
    """Characteristic UI colours (buttons, badges, charts): the most populated hue bins among saturated pixels.

    Works on the real pixel distribution, so small but distinctive accents (e.g. a primary button colour on a mostly
    white UI) are found, which frequency-based quantisation would merge away.
    """
    bins: dict[int, list] = {}
    for image in images:
        small = image.convert("RGB")
        small.thumbnail((480, 480))
        for r, g, b in getattr(small, "get_flattened_data", small.getdata)():
            h, s, v = colorsys.rgb_to_hsv(r / 255, g / 255, b / 255)
            if s < 0.45 or v < 0.45:
                continue
            entry = bins.setdefault(int(h * 24) % 24, [0, 0, 0, 0])
            entry[0] += 1
            entry[1] += r
            entry[2] += g
            entry[3] += b
    ranked = sorted(bins.values(), key=lambda entry: -entry[0])
    return [(entry[1] // entry[0], entry[2] // entry[0], entry[3] // entry[0]) for entry in ranked[:limit] if entry[0] >= 40]


def derive_accent(image: Image.Image) -> tuple[int, int, int]:
    colors = accent_colors([image], limit=1)
    return colors[0] if colors else (99, 102, 241)


def relative_luminance(rgb: tuple[int, int, int]) -> float:
    r, g, b = rgb
    return (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255


def luminance(image: Image.Image) -> float:
    r, g, b = ImageStat.Stat(image.convert("RGB")).mean
    return (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255


# --- fonts ------------------------------------------------------------------------------------------------------

def fc_match(pattern: str, family: str) -> str | None:
    try:
        out = subprocess.run(["fc-match", "-f", "%{family}\t%{file}", pattern], capture_output=True, text=True,
                             timeout=10).stdout
    except (OSError, subprocess.SubprocessError):
        return None
    found_family, _, file = out.partition("\t")
    return file if family.lower() in found_family.lower() and file else None


class Fonts:
    def __init__(self, path: str | None):
        self.regular = self.bold = None
        if path and Path(path).is_file():
            self.regular = self.bold = path
            self.source = path
        else:
            for family in FALLBACK_FAMILIES:
                regular = fc_match(f"{family}:weight=80", family)
                if regular:
                    self.regular = regular
                    self.bold = fc_match(f"{family}:weight=200", family) or regular
                    self.source = family
                    break
        if not self.regular:
            raise SystemExit("no usable font found (pass spec.font)")
        self.cache: dict[tuple[int, int], ImageFont.FreeTypeFont] = {}

    def get(self, size: int, weight: int = 400) -> ImageFont.FreeTypeFont:
        key = (size, weight)
        if key not in self.cache:
            font = ImageFont.truetype(self.regular, size)
            try:
                axes = font.get_variation_axes()
                values = []
                for axis in axes:
                    name = axis["name"].decode() if isinstance(axis["name"], bytes) else str(axis["name"])
                    if name.lower() in ("weight", "wght"):
                        values.append(max(axis["minimum"], min(axis["maximum"], weight)))
                    else:
                        values.append(axis["default"])
                font.set_variation_by_axes(values)
            except OSError:  # static font: pick the bold file for heavy weights
                if weight >= 600 and self.bold != self.regular:
                    font = ImageFont.truetype(self.bold, size)
            self.cache[key] = font
        return self.cache[key]


def wrap(text: str, font: ImageFont.FreeTypeFont, width: float) -> list[str]:
    lines, current = [], ""
    for word in text.split():
        candidate = f"{current} {word}".strip()
        if font.getlength(candidate) <= width or not current:
            current = candidate
        else:
            lines.append(current)
            current = word
    if current:
        lines.append(current)
    return lines


# --- background -------------------------------------------------------------------------------------------------

def cover(image: Image.Image, size: tuple[int, int]) -> Image.Image:
    w, h = size
    scale = max(w / image.width, h / image.height)
    resized = image.convert("RGB").resize((round(image.width * scale), round(image.height * scale)), Image.LANCZOS)
    left, top = (resized.width - w) // 2, (resized.height - h) // 2
    return resized.crop((left, top, left + w, top + h))


def deterministic_background(size: tuple[int, int], accent: tuple[int, int, int]) -> Image.Image:
    w, h = size
    gradient = Image.new("RGB", (1, 2))
    gradient.putpixel((0, 0), (10, 14, 28))
    gradient.putpixel((0, 1), (17, 22, 38))
    base = gradient.resize((w, h), Image.BICUBIC).convert("RGBA")

    sw, sh = w // 4, h // 4
    glow = Image.new("RGBA", (sw, sh), (0, 0, 0, 0))
    draw = ImageDraw.Draw(glow)
    second = with_lightness(tuple((a + b) // 2 for a, b in zip(accent, (59, 130, 246))), 0.45)
    for (cx, cy, r, color, alpha) in (
        (0.70, 0.40, 0.30, accent, 120),
        (0.95, 0.95, 0.22, second, 90),
        (0.05, 1.05, 0.25, second, 60),
    ):
        box = [(cx - r) * sw, cy * sh - r * sw, (cx + r) * sw, cy * sh + r * sw]
        draw.ellipse(box, fill=(*color, alpha))
    glow = glow.filter(ImageFilter.GaussianBlur(sw * 0.09)).resize((w, h), Image.BICUBIC)
    base = Image.alpha_composite(base, glow)

    grid = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    gd = ImageDraw.Draw(grid)
    step, dot = max(24, w // 60), max(1, w // 1600)
    for x in range(step // 2, w, step):
        for y in range(step // 2, h, step):
            gd.ellipse([x - dot, y - dot, x + dot, y + dot], fill=(255, 255, 255, 16))
    return Image.alpha_composite(base, grid)


def scrim(size: tuple[int, int], dark: bool, reach: float = 0.58) -> Image.Image:
    """Horizontal fade behind the typography so text stays readable on any AI stage."""
    w, h = size
    color = (8, 11, 22) if dark else (255, 255, 255)
    strip = Image.new("RGBA", (256, 1))
    for x in range(256):
        t = x / 255
        alpha = 0.78 * max(0.0, 1 - t / reach) ** 1.6 if t < reach else 0.0
        strip.putpixel((x, 0), (*color, round(alpha * 255)))
    return strip.resize((w, h), Image.BILINEAR)


# --- screenshots ------------------------------------------------------------------------------------------------

def framed(shot: Image.Image, width: int, radius: int, border: tuple[int, int, int, int], max_height: int | None = None):
    scale = width / shot.width
    if max_height and shot.height * scale > max_height:
        scale = max_height / shot.height
    w, h = round(shot.width * scale), round(shot.height * scale)
    image = shot.convert("RGB").resize((w, h), Image.LANCZOS)

    b = 1
    mask_big = Image.new("L", (w * 4, h * 4), 0)
    ImageDraw.Draw(mask_big).rounded_rectangle([0, 0, w * 4 - 1, h * 4 - 1], radius * 4, fill=255)
    mask = mask_big.resize((w, h), Image.LANCZOS)

    card = Image.new("RGBA", (w + 2 * b, h + 2 * b), (0, 0, 0, 0))
    ring_big = Image.new("L", ((w + 2 * b) * 4, (h + 2 * b) * 4), 0)
    ImageDraw.Draw(ring_big).rounded_rectangle([0, 0, ring_big.width - 1, ring_big.height - 1], (radius + b) * 4, fill=255)
    ring = ring_big.resize(card.size, Image.LANCZOS)
    card.paste(Image.new("RGBA", card.size, border), (0, 0), ring)
    card.paste(image, (b, b), mask)
    return card


def composite_at(canvas: Image.Image, image: Image.Image, xy: tuple[int, int]) -> None:
    """In-place alpha compositing that tolerates positions partly outside the canvas."""
    x, y = xy
    left, top = max(0, -x), max(0, -y)
    right, bottom = min(image.width, canvas.width - x), min(image.height, canvas.height - y)
    if right > left and bottom > top:
        canvas.alpha_composite(image.crop((left, top, right, bottom)), dest=(x + left, y + top))


def place(canvas: Image.Image, card: Image.Image, xy: tuple[int, int], radius: int, scale: float) -> None:
    blur, offset = round(34 * scale), round(22 * scale)
    pad = blur * 3
    shadow = Image.new("RGBA", (card.width + 2 * pad, card.height + 2 * pad), (0, 0, 0, 0))
    ImageDraw.Draw(shadow).rounded_rectangle([pad, pad, pad + card.width, pad + card.height], radius,
                                             fill=(0, 0, 0, 120))
    composite_at(canvas, shadow.filter(ImageFilter.GaussianBlur(blur)), (xy[0] - pad, xy[1] - pad + offset))
    composite_at(canvas, card, xy)


# --- typography -------------------------------------------------------------------------------------------------

def draw_text_block(size, spec, fonts: Fonts, accent, dark: bool, scale: float, column: tuple[int, int]):
    """Renders eyebrow, title, tagline and features on a supersampled transparent layer."""
    w, h = size
    s = scale * SS
    layer = Image.new("RGBA", (w * SS, h * SS), (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    x0, col_w = column[0] * SS, column[1] * SS

    text_main = (241, 245, 249) if dark else (15, 23, 42)
    text_muted = (176, 188, 206) if dark else (71, 85, 105)
    accent_text = with_lightness(accent, 0.68) if dark else with_lightness(accent, 0.38)

    blocks = []  # (kind, payload, height)
    if spec.get("eyebrow"):
        font = fonts.get(round(21 * s), 600)
        pill_h = round(44 * s)
        blocks.append(("eyebrow", (spec["eyebrow"], font, pill_h), pill_h + round(30 * s)))

    title_size = 86
    while True:
        title_font = fonts.get(round(title_size * s), 800)
        title_lines = wrap(spec["title"], title_font, col_w)
        if len(title_lines) <= 2 or title_size <= 52:
            break
        title_size -= 4
    title_lh = round(title_size * s * 1.08)
    blocks.append(("title", (title_lines, title_font, title_lh), title_lh * len(title_lines) + round(26 * s)))

    tag_font = fonts.get(round(29 * s), 400)
    tag_lines = wrap(spec.get("tagline", ""), tag_font, col_w)[:4]
    tag_lh = round(29 * s * 1.42)
    if tag_lines:
        blocks.append(("tagline", (tag_lines, tag_font, tag_lh), tag_lh * len(tag_lines) + round(44 * s)))

    feat_font = fonts.get(round(25 * s), 500)
    feat_lh = round(25 * s * 1.38)
    badge = round(30 * s)
    feat_indent = badge + round(18 * s)
    features = []
    for feature in spec.get("features", [])[:5]:
        lines = wrap(feature, feat_font, col_w - feat_indent)[:2]
        features.append(lines)
    if features:
        height = sum(feat_lh * len(lines) for lines in features) + round(20 * s) * (len(features) - 1)
        blocks.append(("features", (features, feat_font, feat_lh), height))

    total = sum(block[2] for block in blocks)
    y = max(round(80 * s), (h * SS - total) // 2)

    for kind, payload, height in blocks:
        if kind == "eyebrow":
            text, font, pill_h = payload
            text_w = font.getlength(text)
            pad_x = round(20 * s)
            draw.rounded_rectangle([x0, y, x0 + text_w + 2 * pad_x, y + pill_h], pill_h // 2,
                                   fill=(*accent, 38), outline=(*accent, 140), width=max(1, round(1.5 * s)))
            draw.text((x0 + pad_x, y + pill_h / 2), text, font=font, fill=accent_text, anchor="lm")
        elif kind == "title":
            lines, font, lh = payload
            for i, line in enumerate(lines):
                draw.text((x0, y + i * lh), line, font=font, fill=text_main, anchor="la")
        elif kind == "tagline":
            lines, font, lh = payload
            for i, line in enumerate(lines):
                draw.text((x0, y + i * lh), line, font=font, fill=text_muted, anchor="la")
        elif kind == "features":
            groups, font, lh = payload
            yy = y
            for lines in groups:
                cx, cy, r = x0 + badge / 2, yy + lh / 2, badge / 2
                draw.ellipse([cx - r, cy - r, cx + r, cy + r], fill=(*accent, 255))
                check = [(cx - r * 0.42, cy + r * 0.02), (cx - r * 0.12, cy + r * 0.32), (cx + r * 0.44, cy - r * 0.30)]
                draw.line(check, fill=(15, 23, 42) if relative_luminance(accent) > 0.5 else (255, 255, 255),
                          width=max(2, round(3.2 * s)), joint="curve")
                for i, line in enumerate(lines):
                    draw.text((x0 + feat_indent, yy + i * lh + lh / 2), line, font=font, fill=text_main, anchor="lm")
                yy += lh * len(lines) + round(20 * s)
        y += height

    return layer.resize((w, h), Image.LANCZOS)


# --- main -------------------------------------------------------------------------------------------------------

def compose(spec: dict, no_ai: bool) -> tuple[Image.Image, dict]:
    size = tuple(spec.get("size") or (1920, 1080))
    w, h = size
    scale = w / 1920
    shots = [Image.open(path) for path in spec["screenshots"]]
    if not shots:
        raise SystemExit("spec.screenshots is empty")
    accent = hex_to_rgb(spec["accent"]) if spec.get("accent") else derive_accent(shots[0])
    fonts = Fonts(spec.get("font"))

    background_path = None if no_ai else spec.get("background")
    if background_path and Path(background_path).is_file():
        canvas = cover(Image.open(background_path), size).convert("RGBA")
        dark = luminance(canvas.crop((0, 0, round(w * 0.45), h))) < 0.55
        canvas = Image.alpha_composite(canvas, scrim(size, dark))
        mode = "ai-background"
    else:
        canvas = deterministic_background(size, accent)
        dark = True
        mode = "deterministic"

    border = (255, 255, 255, 40) if dark else (15, 23, 42, 38)
    radius = round(14 * scale)
    pad = round(96 * scale)
    column = (pad, round(610 * scale))

    hero, secondary = shots[0], shots[1:4]
    right = w - round(72 * scale)
    if secondary:
        hero_card = framed(hero, round(1060 * scale), radius, border, max_height=round(700 * scale))
        hero_xy = (right - hero_card.width, round(118 * scale))
    else:
        hero_card = framed(hero, round(1110 * scale), radius, border, max_height=round(860 * scale))
        hero_xy = (right - hero_card.width, (h - hero_card.height) // 2)
    place(canvas, hero_card, hero_xy, radius, scale)

    if secondary:
        count = len(secondary)
        # Secondary images overlap each other slightly: a gap would expose cut-off fragments of the hero behind them.
        width = {1: 580, 2: 540, 3: 400}[count]
        gap = {1: 0, 2: -40, 3: -40}[count]
        x = hero_xy[0] - round(60 * scale)
        y = hero_xy[1] + hero_card.height - round(150 * scale)
        for index, shot in enumerate(secondary):
            card = framed(shot, round(width * scale), radius, border, max_height=round(330 * scale))
            yy = min(y + round(28 * scale) * index, h - card.height - round(40 * scale))
            place(canvas, card, (x, yy), radius, scale)
            x += card.width + round(gap * scale)

    canvas = Image.alpha_composite(canvas, draw_text_block(size, spec, fonts, accent, dark, scale, column))
    info = {"mode": mode, "size": list(size), "accent": "#%02x%02x%02x" % accent, "font": fonts.source,
            "hero": spec["screenshots"][0], "secondary": spec["screenshots"][1:4]}
    return canvas.convert("RGB"), info


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--spec", required=True)
    parser.add_argument("--no-ai", action="store_true", help="ignore spec.background, use the deterministic stage")
    parser.add_argument("--output", help="override spec.output")
    parser.add_argument("--webp", action="store_true", help="additionally write a .webp next to the output")
    args = parser.parse_args()

    spec = json.loads(Path(args.spec).read_text(encoding="utf-8"))
    output = Path(args.output or spec["output"])
    image, info = compose(spec, args.no_ai)
    output.parent.mkdir(parents=True, exist_ok=True)
    if output.suffix.lower() == ".webp":
        image.save(output, "WEBP", quality=90, method=6)
    else:
        image.save(output, "PNG", optimize=True)
    info.update({"output": str(output), "bytes": output.stat().st_size})
    if args.webp and output.suffix.lower() != ".webp":
        webp = output.with_suffix(".webp")
        image.save(webp, "WEBP", quality=90, method=6)
        info["webp"] = str(webp)
    print(json.dumps(info, indent=2, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    sys.exit(main())
