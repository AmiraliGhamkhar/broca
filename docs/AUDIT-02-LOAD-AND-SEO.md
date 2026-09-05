# Audit — Load Readiness (100–1000 users) + SEO/AEO

Date: 2026-09-05 · Target: Mizbanfa shared cPanel, `brocamed.ir`, PHP 8.4, MySQL.

---

# Part 1 — Can it handle 100–1000 concurrent visitors?

**Short answer: yes for 100–300, and yes for 1000 *if* the traffic is mostly
public/anonymous — but only after the queue fix below, and only with the cron
installed.** The application code is efficient; the binding constraint is shared
hosting itself, not Broca.

## 🔴 LAUNCH BLOCKER (found and fixed this pass)

**Queued email was never being sent — every signup would be locked out.**

The chain:
1. `VerifyEmailNotification` and `ResetPasswordNotification` implement
   `ShouldQueue`.
2. `QUEUE_CONNECTION` defaults to `database`, so those mails become rows in `jobs`.
3. **Nothing consumed that queue.** `docs/RUNBOOK.md` and
   `docs/CPANEL_DEPLOYMENT.md` described a cron-driven worker, but no entry
   existed in `routes/console.php` — documentation is not execution.
4. `routes/web.php:121` gates `/dashboard` **and `/checkout`** behind `verified`.

Net effect on launch day: a user registers, never receives the verification
link, and cannot reach the dashboard or buy anything. Every signup, silently.
Password reset would be equally dead.

**Fix applied** — cron-safe drain in `routes/console.php`:

```php
Schedule::command('queue:work --stop-when-empty --tries=3 --backoff=60 --timeout=55 --max-time=50')
    ->everyMinute()->withoutOverlapping(5)->runInBackground();
```

`--stop-when-empty` and `--max-time` keep it a short-lived process (shared
hosting forbids daemons); `withoutOverlapping` stops slow SMTP from stacking
workers on the every-minute tick. Guarded by `tests/Feature/ScheduledTasksTest.php`
so a future edit that drops it fails CI instead of failing users.

> **This makes the cron mandatory, not optional.** One cPanel cron entry now
> drives email delivery, subscription expiry, and payment reconciliation.

## Findings — throughput

### L1 — No compression or browser caching · FIXED

`public/.htaccess` had only Laravel's default rewrite block: no `mod_deflate`,
no `mod_expires`, no `Cache-Control`. Every visitor re-downloaded 59 KB of CSS
and 54 KB of JS uncompressed, on every page, forever.

**Fix.** DEFLATE for text/CSS/JS/JSON/XML/markdown (~11 KB gzipped vs 59 KB —
**~80% saving**), plus `max-age=31536000, immutable` for Vite-fingerprinted
bundles and woff2 fonts. HTML is explicitly `no-cache, private` — pages carry
auth state and free-quota badges and must never be shared between users.
Everything is wrapped in `<IfModule>` so a missing module cannot 500 the site
(LiteSpeed, which Mizbanfa runs, honours both syntaxes).

This is the single largest win available: it cuts bandwidth per returning
visitor to near zero and directly improves LCP.

### L2 — Crawler endpoints rebuilt the whole catalog per hit · FIXED

`SeoController::sitemap()` and `MarkdownController::llms()` each walked every
visible subject, published course and published post on **every request**, with
no caching — while `robots.txt` explicitly invites GPTBot, ClaudeBot,
PerplexityBot and friends to poll them.

**Fix.** Both cached for 1 hour, invalidated on publish/unpublish/delete by
`PublicIndexCacheObserver` (registered for `Course`, `Subject`, `BlogPost`,
following the existing `CourseFreeCapObserver` convention). Crawler traffic no
longer competes with real users for DB connections, and new content still
appears immediately.

### L3 — Query hygiene is genuinely good — verified, no N+1 found

Checked the hot paths line by line:
- `DashboardController` eager-loads the full tree
  (`course.subject`, `course.author`, `course.videos`, …) with constrained
  closures — no N+1.
