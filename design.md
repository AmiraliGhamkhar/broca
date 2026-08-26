# Broca Design System

## 1. Product character

Broca is a Persian, RTL-first medical-education platform. The visual language should feel:

- **Calm:** a warm cream canvas and generous whitespace reduce cognitive load.
- **Trustworthy:** charcoal typography, structured information, clear status feedback, and restrained color use support medical learning and payments.
- **Human:** rounded cards, conversational Persian copy, and soft supporting colors make the product approachable.
- **Focused:** content and learning actions should be more visually prominent than navigation or decoration.

The interface is server-rendered with Blade and enhanced with Alpine.js. Design decisions should work without JavaScript wherever possible.

## 2. Design principles

1. **RTL by default** — Persian is the primary language; every layout, icon, form, and interaction must be checked in RTL.
2. **Content before decoration** — visual treatment should clarify course structure, learning progress, and next actions.
3. **One clear primary action** — each page should have one dominant action, such as enrolling, starting a video, submitting an answer, or downloading a note.
4. **Progressive disclosure** — keep secondary information in supporting text, metadata rows, or expandable sections.
5. **Trust through transparency** — show prices, payment states, expiry dates, validation errors, and learning status explicitly.
6. **Accessible by construction** — use semantic HTML first, then add ARIA only where the native element is insufficient.
7. **Fail visibly and recoverably** — every asynchronous action needs loading, success, failure, and retry states.

## 3. Brand tokens

The current palette is implemented in `resources/css/app.css` using Tailwind v4 theme tokens. These values are the current working palette and may be refined when the final brand file is supplied.

| Token | Hex | Role |
|---|---:|---|
| `cream` / `broca-cream` | `#FDFBF7` | Main page background and light surfaces |
| `ink` / `broca-ink` | `#1C1B19` | Primary text, dark surfaces, primary buttons |
| `broca-slate` | `#4B5563` | Secondary text and muted metadata |
| `coral` / `broca-accent` | `#C2410C` | Brand accent, links, warnings, attention states |
| `broca-sand` | `#E7DED2` | Borders, dividers, subtle navigation surfaces |
| `sun` | `#F5E6C4` | Learning highlights, cards, positive emphasis |
| `teal` | `#0F766E` | Success, completion, confirmation |
| `plum` | `#6B2D5C` | Secondary emphasis and media/playback metadata |

### Color usage

- Use `ink` for the highest-priority text and primary actions.
- Use `coral` sparingly for links, section eyebrows, warnings, and destructive/error emphasis.
- Use `teal` for confirmed success only; do not use it as a general decorative color.
- Use `sun` for learning content and active study surfaces.
- Use `plum` as a supporting accent, not for long text blocks.
- Do not communicate state by color alone. Pair color with text, an icon, or a status label.
- Verify contrast for the final palette before production. Especially check muted text such as `text-ink/65` and `text-ink/50`.

## 4. Typography

### Typeface

Use the self-hosted **Vazirmatn** family from `public/fonts/vazirmatn`:

- 400 — regular body copy
- 500 — medium supporting text
- 700 — bold labels and controls
- 900 — headings, metrics, and major actions

Never introduce a third-party font CDN for the core interface.

### Type hierarchy

| Element | Current treatment | Guidance |
|---|---|---|
| Display heading | `text-5xl` to `text-7xl`, `font-black` | Use for landing, course, dashboard, and learning page titles |
| Page heading | `text-4xl` to `text-5xl`, `font-black` | Keep one `h1` per page |
| Section heading | `text-2xl` to `text-3xl`, `font-black` | Use to group related content |
| Body | `text-base`, `leading-7` or `leading-8` | Prefer comfortable line length and spacing |
| Metadata | `text-sm`, `font-bold`, muted color | Subject, duration, status, and supporting information |
| Eyebrow | `text-sm`, `font-black`, `text-coral` | Short context label above a heading |
| Metrics | `text-3xl` to `text-7xl`, `font-black` | Use for dashboard counts and quiz scores |

Persian body copy should use relaxed line height. Avoid all-caps styling, excessive letter spacing, and dense blocks of untranslated technical language.

## 5. Layout and spacing

