# Muller's Delights Catering Services

Website and admin panel for Muller's Delights Catering Services (Chef Muller, Zimbabwe).

- **Public site**: one page (hero with the spinning platter, packages, events, booking form), plus a booking status page and a privacy notice.
- **Bookings**: saved to the database, then the customer is prompted to send the same details on WhatsApp.
- **Deposits**: optional fixed-amount deposit through Paynow.
- **Admin panel** at `/admin`: bookings inbox, packages and prices, event types, all site wording, contact numbers, deposit settings, admin users.

Plain PHP 8.3, no Composer and no build step. MySQL in production (Aiven), SQLite for local development.

## Run locally

```bash
cp .env.example .env        # the defaults use SQLite, no database server needed
php bin/migrate.php         # creates data/app.sqlite and the starting content
php -S localhost:8080 -t public
```

Open http://localhost:8080, then http://localhost:8080/admin/setup.php to create the first admin (no token is needed outside production).

The web root is `public/`. Always serve that folder, never the project root.

## Structure

```
public/                 Web root
  index.php             The public page, rendered from the database
  booking.php           Saves a booking (JSON for the page's fetch, plain page without JS)
  booking-status.php    Customer's booking page: WhatsApp button, deposit, status
  pay.php               Starts a Paynow deposit
  paynow-result.php     Paynow server-to-server callback
  paynow-return.php     Where Paynow sends the customer back
  payment-check.php     Polled by the status page while a deposit is pending
  privacy.php, health.php
  admin/                Login, setup, dashboard, bookings, packages, events, content, settings
  css/ js/ lib/ img/
src/
  bootstrap.php         Env loading, error handling, helpers, security headers
  db.php                PDO connection (MySQL or SQLite), query helpers, rate limiting
  schema.php            Table creation and one-time seed
  content.php           Editable settings and their defaults, phone and money helpers
  auth.php              Sessions, login throttling, CSRF, flash messages
  paynow.php            Paynow client and payment status handling
  layout.php, admin-layout.php
bin/migrate.php         Run on every deploy (the Docker entrypoint does this)
docker/entrypoint.sh    Sets the port, migrates, starts Apache
Dockerfile, render.yaml
client-source/          The client's original flyer and logo pack (not deployed)
```

## Environment variables

| Variable | Needed | Purpose |
|----------|--------|---------|
| `APP_ENV` | production | `production` hides error details and locks setup behind the token |
| `DATABASE_URL` | production | Aiven service URI, `mysql://user:pass@host:port/defaultdb` |
| `DB_SSL_CA` | recommended | Path to the Aiven CA certificate. Without it the connection is encrypted but the server's identity is not verified |
| `ADMIN_SETUP_TOKEN` | production | Secret for the one-time `/admin/setup.php?token=...` |
| `APP_URL` | with a custom domain | Public https address, no trailing slash. Paynow callbacks use it. On Render it falls back to `RENDER_EXTERNAL_URL` |
| `TRUST_PROXY` | on Render | `1` so https and the visitor IP are read from Render's proxy headers |
| `PAYNOW_INTEGRATION_ID`, `PAYNOW_INTEGRATION_KEY` | for deposits | From the Paynow merchant dashboard. Deposits stay off until both are set |
| `PAYNOW_AUTH_EMAIL` | Paynow test mode | The merchant's Paynow login email. Remove once the integration is live |
| `APP_TIMEZONE` | optional | Defaults to `Africa/Harare` |
| `DB_DRIVER`, `DB_SQLITE_PATH` | local only | `DB_DRIVER=sqlite` for local development |

Never commit `.env`. It is in `.gitignore`.

## Free demo on Render (no database service)

`render.yaml` ships in demo mode: `DB_DRIVER=sqlite`, so the database is a file inside the container and Render is the only service needed.

