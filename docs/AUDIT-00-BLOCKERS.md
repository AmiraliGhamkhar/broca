# Broca — Pre-Audit Blocker Report

Date: 2026-09-05. Status: **audit paused at §6, awaiting client decisions.**

Confirmed environment (client answers, 2026-09-05):

| Fact | Answer |
| --- | --- |
| SSH / Node on host | Unknown → assume worst case: neither |
| Deploy method | Manual FTP / cPanel File Manager |
| PHP version | 8.3 or lower |
| DB engine | MySQL / MariaDB |
| ZarinPal | Sandbox, pre-launch (`BROCA_CHECKOUT_ENABLED=false`) |
| 3D heart video | Not yet supplied |

Three of these turn latent repo issues into hard blockers. Per §1 of the
directive these are flagged with trade-offs rather than silently worked around.

---

## BLOCKER 1 — The dependency tree cannot run on PHP 8.3

`composer.json:9` requires `"php": "^8.4"`. That is not merely an advisory
constraint; the locked tree genuinely needs 8.4:

- `composer.lock` — `shetabit/payment` v7.0.0 and `shetabit/multipay` v3.0.4
  both require `php: ^8.4`. **This is the payment gateway.**
- `composer.lock` — the entire Symfony 8.1 component set (`http-kernel`,
  `console`, `routing`, `http-foundation`, …) requires `php: >=8.4.1`.

Only `laravel/framework` v13.26.1 itself tolerates `^8.3`. Everything under it
does not. On PHP 8.3, `composer install` refuses to resolve; if the `vendor/`
directory is uploaded anyway (which manual FTP invites — see Blocker 2), the
app fatals at boot on Symfony's 8.4-only syntax.

Good news: no application code in `app/` uses 8.4-only language features
(no property hooks, no asymmetric visibility) — the blocker is entirely in
vendor code, so it is a dependency problem, not a rewrite.

**Options — client must choose:**

- **A (recommended). Raise the host to PHP 8.4** via cPanel MultiPHP Manager.
  Zero code change, keeps Laravel 13 + the current payment driver. Most cPanel
  hosts already expose 8.4. Confirm the CLI PHP version too (MultiPHP sets the
  web handler; `php` on the shell can differ — the cron in `routes/console.php`
  needs the 8.4 binary path, e.g. `/opt/cpanel/ea-php84/root/usr/bin/php`).
- **B. Downgrade the stack to PHP 8.3.** Requires dropping to Laravel 12 +
  Symfony 7 + an older `shetabit/payment`. That is a multi-day migration
  touching the payment adapter, and it moves the project onto a shorter
  support window. Not recommended.

Until this is resolved, no backend fix I write can be verified against the real
target — I would be auditing code that cannot boot in production.

## BLOCKER 2 — Manual FTP deploy + current `.gitignore` ships a broken site

`.gitignore` excludes `/vendor` and `/public/build`. Neither exists in the repo.
`public/build` is absent from the working tree right now.

With manual File Manager upload and no SSH, that means: **no Composer
autoloader and no compiled CSS on the server.** The site does not boot, and if
it did it would render with zero Tailwind styling. `docs/CPANEL_DEPLOYMENT.md`
§4 assumes SSH + `composer install` + `npm run build` — that document does not
describe your actual deploy method and is currently misleading.

The frontend needs a real build: `package.json` uses Vite 8 + Tailwind v4 via
`@tailwindcss/vite`, and `layouts/app.blade.php:52` calls `@vite(...)`, which
reads `public/build/manifest.json` at runtime and throws if it is missing.

**Options:**

- **A (recommended for FTP). Build locally, commit the artifacts.** Un-ignore
  `/public/build` and commit compiled CSS/JS. Ship `vendor/` as a
  locally-built, `--no-dev --optimize-autoloader` archive uploaded alongside
  each release (keep `vendor/` out of Git — it is ~large and pollutes history).
  Trade-off: every release depends on a developer machine; the repo carries
  build output. This is the standard, honest answer for no-SSH cPanel.
- **B. Switch to cPanel Git Version Control + `.cpanel.yml` deploy hook.**
  None exists in the repo yet. Requires host-side Composer. Cleaner long-term,
  but only viable if the host provides it — needs confirming.

## BLOCKER 3 — Scheduler and cron require a PHP CLI you may not have

`routes/console.php` schedules three commands, two of them financially
load-bearing: `broca:expire-subscriptions` (hourly) and
`broca:reconcile-payments` (daily 03:30 — the safety net that heals invoices
paid at the gateway but unresolved locally). These need the cPanel Cron Jobs
entry documented in that file.

cPanel Cron Jobs are normally available even without SSH, so this is likely
fine — but it must be confirmed, because **without that cron, subscriptions
never expire and captured payments can silently fail to grant access.** That is
a money-correctness issue, not an ops nicety.

---

## Not blockers, but noted now (full write-up follows once unblocked)

- `.github/workflows/ci.yml` pins PHP 8.4 and runs `npm run build`. Whatever
  we decide above, CI must match the real target or it validates a fiction.
- `resources/views/welcome.blade.php:9-45` carries **fabricated faculty**:
  four named doctors ("دکتر سارا احمدی" et al.) with invented specialties.
  `SPEC.md:866` explicitly forbids presenting a placeholder as a real medical
  credential. This is a legal/trust exposure on a medical education product and
  is the single highest-priority content fix regardless of hosting.
- Landing hero (`<x-landing-hero />`) has a placeholder slot for the pending
  3D-heart asset; the Lenis/GSAP scroll work in `docs/PROJECT_STATUS.md` is
  correctly deferred. No decorative motion should be added in the meantime.
- Payment path is in genuinely good shape on first read: server-side
  amount-bound verification, row-locked finalizer, unique `gateway_reference`
  as a DB idempotency backstop, replay-safe callback. Detailed scrutiny pending.
- Zibal has driver config (`config/payment.php`) but no callback route — an
  incomplete integration that should either be finished or removed, not left
  half-wired.