- Root document: `lang="fa" dir="rtl"`.
- Main page background: warm cream.
- Header and footer use full-width borders with centered content.
- Standard content widths:
  - `max-w-6xl` for navigation, dashboards, and general pages.
  - `max-w-5xl` for learning content and video pages.
  - `max-w-4xl` for quizzes and focused forms.
  - `max-w-3xl` for legal and long-form reading.
  - `max-w-xl` for authentication and compact admin forms.
- Use responsive horizontal padding equivalent to `px-5 sm:px-8 lg:px-12`.
- Use large vertical rhythm on public and learning pages, generally `py-20` to `py-28`.
- Prefer Tailwind logical properties (`ms`, `me`, `ps`, `pe`) when adding directional spacing.
- Avoid fixed heights for text content. Reserve dimensions only for media, progress indicators, and known visual regions.

## 6. Surfaces and shape language

The product uses a soft but structured shape language:

- Large feature surfaces: `rounded-[2rem]`.
- Standard cards and forms: `rounded-2xl`.
- Controls and pills: `rounded-full`.
- Borders: thin `ink`/`sand` borders with low opacity for secondary separation.
- Primary learning and payment cards may use a stronger border or dark surface to establish hierarchy.
- Avoid adding shadows by default; use borders, spacing, and background contrast first.
- Dark media surfaces use `ink` with cream text.

## 7. Navigation

The global header contains:

- Broca wordmark linking to the home page.
- Catalog link.
- Plans link.
- Guest actions: login and registration.
- Authenticated actions: dashboard, admin panel for admins, and logout.

Navigation should remain compact and wrap safely on narrow screens. Every interactive item must have a visible keyboard focus state. Logout remains a POST form with CSRF protection, not a normal link.

## 8. Component patterns

### Buttons and links

Primary button:

```html
<button class="rounded-full bg-ink px-7 py-4 font-black text-cream
               focus:outline-none focus-visible:ring-2 focus-visible:ring-coral">
    Action
</button>
```

Rules:

- Use a button for an action and an anchor for navigation.
- Primary action: dark ink background with cream text.
- Secondary action: cream/transparent background with ink border.
- Highlight action: sun background with ink text.
- Destructive action: coral treatment with explicit confirmation where appropriate.
- Preserve a visible `focus-visible` ring.
- Disabled states must communicate both visually and semantically.
- Use touch targets of at least 44px where practical; never rely on tiny text links for primary actions.

### Cards

Cards should expose a clear hierarchy:

1. Context or subject.
2. Title.
3. Description or metadata.
4. Primary action.

Do not mix unrelated administrative actions into the same content card. For lists, keep card heights flexible so Persian text does not clip.

### Forms

- Every field has a visible, programmatically associated label.
- Use `required`, suitable input types, and appropriate `autocomplete` values.
- Show validation errors next to the relevant field using `role="alert"`.
- Preserve submitted values after validation failures.
- Use `dir="ltr"` for phone numbers, prices, codes, dates, and other machine-oriented values.
- Keep consent text adjacent to its checkbox and explain what acceptance means.

### Status and feedback

Use:

- `role="status"` for successful non-error updates.
- `role="alert"` for errors requiring attention.
- `aria-live="polite"` for async progress that should not interrupt the user.
- Explicit loading labels such as `در حال آماده‌سازی…`.

Every payment, review, playback, enrollment, and form submission must have a recoverable failure state.

### Learning content

Learning pages should prioritize the content itself:

- Video pages: title, course context, playback surface, progress, completion threshold, and playback status.
- Notes: title, type/size metadata, and one clear download action.
- Flashcards: front first, answer reveal second, review quality actions third.
- Quizzes: one question per fieldset, options as labels/radio controls, clear submission action, and a private result page.

Use semantic elements such as `video`, `fieldset`, `legend`, `label`, `progressbar` semantics, and `details` rather than recreating native behavior with generic `div` elements.

## 9. Page-level direction

### Landing page

- Warm cream background.
- Large centered hero with Broca name, Persian tagline, and two clear entry actions.
- Hero media is optional until the final heart asset is supplied.
- Use the poster/static composition as a complete fallback; do not allow missing video files to degrade the experience.
- Keep the hero visually calm and avoid competing calls to action.

### Catalog and course pages

- Use subject context and strong course titles.
- Show published content only.
- Use consistent metadata and action placement.
- Preserve filters and search state across pagination.

