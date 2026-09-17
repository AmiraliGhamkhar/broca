# Site-wide images

- `og-default.png` (1200×630): the Open Graph / Twitter card image used by
  every page via the layout's `og:image` meta unless a view yields a specific
  `og_image`. Keep it at exactly 1200×630; brand palette, Latin-only artwork
  (Persian glyphs render inconsistently across crawlers' image pipelines).

The landing hero uses the selected editorial artwork at `hero/hero.webp`,
with `hero/hero.jpg` as the intentional browser fallback. Both files are
1200×1600 and are rendered by
`resources/views/components/landing-hero.blade.php`; preserve both canonical
paths, formats, and the component's accessible alt text.