- `CatalogController` uses `withCount` for the four content counts rather than
  loading collections, and paginates at 12.
- `Learner\FlashcardController` computes due-counts with **one** grouped
  aggregate join instead of per-deck queries.
- `User::activeSubscription()` and `isEnrolledIn()` are memoised per request,
  so repeated entitlement checks in a Blade loop cost one query, not N.
- `EntitlementService` caches free-cap counts for 300s and **fails closed**.

Indexing matches the access patterns: `subscriptions(user_id, status,
starts_at, ends_at)` composite, `invoices(status, expires_at)`,
`user_flashcard_schedules` due-index, `course_enrollments UNIQUE(user_id,
course_id)`. No missing index found on a hot query.

Injection risk: **none found.** Only three raw fragments exist — `whereRaw('1 = 0')`
(constant), a `selectRaw` of literal column names, and check-constraint DDL in
migrations. All user input goes through bindings, and `CatalogController` even
escapes LIKE wildcards (`addcslashes($q, '\\%_')`), which most codebases miss.

### L4 — Session + cache on the database: the real 1000-user constraint

`SESSION_DRIVER=database` and `CACHE_STORE=database`. Every authenticated
request does a session `SELECT` + `UPDATE`, so concurrent logged-in users
translate directly into DB writes, and shared hosting typically caps
`max_user_connections` around 25–50.

**Assessment by shape of traffic:**
- **1000 concurrent *anonymous* readers** — fine. With L1+L2 they are served
  mostly from disk/opcache and cached documents.
- **1000 concurrent *logged-in* users** — this is where a shared plan will
  strain first. Expect connection-pool contention before CPU exhaustion.

**cPanel-compatible mitigations, in order of preference:**
1. **`SESSION_DRIVER=file`** (and `CACHE_STORE=file`). On a single-server
   shared host this is *faster* than the DB driver and removes the write
   pressure entirely. The usual objection — no shared state across web nodes —
   does not apply to a single cPanel account. **This is the recommended change
   before a traffic spike.** I have not applied it: it invalidates all existing
   sessions on switch, so you should choose the moment.
2. **Redis** if Mizbanfa offers it on the plan (`config/cache.php` already has
   the connection defined). Ask support — many Iranian hosts do.
3. Raising `max_user_connections` is a host-support request, not a code change.

**Flag (cPanel constraint):** true horizontal scaling, an object cache server,
or a queue daemon are all impossible on shared hosting. If sustained
1000-concurrent *authenticated* load is the real target, the honest answer is a
VPS — not a code change. For launch traffic and normal growth, the current
setup with the fixes above is appropriate.

### L5 — `APP_DEBUG` / `LOG_LEVEL` must change for production

`.env.example` ships `APP_DEBUG=true`, `LOG_LEVEL=debug`. In production this
leaks stack traces (including DB credentials in connection exceptions) and
writes a large log on every request — a real disk and I/O cost under load, and
a security exposure. Production `.env`: `APP_DEBUG=false`, `LOG_LEVEL=error`.

---

# Part 2 — SEO + AEO

**This is already well above typical standard.** The `llms.txt` + Markdown-twin
implementation is current best practice and correctly reasoned in-code. Most of
my job here was verifying it rather than fixing it.

## Already correct

- **`llms.txt`** at the root following the llmstxt.org convention, generated
  from the same Eloquent rows as the HTML — it cannot drift from the site.
- **Markdown twins** (`/catalog.md`, `/blog/{slug}.md`, …) served as RFC 7763
  `text/markdown`, advertised via `<link rel="alternate">`, a `Link:` header,
  and an sr-only hint. Route ordering is correct (`.md` patterns registered
  before `/{slug}`) with a comment explaining why.
- **`Accept: text/markdown` negotiation** in `ServeMarkdown`, correctly only
  switching on an explicit q-weighted preference — a wildcard `Accept` is
  treated as "no preference", so browsers are unaffected. The in-code note that
  this is not cloaking (same URL, client-requested representation) is right.
