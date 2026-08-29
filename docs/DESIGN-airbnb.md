# Broca — Airbnb-Inspired Design Specification (implemented)

> Source: the Airbnb UI design spec pasted by the client (2026-08-29), adapted
> for Broca by the confirmed decisions: pure-white canvas, ink `#222222`,
> **single Rausch accent** `#ff385c` (client-chosen over Broca coral/teal),
> self-hosted Lalezar + Vazirmatn, all 58 views restyled including the admin
> panel, existing video/poster hero restyled (not replaced).

## 1. Palette

| Token | Value | Usage |
|---|---|---|
| `canvas` | `#ffffff` | page background — the canvas is pure white |
| `surface-soft` | `#f7f7f7` | cards/panels needing a subtle tint, inputs on hover |
| `surface-strong` | `#f2f2f2` | pressed input surfaces |
| `ink` | `#222222` | primary text, dark buttons |
| `body` | `#3f3f3f` | secondary text |
| `muted` | `#6a6a6a` | meta text, captions |
| `muted-soft` | `#929292` | disabled text |
| `hairline` | `#dddddd` | borders, dividers |
| `hairline-soft` | `#ebebeb` | lighter borders, table rows |
| `border-strong` | `#c1c1c1` | strong input borders |
| `rausch` | `#ff385c` | **the single accent** — CTAs, active states, links, price highlights |
| `rausch-active` | `#e00b41` | hover/pressed accent |
| `rausch-disabled` | `#ffd1da` | disabled accent fills |
| `rausch-tint` | `#fff1f3` | accent-tinted backgrounds |
| `error-text` | `#c13515` | error messages |
| `legal-link` | `#428bff` | legal footer links |

## 2. Typography

- **Display / headings:** Lalezar (400 only), self-hosted at
  `public/fonts/lalezar/` (OFL). Used via `font-display` for h1s and hero
  numerals — Persian headings keep the friendly rounded Lalezar voice.
- **Body / UI:** Vazirmatn (400/500/700/900), self-hosted at
  `public/fonts/vazirmatn/` (OFL).
- No CDNs — both families are local woff2 with `font-display: swap`.

## 3. Elevation

One dominant shadow tier (`shadow-float`) for floating cards/buttons:

```
0 0 0 1px rgb(0 0 0 / 0.02),
0 2px 6px  0 rgb(0 0 0 / 0.04),
0 4px 8px  0 rgb(0 0 0 / 0.10)
```

A lighter `shadow-raised` exists for small raised controls
(`0 1px 2px rgb(0 0 0/.08), 0 4px 12px rgb(0 0 0/.08)`).

## 4. Radii

- Cards/panels: `rounded-2xl` (16px) for panels, `rounded-3xl` (24px) for
  hero/feature cards.
- Inputs: `rounded-xl` (12px).
- Buttons, badges, chips, pagination: `rounded-full` (pill).

## 5. Components

- **Buttons:** pill (`rounded-full`), `text-xs`–`text-sm` bold; primary =
  `bg-rausch text-white` with `hover:bg-rausch-active`; secondary = `bg-ink
  text-white` with `hover:bg-rausch`; tertiary = bordered
  `border border-ink/20 text-muted hover:bg-surface-soft`.
- **Cards:** white (`bg-white`) or `surface-panel` (white with hairline
  border), `rounded-2xl/3xl`, `shadow-float` on hover-elevated elements.
- **Inputs:** white, `rounded-xl`, `border border-ink/20`, bold small text;
  focus outline follows the spec's 2px ink ring.
- **Badges/pills:** `rounded-full`, tinted backgrounds (`bg-teal/10 text-teal`
  for success, `bg-rausch-tint text-rausch` for accent, `bg-surface-soft
  text-ink` for neutral).
- **Tables:** `divide-y divide-hairline-soft`, header `bg-surface-soft
  text-muted`, hover rows tinted.
- **Hero:** existing video/poster hero restyled onto the white canvas with
  Lalezar display headline, rausch CTA, ink secondary button — the 3D-heart
  video/poster is kept as the client supplied it.
- **Nav:** white sticky bar, hairline bottom border, ink links, rausch active
  underline; admin nav uses pill tabs.

## 6. Breakpoints

Standard Tailwind: `sm` 640 · `md` 768 · `lg` 1024 · `xl` 1280 · `2xl` 1536.
Content columns: `max-w-7xl` pages, `max-w-4xl` forms, `max-w-2xl` prose.

## 7. Conventions

- RTL-first (`dir="rtl"` on the document), Persian copy unchanged from the
  original site.
- `bg-white` (not `bg-canvas`) on cards; page canvas inherits white from body.
- Legacy class aliases (cream/coral/sun/slate/sand/gray-*/red-*) remain
  defined in `app.css` for compile safety but are no longer used in views
  (verified by grep, zero leftovers).
- Build: `npm run build` — CSS `public/build/assets/app-*.css` (~48 kB).
