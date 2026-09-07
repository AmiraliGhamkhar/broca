# Broca Website Design

## 1. Product summary

**Broca** is a Persian, RTL-first medical education platform built for three main audiences:

1. **Guests** discovering courses, plans, and blog content
2. **Learners** consuming videos, notes, flashcards, and quizzes
3. **Admins** managing content, publication flow, plans, and operations

The site should feel:

- **trustworthy** enough for medical-learning content
- **simple** enough for fast scanning in Persian
- **premium** without becoming visually heavy
- **structured** so content hierarchy is always clearer than decoration

This is a server-rendered Laravel + Blade product with light Alpine.js enhancement, so the design must remain strong even without complex frontend behavior.

---

## 2. Design direction

The current implementation is an **Airbnb-inspired, high-clarity interface** adapted for a medical-learning brand.

### Core visual traits

- **Pure white canvas** for a clean clinical feel
- **Dark ink typography** for trust and readability
- **One strong accent color** (`rausch`) for CTA emphasis and active states
- **Rounded cards and pill controls** for friendliness
- **Thin borders over heavy shadows** for a calm, modern layout
- **RTL-first composition** across all pages and components

### Brand personality

- **Clinical, not cold**
- **Premium, not luxurious**
- **Educational, not corporate**
- **Warm enough for students, disciplined enough for medical topics**

---

## 3. Visual system

### 3.1 Color palette

These are the implemented tokens in `resources/css/app.css`.

| Token | Value | Usage |
|---|---|---|
| `canvas` | `#ffffff` | page background |
| `surface-soft` | `#f7f7f7` | subtle card/input backgrounds |
| `surface-strong` | `#f2f2f2` | pressed/denser surfaces |
| `ink` | `#222222` | primary text, dark buttons, dark panels |
| `body` | `#3f3f3f` | long-form body text |
| `muted` | `#6a6a6a` | secondary copy, metadata |
| `muted-soft` | `#929292` | low-emphasis text |
| `hairline` | `#dddddd` | borders |
| `hairline-soft` | `#ebebeb` | soft dividers and table rules |
| `border-strong` | `#c1c1c1` | stronger form borders |
| `rausch` | `#ff385c` | primary accent, CTA, active state |
| `rausch-active` | `#e00b41` | hover/pressed accent |
| `rausch-disabled` | `#ffd1da` | disabled accent surfaces |
| `rausch-tint` | `#fff1f3` | tinted badge/panel backgrounds |
| `teal` | `#0f766e` | success/published state |
| `error-text` | `#c13515` | error feedback |
| `legal-link` | `#428bff` | legal/support link emphasis |

### Color rules

- Use **ink** for the primary interface structure.
- Use **rausch** only where the UI needs a clear “this matters” signal.
- Use **teal** for success, publication, confirmation, or healthy state.
- Avoid multi-accent competition. The system works because one accent dominates.
- Never rely on color alone to communicate state.

---

### 3.2 Typography

#### Fonts

- **Display / headings:** `Lalezar`
- **Body / UI / forms:** `Vazirmatn`

Both are self-hosted and already part of the current implementation.

#### Hierarchy

| Role | Typical treatment |
|---|---|
| Hero headline | `font-display`, `text-4xl` to `text-6xl` |
| Page title | `font-display`, `text-3xl` to `text-5xl` |
| Section title | `text-xl` to `text-3xl`, bold |
| Card title | `text-base` to `text-lg`, bold |
| Body copy | `text-sm` to `text-base`, relaxed line-height |
| Metadata | `text-xs` / `text-[11px]`, muted |
| Pills / tags | bold, compact, rounded-full |

#### Type behavior

- Persian body text should stay open and readable.
- Headlines can be expressive, but paragraphs should remain calm.
- Long medical content should use generous line height.
- LTR formatting should be applied to dates, prices, numeric totals, IDs, and codes where useful.

---

### 3.3 Shape, radius, and depth

#### Radius

- **Hero / major cards:** `rounded-3xl`
- **Panels / forms:** `rounded-2xl`
- **Inputs:** `rounded-xl`
- **Buttons / filters / tags:** `rounded-full`

