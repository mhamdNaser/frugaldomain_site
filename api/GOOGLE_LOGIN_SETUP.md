# Google Sign-In — setup guide

Google Sign-In is **free**. There is no token to buy and no paid tier for basic
authentication: you create an OAuth **Client ID** in Google Cloud Console with
your own Google account, at no cost and with no usage charge for sign-in.

I cannot create it for you because the console requires you to be signed in to
your own Google account. The steps below take about five minutes.

---

## 1. Create the OAuth client

1. Go to <https://console.cloud.google.com/>
2. Top bar → project selector → **New Project**
   - Name: `FrugalDomain` → **Create**
3. Left menu → **APIs & Services** → **OAuth consent screen**
   - User type: **External** → **Create**
   - App name: `FrugalDomain`
   - User support email: `medo@frugaldomain.site`
   - Developer contact email: `medo@frugaldomain.site`
   - Save and continue through the remaining steps (no scopes needed beyond the
     defaults: `email`, `profile`, `openid`)
   - Publishing status: **Publish app** (while it is in "Testing" only the
     accounts you list can sign in)
4. Left menu → **Credentials** → **Create credentials** → **OAuth client ID**
   - Application type: **Web application**
   - Name: `FrugalDomain Web`
   - **Authorised JavaScript origins** — add each of:
     ```
     https://frugaldomain.site
     https://www.frugaldomain.site
     http://localhost:5173
     ```
   - **Authorised redirect URIs** — only needed for the server-side flow; the
     button flow below does not use one. Add it anyway if you plan to switch:
     ```
     https://frugaldomain.site/auth/google/callback
     http://localhost:5173/auth/google/callback
     ```
   - **Create**

You now have a **Client ID** that looks like
`123456789-abcdefg.apps.googleusercontent.com`, and a Client Secret.

The Client ID is public and safe to ship in the frontend bundle. The **Client
Secret must never** appear in frontend code — it belongs in `api/.env` only.

---

## 2. Where the values go

**`api/.env`** (server, secret):

```
GOOGLE_CLIENT_ID=123456789-abcdefg.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxxxxx
```

**`sydev-front/.env` and `.env.production`** (public):

```
VITE_GOOGLE_CLIENT_ID=123456789-abcdefg.apps.googleusercontent.com
```

After editing `api/.env` on the server, run:

```bash
cd ~/domains/frugaldomain.site/public_html/api
php artisan config:clear
```

---

## 3. Which flow to use

**Recommended: Google Identity Services (the "Sign in with Google" button).**

The browser gets a signed JWT credential from Google and posts it to the API,
which verifies the signature against Google's public keys and then issues its
own Sanctum token. No redirect, no secret in the browser, and it works the same
for sign-in and sign-up — a first-time Google user is simply created on the
spot.

Verification on the server should check, at minimum:

- the token signature against `https://www.googleapis.com/oauth2/v3/certs`
- `aud` equals your Client ID
- `iss` is `accounts.google.com` or `https://accounts.google.com`
- `exp` is in the future
- `email_verified` is true

`google/apiclient` does all of this in one call:

```bash
composer require google/apiclient
```

```php
$client = new Google_Client(['client_id' => config('services.google.client_id')]);
$payload = $client->verifyIdToken($request->input('credential'));
// $payload['sub'], ['email'], ['name'], ['picture'], ['email_verified']
```

**Never** trust a Google account without verifying the token server-side, and
never link an account on `email` alone — match on the Google `sub` (the stable
account id) and only fall back to email when `email_verified` is true.

---

## 4. Database

Add to the `users` table:

- `google_id` — string, nullable, **unique** (stores Google's `sub`)
- `avatar` — string, nullable (optional, Google returns a picture URL)
- make `password` nullable, since a Google-only user never sets one

---

## 5. What is still needed

Once you paste the Client ID into the two `.env` files, the remaining work is:

1. a migration adding `google_id` / `avatar` and making `password` nullable
2. `config/services.php` entry for the Google credentials
3. a `POST /auth/google` endpoint that verifies the credential and returns a
   Sanctum token exactly like the password login does
4. the Google button on the login and register pages

Tell me when you have the Client ID and I will implement all four. The login
analytics already record a `provider` column, so Google sign-ins will show up
in the security log as `provider=google` automatically.