- **robots.txt distinguishes retrieval agents from training agents**
  (`OAI-SearchBot`/`ChatGPT-User`/`Claude-User` vs `GPTBot`/`ClaudeBot`/
  `CCBot`/`Google-Extended`) and names them explicitly rather than relying on
  `User-agent: *`. That distinction is exactly what drives citation traffic,
  and the per-bot blocks matter because several engines ignore wildcard allows.
  Cloudflare's `Content-Signal` line is a good forward-looking addition.
- **Structured data**: `Organization` + `WebSite`/`SearchAction` sitewide,
  `Course` on course pages, `FAQPage` on the landing page. Encoded with
  `JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT`, which correctly
  prevents `</script>` breakout — there is even a dedicated
  `JsonLdEscapingTest`.
- **Semantics/i18n**: `<html lang="fa" dir="rtl">`, per-page canonical, OG +
  Twitter cards with dimensions, self-hosted Vazirmatn/Lalezar preloaded with
  `crossorigin` and `font-display: swap`.

## Findings

### S1 — Core Web Vitals were being lost to transport, not markup · FIXED

The markup was already LCP-friendly (preloaded self-hosted fonts, no
render-blocking third parties), but **L1** meant every asset shipped
uncompressed and uncached. INP/LCP gains from the `.htaccess` work land here
too. CWV is a real ranking input, so this is an SEO fix as much as a perf one.

### S2 — Sitemap lacked `Cache-Control` and freshness signals · PARTIALLY FIXED

Now emits `Cache-Control: public, max-age=3600`. `lastmod` is present per URL.
Note `priority` is included but has been **ignored by Google since 2015** —
harmless, not worth removing.

### S3 — `og:image` is a single static default

Every page shares `/images/og-default.png`. Per-course and per-post OG images
measurably improve social/AI-surface CTR. Not fixed: needs design assets, and
generating them on shared hosting without ImageMagick is a real constraint.
Recommend design-supplied images on the top ~10 courses, not runtime generation.

### S4 — No `Article`/`MedicalWebPage` schema on blog posts

Courses have `Course` schema; blog posts have none. For a YMYL medical topic,
`MedicalWebPage` with explicit `author`/`reviewedBy` (data you already model via
contributors) is the highest-leverage remaining structured-data win — it is
exactly what answer engines extract when deciding whether to trust and cite
medical content. **Recommended next step**, deliberately not done in this pass
because it should land together with the real contributor data (see the
fabricated-faculty fix in AUDIT-00), not against placeholder rows.

---

# Your action list before sending to the host

**In cPanel (required):**
1. MultiPHP Manager → PHP **8.4** for `brocamed.ir` (check CLI too).
2. **Cron Jobs**, every minute — now drives email, expiry, *and* reconciliation:
   ```
   * * * * * cd /home/brocamed/broca && /opt/cpanel/ea-php84/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
   ```
3. Point the domain document root at `/home/brocamed/broca/public`.

**In production `.env`:**
```
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
APP_URL=https://brocamed.ir
APP_FORCE_HTTPS=true
TRUSTED_PROXIES=*
```
`TRUSTED_PROXIES` matters: without it, HTTPS detection fails behind the host's
TLS terminator (see AUDIT-00 for why this silently broke under `config:cache`).

**Verify after deploy:**
```
curl -I https://brocamed.ir/                      # 200, Cache-Control present
curl -sI https://brocamed.ir/build/assets/*.css   # immutable, max-age=31536000
curl -sH 'Accept-Encoding: gzip' -I https://brocamed.ir/  # Content-Encoding: gzip
curl -s https://brocamed.ir/llms.txt | head       # AEO index renders
```
Then register a real account and confirm the verification email arrives within
~60 seconds — that is the end-to-end proof the queue cron is working.

**Decisions I need from you:**
- Zibal: remove or finish? (AUDIT-01 P2)
- Switch `SESSION_DRIVER` to `file` before launch? (L4 — recommended; logs
  everyone out at the moment of the switch)