#### Shadow

The design uses a restrained floating shadow, mainly for:

- hero support cards
- dropdowns
- form panels
- premium CTAs

Most structure should come from:

- borders
- whitespace
- contrast
- grouping

not from stacked shadow layers.

---

## 4. Layout system

### Global frame

The site uses a consistent full-page structure:

1. **Top announcement bar**
2. **Sticky navigation header**
3. **Main page content**
4. **Feedback/toast region**
5. **Footer with medical and legal context**

### Widths

- Primary public pages: `max-w-7xl`
- Forms and focused editing: `max-w-4xl`
- Reading/prose areas: narrower content blocks inside page shells

### Spacing principles

- Large breathing room on marketing/public pages
- Tighter but still clean density in admin areas
- Repeated section rhythm using strong top/bottom padding and border separators

### Responsive behavior

- Desktop navigation collapses into a mobile drawer
- Pill filters become horizontally scrollable on smaller screens
- Multi-column cards stack cleanly without changing content priority
- Hero becomes vertically centered and more text-led on mobile

---

## 5. Information architecture

### Public-facing areas

- **Home**
- **Catalog**
- **Course detail**
- **Plans**
- **Blog**
- **Legal pages**
- **Auth pages**

### Learner areas

- **Dashboard**
- **Video learning page**
- **Note download page**
- **Flashcard study flow**
- **Quiz and result views**

### Admin areas

- **Admin dashboard**
- **Subjects**
- **Courses**
- **Videos**
- **Notes**
- **Flashcard decks/cards**
- **Quizzes/questions**
- **Plans**
- **Blog CRUD**
- **Activity logs**
- **2FA/security**

---

## 6. Key page designs

### 6.1 Home page

The home page is the brand and product pitch.

#### Structure

- Large hero with two-column composition
- Four specialty/course category cards
- Four-pillar learning methodology section
- Faculty/reviewer trust section
- Final freemium CTA block

#### Hero behavior

The hero combines:

- a Persian educational headline
- a short value proposition
- primary and secondary CTAs
- trust micro-signals
- a supporting “atlas” card showing sample learning modules

#### Intent

The homepage should answer these questions immediately:

- What is Broca?
- Is it serious and medically credible?
- What can I learn here?
- Can I start for free?

---

### 6.2 Catalog page

The catalog is the main browsing experience.

#### Main elements

- page title and short context copy
- search form
- horizontal subject filter pills
- responsive course card grid
- clear empty state when nothing matches

#### Course card anatomy

- optional cover image
- subject label
- level pill
- course title
- summary/excerpt
- author/reviewer metadata
- single CTA to enter the course

#### Intent

The catalog should feel like a **structured learning library**, not a generic store.

---

### 6.3 Course detail / learning entry

The course page should guide a learner into the learning stack:

- overview
- published content only
- video lessons
- notes
- flashcards
- quizzes

Design emphasis should remain on:

- progression
- credibility
- access state
- one clear next action per content type

---

### 6.4 Plans page

The plans page is a pricing + trust page.

#### Structure

- centered intro
- 3-column plan comparison layout
- highlighted preferred paid plan
- explicit distinction between free and paid access
- checkout or fallback state depending on auth/subscription/config

#### Pricing design rules

- show **Toman** as the user-facing primary amount
- show **Rial** as supporting exactness when useful
- clearly indicate active subscription lockout
- never present a dead purchase CTA when checkout is disabled

#### Tone

This page should feel:

- transparent
- low-pressure
- trustworthy
- easy to compare

---

### 6.5 Blog

The blog extends the educational brand into editorial content.

#### Index page

- hero-like page intro
- responsive article grid
- category pills
- title + excerpt
- author display
- simple “read article” CTA

#### Post detail

The post page should prioritize:

- title
- category/context
- author/reviewer trust
- cover image if present
- readable long-form Persian body content

The blog should look like an integrated part of the learning ecosystem, not a separate marketing CMS.

---

### 6.6 Auth pages

Login, registration, and recovery views should be compact, calm, and confidence-building.

#### Visual goals

