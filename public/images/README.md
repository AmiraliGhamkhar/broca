# Landing hero assets (placeholder)

`heart-placeholder.svg` is a temporary flat-cream poster image used by the
landing page hero. It exists so the page renders even before the client
supplies the real 3D-heart asset.

**To replace with the real asset** (per SPEC §15 placeholder register):
1. Place the MP4/WebM video files in `public/videos/`
2. Place the high-resolution poster image in `public/images/` (e.g. `heart-hero.jpg`)
3. Update `resources/views/components/landing-hero.blade.php` to reference the new asset names
4. Remove this README and the placeholder file
