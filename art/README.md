# Cover images

The README hero and social/OG card are **generated**, not hand-drawn.

| File | Used by |
|------|---------|
| `cover-light.png` | README `<picture>` (light colour scheme) |
| `cover-dark.png` | README `<picture>` (dark colour scheme) |
| `../website/public/cover.png` | Docs site `og:image` / `twitter:image` (light variant) |

All three are produced from a single source: [`website/scripts/make-cover.mjs`](../website/scripts/make-cover.mjs).

## Regenerate

```bash
cd website
npm install          # first time only (needs the `sharp` dependency)
npm run cover        # or: node scripts/make-cover.mjs
```

This rewrites `art/cover-light.png`, `art/cover-dark.png` and `website/public/cover.png`.

## Edit the design

Open `website/scripts/make-cover.mjs`:

- **Colours** — the `LIGHT` and `DARK` palette objects at the top.
- **Text** — the title, eyebrow, tagline and pill labels in `buildSvg()`.
- **Terminal sample** — the `tree` and `bodyLines` arrays.

The SVG is rendered at 2× (2400×1260) via `sharp` for a crisp result. After editing, re-run `npm run cover` and commit the updated PNGs.

> The OG image is never shown on the site itself — it only appears as the preview card when the docs URL is shared (Twitter, Slack, GitHub, LinkedIn).
