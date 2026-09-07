# Project status

What exists, what works, and what is still a placeholder. Referenced from
`README.md`; updated 2026-09-07 (round 9).

## Shipped and working

| Area | State |
|---|---|
| Public site (home, catalog, subjects, courses, blog, plans, legal) | Server-rendered Blade, SEO + Markdown twins + JSON-LD |
| Accounts | Register (email + mobile), login with email **or** mobile, password reset, suspension, admin TOTP 2FA with recovery codes |
| Verification | Emailed link **and** SMS one-time code — either activates the account (`docs/SMS_AND_VERIFICATION.md`) |
| Entitlements | Freemium caps enforced server-side (`EntitlementService` + `ContentPolicy`), never in JS |
| Learner | Video playback with progress, PDF notes, SM-2 flashcards, untimed quizzes |
| Payments | ZarinPal (shetabit) with invoices, server-side verification, idempotent activation, reconciliation; Zibal driver wired as a second option |
| Admin | Dashboard, subjects/courses/videos/notes/decks/cards/quizzes/questions CRUD, publication workflow, users, activity log, appearance, backups |
| Telegram bot | Content + user management, cover images, backups, **and** an operations menu (health, plan restore, queue, SMS test) |
| SEO / LLM | `robots.txt`, `sitemap.xml`, `/llms.txt`, `.md` twins with content negotiation |
| Test suite | CI (MySQL 8, PHP 8.4) green on this branch: phone verification, plan lineup, admin login/recovery, Telegram ops and the SMS driver all covered |

## Known gaps / placeholders

| Item | Note |
|---|---|
| **Committed assets** | `public/build` is committed for Node-less cPanel hosts; CI fails a push whose build output does not match the sources (`npm run build`). A rebuild after this round's markup was byte-identical, so nothing needs re-committing. |
| **SMS panel not chosen** | Driver-based; defaults to `log`, so nothing is sent until `SMS_DRIVER=http` + `SMS_HTTP_URL` are set. `docs/SMS_AND_VERIFICATION.md` §3. |
| Legal copy | Terms / privacy / medical disclaimer are placeholders (`v1-placeholder`) — real text required before launch. |
| Prices | رایگان / ۲۷۰ تومان / ۶۰۰ تومان are the confirmed lineup, stored in the `plans` table and editable in the admin panel. |
| Video hosting | `PlaceholderVideoProvider` + a committed sample asset; a real VOD/CDN provider replaces one binding. |
| Payments in production | `BROCA_CHECKOUT_ENABLED=false` until a live merchant id and a full sandbox→live cycle are verified. |
| Hero / brand assets | Placeholder artwork; final palette hexes and hero asset come from the client. |
| FSRS scheduling | SM-2 is implemented; FSRS is a drop-in replacement behind the same signatures. |
| Blog authoring | Admin/Telegram-driven Markdown; no WYSIWYG. |

## Operational surface

```bash
php artisan broca:ops:health           # delivery, queue, plans, admins, bot
php artisan broca:user:diagnose {id}   # why an account cannot sign in (read-only)
php artisan broca:user:repair {id}     # restore access (status, verification, password, role)
php artisan broca:identifiers:normalize
php artisan broca:sync-plans           # canonical lineup (free / 1-month / 3-month)
php artisan broca:sms:test {phone}
```

The same health report is available in Telegram: «🩺 عملیات و سلامت».

## Definition of done for launch

- [ ] Real legal copy (terms, privacy, medical disclaimer)
- [ ] Live ZarinPal merchant + one full payment → callback → subscription →
      expiry cycle
- [ ] SMTP verified end-to-end: register → mail arrives → link → dashboard
- [ ] SMS panel chosen and `broca:sms:test` green
- [ ] Cron installed (`schedule:run`) or `BROCA_NOTIFICATIONS_QUEUE=sync`
- [ ] `broca:ops:health` exits 0
- [ ] Backup restored successfully at least once