### Plans and payments

- Show each plan’s duration, description, price in تومان, and exact Rial value where useful.
- Never hardcode prices in templates.
- When checkout is disabled, show an explicit availability message instead of a dead purchase button.
- Payment success and failure pages must clearly state the result, invoice number, amount, and next action.

### Learner dashboard

- Put due flashcards, completed videos, subscription state, and recent attempts in a scannable metric grid.
- Follow metrics with the learner’s active course list.
- Use a clear next-step action rather than presenting only historical data.

### Admin

- Use a denser layout than learner pages but preserve the same palette and focus states.
- Make security state visible: mandatory TOTP enrollment/challenge and session status.
- Separate content management, plans, publication, and audit history.
- Never expose secrets, recovery codes, or sensitive payment payloads in rendered tables.

### Legal pages

- Use a readable narrow column and generous line height.
- No placeholder legal copy may remain before production checkout is enabled.
- Display the current legal/consent version where the product requires it.

## 10. Motion and interaction

- Motion should clarify state, not decorate every transition.
- Respect `prefers-reduced-motion: reduce`.
- Do not autoplay a large visual experience for users who request reduced motion.
- Preserve user control over video playback.
- For async actions, disable duplicate submission while in flight and restore retry capability after failure.
- Alpine islands must remain usable if JavaScript fails or is delayed; use `x-cloak` only to hide content that has a safe non-JavaScript fallback.

## 11. Accessibility baseline

Target WCAG 2.2 AA:

- 1.1.1 — informative images have meaningful alt text; decorative images are hidden from assistive technology.
- 1.3.1 — structure is expressed semantically and form relationships are programmatic.
- 1.4.3 / 1.4.11 — text and UI component contrast meet AA requirements.
- 2.1.1 — all interactions work with a keyboard.
- 2.4.3 / 2.4.7 — focus order is logical and focus is visible.
- 2.5.8 — interactive targets meet minimum target-size expectations or have adequate spacing.
- 3.3.1 / 3.3.2 — errors and instructions are clear, visible, and associated with fields.
- 4.1.2 — custom controls have correct names, roles, and values.

Test every new screen with keyboard-only navigation, zoom, a narrow viewport, and a screen reader or automated accessibility checker.

## 12. Performance baseline

The current application is a small Blade/Alpine site, so keep the client bundle simple.

- **LCP:** use an optimized, correctly sized hero poster; avoid making a large video the only meaningful above-the-fold content.
- **INP:** avoid expensive synchronous work on input or repeated large DOM updates.
- **CLS:** reserve media dimensions and avoid late layout shifts from fonts, banners, or injected content.
- Keep Vazirmatn self-hosted with `font-display: swap`.
- Add route-specific bundles only after measuring a real bundle or interaction problem.
- Paginate growing admin and activity lists.

## 13. Content voice

Persian copy should be:

- Clear, warm, and direct.
- Appropriate for medical students and learners.
- Consistent in terminology across catalog, learning, payment, and account pages.
- Free of internal implementation terms such as “endpoint,” “payload,” or “provider” unless shown in an admin/technical context.
- Honest about unavailable features, payment status, and placeholder assets.

Use familiar action wording such as:

- `ثبت‌نام کن`
- `رفتن به داشبورد`
- `خرید و پرداخت`
- `دریافت جزوه`
- `دریافت مجوز پخش`
- `ثبت پاسخ‌ها`

## 14. Asset rules

- Store production fonts and public images locally.
- Keep private notes and protected media outside public storage.
- Do not reference nonexistent hero or video files.
- Every important image needs a deliberate fallback and dimensions.
- Use SVG or optimized raster assets for interface decoration; do not add large decorative assets without a measured benefit.

## 15. Source of truth

- Design tokens: `resources/css/app.css`.
- Global layout: `resources/views/layouts/app.blade.php`.
- Public and learner composition: `resources/views/`.
- Persian product copy: `lang/fa/app.php` and page templates.
- Product constraints and pending visual decisions: `SPEC.md`, `DECISIONS.md`, and `docs/PROJECT_STATUS.md`.

When the final client design file arrives, update the tokens and component treatments here first, then apply the smallest possible template changes. Keep RTL, accessibility, payment transparency, and reduced-motion behavior as non-negotiable requirements.
