# SMS & account verification

How a new account is activated, what can stop that happening, and how to see
which of it is broken in under a minute.

Related: `docs/RUNBOOK.md` §11 (auth operations), `docs/CPANEL_DEPLOYMENT.md`
(deploy + cron), `DECISIONS.md` "Round 9".

---

## 1. The model: two channels, either one is enough

Registration asks for an email **and** a mobile number, so registration now
sends to both:

| Channel | What is sent | Where it is accepted |
|---|---|---|
| Email | signed verification link (`VerifyEmailNotification`, 60 min) | `/email/verify/{id}/{hash}` |
| Mobile | 6-digit one-time code by SMS (`PhoneVerificationNotification`, 10 min) | `POST /phone/verify` |

`App\Http\Middleware\EnsureVerifiedContact` (route alias `verified.contact`)
lets a request through when **either** `email_verified_at` **or**
`phone_verified_at` is set. It guards `/dashboard`, `/checkout`, playback and
everything under `/admin`.

**Why it is built this way.** The funnel used to have one path in. Every
failure of that path — a queue worker that is not running, an SMTP port the
host blocks, a provider that silently drops the message, a spam folder the
student never opens — produced the same result: the account exists and cannot
be used. Mail is the least reliable dependency on shared hosting, so it must
not be the only one.

Registration sends both channels *after* the session is created, and each
inside its own `try/catch`: a dead transport costs the user a resend, never a
500. If neither could be delivered the app logs at `critical` with the account
id — that line is the one to alert on.

---

## 2. Delivery is inline, on purpose

`config/broca.php`:

```php
'notifications' => [
    'queue' => env('BROCA_NOTIFICATIONS_QUEUE', 'sync'),
],
```

`sync` means the verification mail and the SMS code are sent during the
request that creates the account. That is deliberate: with `database`, the mail
waits in the `jobs` table for a worker, and on shared hosting that worker is a
cron entry that may not exist, may be paused, or may have died weeks ago. The
symptom is not an error — it is silence, and every new signup is dead on
arrival.

Switch to `database` only on a host where you have watched the worker drain:

```bash
# the queue is actually moving
php artisan tinker --execute 'echo DB::table("jobs")->count();'   # expect 0
```

Backups, media jobs and anything else keep using `QUEUE_CONNECTION`.

---

## 3. The SMS driver

No Iranian panel had been chosen when this was built, so the stack is
driver-based (`config/sms.php`), and the default ships nothing anywhere.

| Driver | Behaviour |
|---|---|
| `log` **(default)** | Writes the message to `laravel.log`; reports success. The code is only rendered in the log outside production. |
| `null` | Discards everything (CI, staging clones of production data). |
| `http` | Generic panel driver — the request is described entirely in env. |

### Going live with a panel

```env
SMS_ENABLED=true
SMS_DRIVER=http
SMS_FROM=3000xxxx                       # your approved sender number
SMS_HTTP_URL=https://panel.example/send
SMS_HTTP_METHOD=POST
SMS_HTTP_ENCODE=json                    # or: form
SMS_HTTP_HEADERS={"X-API-KEY":"your-key"}
SMS_HTTP_BODY={"receptor":":to","message":":message","sender":":from"}
SMS_HTTP_SUCCESS_STATUS=200,201,202
SMS_HTTP_SUCCESS_CONTAINS=              # optional second gate
SMS_HTTP_TIMEOUT=15
```

`:to`, `:message`, `:from`, `:code` and `:reference` are substituted in the
URL, in every header value and in every body value. Two examples:

```env
# Kavenegar-style lookup (pattern) message
SMS_HTTP_URL=https://api.kavenegar.com/v1/${KAVENEGAR_KEY}/verify/lookup.json
SMS_HTTP_BODY={"receptor":":to","token":":code","template":"broca-verify"}

# Generic panel
SMS_HTTP_URL=https://panel.example/send
SMS_HTTP_BODY={"to":":to","text":":message","from":":from"}
```

Then prove it, before telling anyone it works:

```bash
php artisan broca:sms:test 09123456789
```

It prints the driver, whether the panel accepted the message, and the panel's
own answer.

**Two failure modes worth knowing.** A panel that answers HTTP 200 with a JSON
error body is treated as a failure only if `SMS_HTTP_SUCCESS_CONTAINS` names a
substring the success body always contains — set it. And a message that is
accepted by the panel is still not proof of delivery to a handset; the panel's
own dashboard remains the source of truth for deliverability.

---

## 4. The code itself

| Setting | Default | Why |
|---|---|---|
| `BROCA_PHONE_VERIFICATION` | `true` | master switch for the mobile path |
| `BROCA_PHONE_CODE_LENGTH` | `6` | longest code a person retypes without error |
| `BROCA_PHONE_CODE_TTL` | `10` minutes | bounds the value of a leaked/observed code |
| `BROCA_PHONE_CODE_ATTEMPTS` | `5` | guessing budget per issued code |
| `BROCA_PHONE_RESEND_COOLDOWN` | `60` seconds | each resend costs money |

The code is stored **bcrypt-hashed** (`users.phone_verification_code`) and
cleared the moment it is used, expires, or burns its attempt budget. A 6-digit
code is 10⁶ possibilities, so the TTL and the attempt counter — not the hash
alone — are what make a table dump unprofitable.

The verification screen accepts Persian or Latin digits, because the code is
shown in Persian numerals and a Persian-language keyboard types Persian
numerals.

---

## 5. When somebody says "nothing arrived"

```bash
php artisan broca:ops:health      # or press «🩺 عملیات و سلامت» in the bot
```

It answers, in one screen: is the database reachable; which mail driver is
configured; which queue connection the notifications use; how many jobs are
pending and how many failed; which SMS driver is live and whether its URL is
set; whether the plan lineup is complete; how many admins there are and how
many can actually sign in. It exits non-zero when anything is wrong.

Then, in order:

1. **Pending jobs with a non-`sync` notification queue** → the worker is not
   running. Fix the cron (`* * * * * php artisan schedule:run`) or set
   `BROCA_NOTIFICATIONS_QUEUE=sync`.
2. **`mail.default` is `log`** → no mail can leave the server. Set
   `MAIL_MAILER=smtp` and real credentials.
3. **`sms.default` is `log`** → codes are being written to the log, not sent.
4. **Both look fine and the user still has nothing** → look at the account:

```bash
php artisan broca:user:diagnose user@example.com
```

which prints the stored identifiers, the account status, both verification
flags, the password's hash algorithm and the number of live sessions — read
only, and it names the repair command for whatever it finds.

```bash
# unblock one account without changing its password
php artisan broca:user:repair user@example.com --verify-email --force
```