- minimal distractions
- strong labels
- obvious primary action
- clear validation feedback
- support for Persian inputs and machine-readable values

---

### 6.7 Learner dashboard

The learner dashboard should answer:

- What do I have access to?
- What should I do next?
- How far have I progressed?

#### Typical modules

- active subscription state
- enrolled/current courses
- due flashcards
- quiz results/progress
- recent activity and next step

The experience should feel more like a **study cockpit** than a social dashboard.

---

### 6.8 Admin dashboard

The admin UI is a “studio” rather than a generic back office.

#### Structure

- studio header + quick links
- pill-based admin navigation
- key metric cards
- high-contrast quick-action banner
- recent content/activity panels
- infrastructure/health side panel

#### Admin tone

- denser than public UI
- still branded and consistent
- optimized for scanning and repeated use
- visibly operational, not decorative

---

## 7. Component library

### 7.1 Header

Includes:

- announcement strip
- logo/wordmark
- desktop nav pills
- search bar
- auth actions or user menu
- mobile menu trigger

The header should always feel lightweight and sticky, never bulky.

### 7.2 Buttons

#### Primary

- `bg-rausch text-white`
- used for signup, important submit, and purchase actions

#### Dark secondary

- `bg-ink text-white`
- used for strong but non-primary actions

#### Outline / neutral

- bordered, white or soft background
- used for secondary navigation and management actions

#### Rules

- one strongest action per region
- maintain visible hover/focus states
- use destructive styling only for truly destructive actions

### 7.3 Cards

Core card families:

- specialty cards
- course cards
- blog cards
- pricing cards
- admin metric cards
- form panels

All cards follow the same system:

- clear title hierarchy
- muted support text
- thin borders
- rounded corners
- optional hover lift for discoverability

#### Pricing cards (`.prc-05`)

Three tiers on `/plans`: **رایگان**, **یک‌ماهه ۲۷۰ تومان**, **سه‌ماهه ۶۰۰ تومان**.

- Source: CodeFronts "Scale-Up Focused Plan Hover" (MIT), scoped under
  `.prc-05` in `resources/css/app.css`; no JavaScript.
- Colors are palette-only — `rausch` for the checkmarks, CTA hover and the
  featured glow, `ink`/`body`/`muted` for type, `hairline-soft` for borders,
  `rausch-tint` for the featured card wash, `teal` for the duration and
  per-month-equivalent accents. `surface-soft` for the free pill.
- Interaction stays as upstream: the hovered/focused card lifts
  (`translateY(-10px) scale(1.04)`) with a pre-rendered accent shadow, while a
  grid-level `:has()` rule eases siblings back and dims them.
- Prices are DB content (`plans.price_irr`, Rial) rendered as Toman; the
  free tier shows the word «رایگان» instead of `0`. Multi-month tiers add a
  per-month equivalent line, measured against the priciest per-month paid tier
  and hidden entirely when no saving exists. Amounts use `number_format()`
  digits inside a `dir="ltr"` node; digits inside Persian sentences
  (`۱ ماه دسترسی`, `۲۶٪`) go through `App\Support\PersianNumber`.
- Layout is fixed at three columns from `md` up (never `auto-fit`, which orphans
  the third card near 900px) and single column on phones.
- `public/plans-pricing-preview.html` is a static, framework-free preview of the
  same markup (same compiled stylesheet) for design review; it is not a route.

### 7.4 Pills, tags, and badges

Used for:

- subject filters
- content category markers
- level indicators
- status labels
- quick metadata

These help scanning and should remain compact and bold.

### 7.5 Forms

Forms are used heavily in auth and admin flows.

Rules:

- visible labels
- compact but readable Persian inputs
- strong borders on focus
- preserved values after validation
- inline or top-level error feedback
- safe support for dates, status values, numeric fields, URLs, and text blocks

### 7.6 Tables

Admin listing views use soft table styling:

- tinted headers
- thin row dividers
- muted metadata
- pill-like actions beside each record

### 7.7 Toasts and feedback

The global layout already supports:

- success messages
- error alerts
- dismiss actions

Feedback should be immediate, readable, and placed near the top of the content flow.

