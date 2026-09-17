# Background prompt (OpenAI Image API)

`scripts/generate_background.py` reads the two `text` blocks below and fills in these placeholders:
- `{base}`: the most frequent screenshot colours
- `{accent}`: the most characteristic saturated UI colours
- `{contrast}`: a light/dark hint derived from the screenshots' brightness
- `{mood}`: a short mood description

Do not use other curly braces in the blocks.

## Rules for the AI
The model only paints the **stage**. Real screenshots and all typography are placed pixel-exact by `compose.py`.

The following are forbidden in the backdrop:
- any text, letters, numbers, glyphs or pseudo-writing
- user interfaces, windows, dashboards, charts, tables, buttons, icons
- screens, laptops, phones or monitors with content
- logos, brand marks, emblems, watermarks, mascots
- people, hands, photos of real places

Allowed: abstract surfaces, gradients, soft light, depth, subtle geometric shapes, fine grain, bokeh.

## Fidelity gate (performed by Claude after generation)
Look at `background.png` with the Read tool. Reject it if **any** of these is visible:
- text-like or number-like shapes, including blurred or "almost" letters
- UI fragments, windows, cards with content, charts, icons
- logos or brand-like marks
- strong detail or bright spots in the left 40% (the typography area)

On a rejection: try `--mode text-only` once. If that is also rejected, use the deterministic background
(`compose.py` without `background`).

<!-- prompt:reference -->
```text
Create a wide landscape presentation backdrop for a software product showcase.
The attached images are screenshots of the real application. Use them ONLY as a reference for the colour palette
and the overall mood. Do NOT reproduce, redraw, crop or depict the screenshots or any part of them.

Style: {mood}. Abstract, premium, minimal. Smooth gradients, soft volumetric light, subtle depth,
very fine grain. {contrast}
Accent colours of the product, to be used sparingly for soft glows and light: {accent}.
Neutral colours of the product for orientation: {base}.

Composition: the left 40 percent must be calm, even and slightly darker with almost no detail, reserved for
typography that will be added later. The right 60 percent may carry a soft glow and gentle abstract light shapes
where screenshots will be placed later. Keep all edges clean.

Strictly forbidden: any text, letters, numbers or pseudo-writing; any user interface, windows, cards, charts,
tables, buttons or icons; any device, screen, laptop or phone; any logo, emblem, watermark or brand mark;
people or hands. The result must be a pure abstract background.
```

<!-- prompt:text-only -->
```text
A wide landscape abstract background for a software product presentation.
Style: {mood}. Premium and minimal: smooth gradients, soft volumetric light, subtle depth, very fine grain.
{contrast}
Accent colours, used sparingly for soft glows: {accent}. Neutral product colours for orientation: {base}.

Composition: the left 40 percent is calm, even and slightly darker with almost no detail (space for typography).
The right 60 percent has a soft glow with gentle abstract light shapes. Clean edges.

Strictly forbidden: any text, letters, numbers or pseudo-writing; user interfaces, windows, cards, charts, tables,
buttons or icons; devices, screens, laptops or phones; logos, emblems, watermarks or brand marks; people or hands.
Pure abstract background only.
```
