# Site-wide images

- `og-default.png` (1200×630): the Open Graph / Twitter card image used by
  every page via the layout's `og:image` meta unless a view yields a specific
  `og_image`. Keep it at exactly 1200×630; brand palette, Latin-only artwork
  (Persian glyphs render inconsistantly across crawlers' image pipelines).

The 2026-09 landing hero is an editorial card rendered by
`resources/views/components/landing-hero.blade.php` and needs no image
assets; the former `heart-placeholder.svg` poster was removed with it.