### 7.8 Empty states

Empty states use:

- soft dashed borders
- icon/emoji anchors
- short explanation
- single recovery action

This keeps the product from feeling broken when there is no content yet.

---

## 8. Content and voice

### Language

- Primary UI language: **Persian**
- Layout direction: **RTL**
- Technical/admin docs may remain English, but the product UI should stay Persian

### Voice

The copy style should be:

- direct
- warm
- confident
- medically responsible
- student-friendly

Avoid:

- excessive marketing exaggeration
- unexplained technical jargon in learner-facing UI
- vague or ambiguous payment or access wording

---

## 9. Medical trust design rules

Because this is a medical-learning platform, trust cues are part of the design system.

### Required trust signals

- author/reviewer visibility where relevant
- educational disclaimer in footer/legal areas
- explicit publication/review states in admin
- transparent payment and subscription states
- structured content hierarchy

### Forbidden patterns

- flashy gamification that weakens credibility
- unexplained health claims
- casual ambiguity around access, payment, or publication state
- publication flows that bypass review for medical content

---

## 10. Accessibility baseline

The website should maintain a practical WCAG AA baseline.

### Minimum expectations

- semantic headings and landmarks
- keyboard-usable navigation and menus
- visible focus states
- readable contrast
- clear error feedback
- adequate tap targets
- meaningful alt text for informative images
- no state communicated by color alone

### RTL accessibility notes

- ensure focus order still makes sense in Persian layouts
- ensure numeric/LTR content is readable and intentionally aligned
- avoid tight line lengths in long Persian paragraphs

---

## 11. Motion and interaction

Motion should support clarity, not spectacle.

### Acceptable motion

- gentle hover lift on cards
- button hover color changes
- dropdown reveal
- lightweight menu transitions

### Rules

- respect `prefers-reduced-motion`
- avoid large distracting transitions
- don’t make learning tasks depend on animation
- keep content stable to avoid layout shift

---

## 12. Imagery and media

### Cover images

- Course and blog covers should read well in wide-card ratios
- Current card usage suggests a **16:10-style presentation**
- Images should support the topic, not overpower text

### Visual direction

Imagery should feel:

- academic
- anatomical / scientific where relevant
- clean and high-quality
- not stock-photo generic if avoidable

### Fallbacks

- every important media block should degrade gracefully
- missing images must not collapse card structure
- long text should still communicate enough value without art

---

## 13. Admin-specific design notes

The admin panel should stay visually connected to the public site while becoming more operational.

### Key admin characteristics

- pill-tab navigation
- dashboard metrics first
- fast CRUD access
- clear publication states
- safe destructive actions
- visible security posture

### Blog admin

The new blog admin should support:

- CRUD management
- search/filter
- transition-based publication flow
- delete actions
- cover preview
- visible author/reviewer metadata

---

## 14. Implementation source of truth

These files represent the current implemented design most directly:

- `resources/css/app.css` — design tokens and shared visual behavior
- `resources/views/layouts/app.blade.php` — global layout shell
- `resources/views/components/landing-hero.blade.php` — homepage hero direction
- `resources/views/welcome.blade.php` — public homepage composition
- `resources/views/catalog/index.blade.php` — catalog browsing pattern
- `resources/views/plans.blade.php` — plan/pricing pattern
- `resources/views/blog/index.blade.php` and `resources/views/blog/show.blade.php` — editorial pattern
- `resources/views/admin/*` — admin visual system
- `docs/DESIGN-airbnb.md` — implementation-level inspiration/spec reference

---

## 15. Practical design summary

If someone redesigns or extends Broca, they should preserve these non-negotiables:

1. **RTL-first Persian experience**
2. **White + ink + single-accent visual system**
3. **Lalezar for display, Vazirmatn for body**
4. **Rounded, airy, trust-first interface**
5. **Clear educational hierarchy over decoration**
6. **Transparent access/payment/publication states**
7. **Public, learner, and admin areas that feel like one product**

In one sentence:

> **Broca should look like a modern Persian medical-learning studio: clean, credible, focused, and easy to navigate.**