1. Render: New > Blueprint, pick the repository and branch.
2. Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` (10 characters or more). Leave everything else empty.
3. Deploy, then log in at `/admin` with those details.

**Demo data does not last.** On Render's free plan the container's disk is wiped whenever the service sleeps (after about 15 minutes idle) or redeploys. Bookings, edits and extra admins are lost and the site returns to its starting content. The admin from `ADMIN_EMAIL` and `ADMIN_PASSWORD` is recreated on each start, so the login always works. Do not take real bookings in demo mode.

To go live, delete `DB_DRIVER` in Render's Environment tab and set `DATABASE_URL` to a MySQL database, as below. `ADMIN_EMAIL` and `ADMIN_PASSWORD` can then be removed: they only ever create an admin when none exists.

## Deploy to Render with Aiven MySQL

1. **Aiven**: create a MySQL service. Copy its Service URI and download its CA certificate (`ca.pem`).
2. **GitHub**: push this project to a repository.
3. **Render**: New > Blueprint, pick the repository. `render.yaml` creates one Docker web service.
4. Set the environment variables Render asks for:
   - `DATABASE_URL`: the Aiven Service URI. If it ends in `?ssl-mode=REQUIRED`, that suffix is ignored and can stay.
   - `DB_SSL_CA`: add `ca.pem` as a Secret File named `aiven-ca.pem`, then set this to `/etc/secrets/aiven-ca.pem`.
   - Leave the Paynow values empty for now.
5. Deploy. The container creates the tables and starting content on first start. The deploy fails loudly if it cannot reach the database.
6. In Render's Environment tab, copy the generated `ADMIN_SETUP_TOKEN` and open `https://YOUR-SITE/admin/setup.php?token=THE_TOKEN` to create the first admin. The page locks itself as soon as one admin exists.
7. When a custom domain is attached, set `APP_URL` to it (for example `https://mullersdelights.co.zw`).

Render's free plan sleeps after inactivity, so the first visit after a quiet spell takes a while to load. Admin sign-ins are stored in the container and end when it restarts or redeploys.

## Turning on Paynow deposits

1. In the Paynow merchant dashboard, create a **3rd Party Site or Link Profile** integration for USD. Note the Integration ID and Integration Key.
2. Set `PAYNOW_INTEGRATION_ID` and `PAYNOW_INTEGRATION_KEY` on Render. While the integration is in test mode, also set `PAYNOW_AUTH_EMAIL` to the merchant's Paynow login email.
3. In the admin panel, go to Settings > Online deposits, switch it on and set the amount.
4. Make a test booking, pay the deposit using Paynow's test flow, and confirm the booking shows "Deposit paid" in the admin panel.
5. Ask Paynow to set the integration live, then remove `PAYNOW_AUTH_EMAIL`.

**The Paynow client has only been tested against a local mock of the gateway, not against Paynow itself.** Step 4 is the real acceptance test and must be done before telling the client deposits work.

How payment state is decided: the customer returning from Paynow proves nothing. A deposit is marked paid only by a signed message from Paynow (the callback or a poll), and only if the amount is at least the deposit asked for.

## Security notes

- Admin pages require login. Every admin form carries a CSRF token. Login is throttled per address and per account.
- All queries are parameterised. All output is escaped. A content security policy blocks inline scripts.
- Booking prices are read from the database, never from the submitted form.
- The booking form has a honeypot field and a limit of 5 bookings per hour per address.
- Customers reach their booking through a long random link, not a guessable number.
- `src/`, `bin/` and `.env` sit outside the web root and are not served.

## Personal data

Bookings hold a name, phone number and event details. Chef Muller can delete any booking (and its payment records) from its page in the admin panel when a customer asks. The privacy notice at `/privacy.php` is a plain-language starting point and should be reviewed against Zimbabwe's Cyber and Data Protection Act before launch.

## Images

- `hero.png` is the braai platter from the Lavish Bites template, a stand-in until the client supplies a photo of their own food. Replace it with a round, transparent 800x800 PNG.
- `chef.jpg`, `plate.jpg`, `salad.jpg` and `buffet.jpg` are low-resolution crops from the client's flyer.
- Photos are not editable from the admin panel. Replace the files in `public/img/` and redeploy.
