# Broca — Environment & Blocker Report (RESOLVED)

Date: 2026-09-05. Status: **all launch blockers cleared; CI green on the
real target (PHP 8.4 + MySQL).**

## Confirmed environment

| Fact | Value |
| --- | --- |
| Host | Mizbanfa shared cPanel |
| Domain | `brocamed.ir` |
| SSH | Available |
| Deploy | cPanel **Git Version Control** (`.cpanel.yml` now in repo) |
| PHP | 8.4 available — **must be selected in MultiPHP Manager** |
| DB | MySQL |
| Node on host | Unconfirmed — deploy is designed to work without it |
| ZarinPal | Sandbox, pre-launch (`BROCA_CHECKOUT_ENABLED=false`) |
| 3D heart video | Not yet supplied |

---

## Resolved

### 1. PHP 8.4 requirement — RESOLVED (action required in cPanel)

`composer.json` requires `php: ^8.4`, and this is real: `shetabit/payment` v7
and `shetabit/multipay` v3 (the ZarinPal driver) require `^8.4`, and the whole
Symfony 8.1 set requires `>=8.4.1`. On 8.3 the app does not boot.

No application code uses 8.4-only syntax, so nothing needed rewriting.

**Your action:** set PHP 8.4 in MultiPHP Manager for `brocamed.ir`, and check
the **CLI** version too — cPanel cron/SSH often defaults to an older EA-PHP.
`.cpanel.yml` pins `/opt/cpanel/ea-php84/root/usr/bin/php` and aborts the
deploy with a clear message if it resolves to anything below 8.4.

### 2. Deploy method — RESOLVED

Added **`.cpanel.yml`**, so *Update from Remote → Deploy HEAD Commit* now:
runs `composer install --no-dev --optimize-autoloader`; rebuilds assets **only
if npm exists**, else uses committed artifacts; refuses to finish if no Vite
manifest is present; migrates; rebuilds all caches — all behind
`artisan down`/`up`. Validated as YAML, and every task passes `bash -n`.

### 3. Node dependency / unstyled-site risk — RESOLVED

`public/build` was gitignored and absent. With Node unconfirmed on the host,
a deploy could ship a site with **zero CSS** (`@vite()` throws on a missing
manifest). Built assets are now committed (120 KB), and CI **fails if they
drift** from source. That guard immediately caught one stale build during this
work — the failure mode is real.

### 4. Cron — still your action

cPanel → Cron Jobs, every minute, using the 8.4 binary:

```
* * * * * cd /home/brocamed/broca && /opt/cpanel/ea-php84/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Without it, `broca:reconcile-payments` never runs — that is the safety net
that heals invoices paid at ZarinPal but unresolved locally. **Money captured
without access granted.** Not optional once checkout goes live.

---

## Bugs found and fixed in this pass

- **Fabricated medical faculty (highest severity).** The landing page
  hardcoded four invented doctors with invented institutional credentials
  ("عضو هیئت علمی دانشگاه علوم پزشکی"), and the seeder created them as
  publicly visible rows. `SPEC.md` forbids exactly this. Faculty is now
  DB-driven via `HomeController`, limited to visible contributors actually
  attached to published content, with an honest empty state describing the
  review *process* instead of inventing people. Seeder rows are renamed to
  obvious samples and set `is_visible = false`. Locked in by
  `tests/Feature/FacultyIntegrityTest.php`.

- **`quizzes.sort_order` missing (pre-existing, production-breaking).** Every
  sibling content table has it; `Admin\QuizController` validates it, writes it
  when reordering questions, and calls `orderBy('sort_order')` — which throws
  `SQLSTATE[42S22]` on MySQL, so the **admin quiz list was broken**. A fresh
  `php artisan db:seed` also aborted partway, leaving a half-populated DB.
  Added the column (with deterministic backfill) and the missing `Fillable`
  entry. Found because the new test seeds the full `DatabaseSeeder`.

- **Trusted proxies lost under `config:cache`.** `bootstrap/app.php` read
  `env('TRUSTED_PROXIES')` directly. After `config:cache` — which the deploy
  hook always runs — `env()` outside `config/` returns null, so proxy trust
  silently vanished. Behind Mizbanfa's TLS terminator that means
  `$request->secure()` is false forever: an **infinite HTTPS redirect loop**,
  and the Secure session cookie never set. Now resolved at request time in
  `App\Http\Middleware\TrustProxies` (config alone doesn't work either — the
  config repository isn't bound yet inside the `withMiddleware` closure).

---

## Still open

> Supersession note (Round 10, 2026-09-10 re-audit): the first three items
> below are resolved by later rounds — Zibal finished + inert (Round 6,
> DECISIONS.md), prices confirmed ۲۷۰/۶۰۰ تومان (Round 7), hero is a photo,
> not the 3D-heart video (Round 6; final pick still the client's).
> Legal copy (`v1-placeholder`) is still open — see
> docs/PROJECT_STATUS.md for the current launch gates.

- **Zibal** has driver config but no callback route — half-wired. Finish it or
  remove it; don't leave a money path partially built.
- **Placeholder register** (`SPEC.md` §15): real plan prices, legal copy, and
  the terms/privacy version strings are still `v1-placeholder`.
- **3D heart video** pending from client; hero has a marked placeholder slot.
  No decorative motion added in the meantime, per the no-slop rules.
- Remaining audit domains (DB indexing/N+1, auth, payments deep-dive, SEO/AEO,
  micro-interactions) not yet written up — the money path looked strong on
  first read: server-side amount-bound verification, row-locked finalizer,
  unique `gateway_reference` as a DB idempotency backstop, replay-safe callback.
