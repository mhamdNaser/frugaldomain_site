# Shopify store management inside FrugalDomain — design

Date: 2026-11-24 · Status: draft for approval

## Intent
Retire `collectify.sbs` and run everything Collectify did inside FrugalDomain,
on `frugaldomain.site`, under FrugalDomain's identity. Collectify's own domain
gets few visitors; FrugalDomain's traffic should discover the feature.

## Decisions taken with the owner
- **No data migration.** The only Collectify data is the owner's test store
  (2 users, 1 store, 105 mirrored products, 0 orders). After the merge the owner
  reconnects the store on `frugaldomain.site`; the sync engine re-imports it.
- **The name "Collectify" is dropped.** The feature is "Shopify" inside
  FrugalDomain: public page `/shopify` (+ `/ar/shopify`, `/hi/…`, `/zh/…`, `/tr/…`).
  `collectify.sbs/*` gets a 301 to `https://frugaldomain.site/shopify` (owner
  sets this on the old host).
- **Promo popup** on the public site pointing to `/shopify`: corner card on
  desktop, bottom bar on mobile (never a full-screen interstitial — Google
  penalises those on mobile); appears 4 s into the first visit; dismissed →
  hidden 7 days; clicked → never again; not shown on `/shopify`, admin,
  account, or full-screen tools (drawing board, whiteboard, simulators);
  on/off and text (5 languages) editable in Admin → Appearance.
- **Admin navigation:** one main sidebar tab **"Shopify"** with its own
  dashboard and the feature's sub-tabs under it.
- **Billing:** super admin can create plans, add payment methods, and link
  plans to payment methods, with a complete subscription flow. The only active
  plan is free. Distribution is direct (not the Shopify App Store), so Shopify
  Billing API is not required. *(Assumed — the owner did not say the app is on
  the App Store.)*
