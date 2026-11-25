# Shopify feature — go-live checklist

Everything Collectify did now runs on `frugaldomain.site`. These are the steps
only you can do on the server, in order.

## 1. Upload and migrate

1. Upload the site as usual (frontend build + `api/`).
2. From `api/`:
   ```bash
   php artisan migrate --force
   php artisan optimize:clear
   ```
   New tables/columns: Shopify sync tables, store team (`users.store_id`,
   `store_roles`, `store_role_user`), billing (`payment_methods` columns,
   `plan_payment_method`, `subscriptions.payment_method_id/reference`), indexes.

## 2. `.env` (API) — add / check

| Key | Value |
|--|--|
| `SHOPIFY_WEBHOOK_ENDPOINT` | `https://api.frugaldomain.site/api/shopify/webhooks` |
| `SHOPIFY_WEBHOOK_SECRET` | optional fallback; each store can have its own secret |
| `CACHE_STORE` | `file` (recommended on shared hosting) |
| `QUEUE_CONNECTION` | `database` |

CORS / Sanctum: the dashboard is on `frugaldomain.site`, so no new domain is
needed. Remove `collectify.sbs` from `SANCTUM_STATEFUL_DOMAINS` / CORS if it
was there.

## 3. Cron (required — the sync queue runs from it)

One line in the hosting cron, every minute:

```
* * * * * cd /path/to/public_html/api && php artisan schedule:run >> /dev/null 2>&1
```

It drains the Shopify queues (`queue:work --stop-when-empty --max-time=50`) and
re-sends failed pushes to Shopify (`shopify:outbound-dispatch-due`).

## 4. Shopify app settings

Stores connect with a shop domain + Admin API token (a custom app), so there is
no OAuth redirect to change; the webhooks are re-pointed in step 5.4. Only if
you also created an app in the Shopify Partner dashboard, update every URL that
still points at Collectify:

- App URL → `https://frugaldomain.site/admin/my-store`
- Allowed redirection URL(s) → the same host (`https://frugaldomain.site/...`)
- Webhook / compliance webhook URLs → `https://api.frugaldomain.site/api/shopify/webhooks`

## 5. Reconnect your test store

1. Sign in on `frugaldomain.site` (or create an account).
2. Open `/shopify` → **Connect your store** (this turns the account into a store owner),
   or go straight to **Admin → Shopify → Connect & Sync**.
3. Paste the shop domain and the Admin API token, connect, start the import.
4. Point the store's webhooks at the new domain:
   ```bash
   php artisan shopify:register-webhooks            # every connected store
   php artisan shopify:register-webhooks --owner=ID # one owner
   ```

## 6. Retire `collectify.sbs`

On the old host, send every URL to the feature page with a permanent redirect
(`.htaccess` in the old document root):

```apache
RewriteEngine On
RewriteRule ^ https://frugaldomain.site/shopify [R=301,L]
```

Keep the redirect (and the domain) for at least 6–12 months so search engines
move the old ranking to `/shopify`. Then submit `https://frugaldomain.site/sitemap.xml`
in Google Search Console.

## 7. Billing (optional now)

`.env` must have `FRONTEND_URL=https://frugaldomain.site`: PayPal and Stripe send
the buyer back to `/admin/billing/upgrade` on that host.

Admin → Shopify → **Payment Methods** → *Add payment method*, then pick the type:

| Type | What to fill in | How the plan switches |
|--|--|--|
| PayPal (also Visa / Mastercard) | Client ID + secret from developer.paypal.com → Apps & Credentials, mode `sandbox` first, then `live` | automatically after the capture |
| Stripe (Visa / Mastercard) | secret key `sk_test_...` (then `sk_live_...`) | automatically once the session is paid |
| Sham Cash | your account code (+ holder name) | the owner sends the transfer number / receipt photo; you press *Confirm payment* |
| Manual | instructions in each language (bank or card details) | same as Sham Cash, proof optional |

Then Admin → Shopify → **Plans**: create a paid plan and tick its payment
methods. Receipts show under **Subscriptions** (*Receipt* button); they are
stored privately in `api/storage/app/private/billing-receipts`, never in public.
The free plan stays the default.

## 7b. Store owners

Admin → Shopify → **Store Owners** lists every store owner (with or without a
store) and adds one by hand. Owners can also sign up on their own at
`/shopify/register` and sign in at `/shopify/login`.

## 8. Popup

Admin → Appearance → **Promo popup**: on/off, link (default `/shopify`), text in
5 languages.

## 9. Then delete the `shopify` folder

Only after steps 1–5 work: the store is connected, products imported, a change
in Shopify shows up in the dashboard, and a change in the dashboard reaches Shopify.