- **Repos (owner's workflow):** frontend source in `frugaldomain/sydev-front`
  (pushed to `sydev-front` before deploy); full site (API + built frontend) in
  `frugaldomain`. The owner does all pushing and deployment. The `shopify`
  folder is deleted by the owner after verification.

## Goals
- Merchants connect a Shopify store at `frugaldomain.site` and get everything
  Collectify offered: two-way sync (webhooks in, outbound queue out), catalog,
  orders, customers, inventory, content, store team and roles.
- The feature looks and reads like the rest of FrugalDomain (fg tokens, both
  modes, five languages).
- Strong caching so shared hosting is not overloaded.
- SEO for `/shopify` on par with the rest of the site.

## Non-goals
- Migrating Collectify's database.
- A second online payment gateway beyond the first one the owner names (the
  gateway layer is pluggable; see Billing).
- Shopify App Store listing / Shopify Billing API.
- Pushing, deploying, or changing DNS / the old host.

## Anti-goals
- Breaking any existing FrugalDomain feature (tools, learning, forms, icons,
  appearance, analytics).
- Shopify tokens, password hashes or other secrets in any cache.
- A queue/cron setup that saturates shared hosting.
- Collectify branding or `collectify.sbs` links left anywhere.

## Approaches considered
| | Approach | Trade-off |
|--|--|--|
| **1 (chosen)** | Port Collectify into FrugalDomain module by module: add the Shopify module, three-way-merge the modules both apps share, port the dashboard pages onto FrugalDomain's i18n and tokens. | One codebase, one login, one identity. Cost: careful merging of diverged modules. |
| 2 | Run Collectify as a second app under a sub-path of the same domain. | Least merge work, but two codebases, two logins, two translation systems, two deploys forever. |
| 3 | Take Collectify as the base and re-add FrugalDomain's features. | FrugalDomain has far more (learning, tools, forms, appearance…); high loss risk. |

## Design

### Backend (`frugaldomain/api`)
- **Add** `app/Modules/Shopify` (sync engine, webhooks, outbound sync,
  console commands) with its migrations, provider, `config/shopify.php`,
  `.env.example` keys. Drop the diagnostic scripts (`_diag_webhooks2.php`,
  `register_topics_runner.php` stays only if used by a command).
- **Merge** the modules where Collectify is newer — Stores, Catalog, Orders,
  CMS, Billing, User (store team: `users.store_id`, `store_roles`,
  `EnforceStorePermissions` middleware), Marketing, Inventory, Shipping,
  Fulfillment routes — keeping every FrugalDomain addition. New migrations only
  add; nothing drops existing FrugalDomain tables or columns.
- **Analytics:** keep FrugalDomain's Core analytics (tables and routes); do not
  bring Collectify's `Analytics` module (route and name collision). The Shopify
  dashboard uses Core/Shopify stats.
- **Queues:** scheduler in `routes/console.php`: `queue:work
  --stop-when-empty --max-time=50` each minute over the Shopify queues plus
  `shopify:outbound-dispatch-due`. Run with `withoutOverlapping()`. One cron
  line on the host (`schedule:run` every minute).
- **Security fixes from Collectify's own audit:** no `Cache::flush()`; no
  whole-table caches of users/stores (secrets); webhook HMAC verification kept.

### Billing (super admin)
- Plans CRUD (existing Billing module, Collectify's newer `Plan` with
  `max_team_members`, `trial_days`, `billing_interval`).
- New `payment_methods` (name, driver, config encrypted, active, sort) and
  `plan_payment_method` pivot.
- Driver interface (`PaymentGateway`: `checkout(subscription)`,
  `handleCallback(request)`, `cancel(subscription)`), with:
  - `manual` driver: shows the admin's bank/transfer instructions; super
    admin confirms the payment, which activates the subscription.
  - one online driver, chosen by the owner later (Stripe, PayPal, HyperPay,
    Tap, Paymob…) — the slot and config UI exist now.
- Subscriptions: every connected store gets the free plan (existing
  `FreePlanService`); upgrade flow checkout → pending → active / failed;
  feature limits read from the plan (team members, stores) — with the free
  plan generous enough that nothing is blocked today.

### Frontend (`frugaldomain/sydev-front`)
- **Admin sidebar:** a main tab **Shopify** containing: Dashboard (store
  health, sync status, counts, recent sync errors), My store / connect,
  Products, Collections, Orders, Draft orders, Customers, Inventory,
  Fulfillment, Discounts, Content, Sync logs (imports, errors, Shopify updates,
  webhooks), Team (members, roles), and — super admin only — Plans, Payment
  methods, Subscriptions, Stores.
  Existing FrugalDomain partner pages are reused where they already exist;
  Collectify-only pages (MyStore, Team, AccountsManage, Logs, sync overlay) are
  ported. All strings through `useAdminT` (keys added to
  `api/resources/lang/{ar,hi,zh,tr}/admin.php`); colours only from fg tokens
  (`check-hardcoded-colors.mjs` must stay green).
- **Public page `/shopify`:** built from Collectify's home (hero, synced data,
  how it works, features, sync, trust, FAQ, final CTA), rewritten for
  FrugalDomain's voice and identity using the home `kit`, in five languages,
  with "Connect your store" → register/login → connect.
- **Home section:** new registry type `shopify_showcase` (heading, text,
  bullets, button → `/shopify`), added to the default home after the hero/stats,
  editable in the Home sections builder.
- **Promo popup:** component in `SiteLayout`, settings stored as a new
  appearance key `promo` (API + `home-preview`-style injection not needed —
  fetched with the existing `useAppearance`).
- **SEO:** `/shopify` + language variants in `scripts/routes.js` (prerender +
  sitemap), title/description/canonical/hreflang/OG, JSON-LD
  `SoftwareApplication` + `FAQPage`; `llms.txt` entry.

### Caching and hosting load
- Public pages stay prerendered static files (no PHP/DB per visit);
  `/shopify` likewise.
- Public API GETs (appearance, plans list, locale) use `Cache::remember` with
  explicit invalidation on save, plus `Cache-Control: public, max-age`.
- `CACHE_STORE=file` instead of `database` on shared hosting (fewer DB hits;
  Spatie permission cache follows) — owner sets it in `.env`.
- Hashed assets already get long cache headers; HTML no-cache.
- Webhook endpoint: returns 200 fast and queues the work; per-store rate
  limit; sync jobs batched; indexes on the sync tables' lookup columns.

## Left to the builder
- Exact file-level merge decisions inside diverged modules (keep both sides'
  additions; prefer Collectify's Shopify-aware logic).
- Dashboard layout details; landing-page copy wording.
- Which existing partner pages need restyling beyond the sweep already done.
