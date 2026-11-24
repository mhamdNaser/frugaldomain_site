# Shopify Store Management Inside FrugalDomain — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move everything Collectify does into FrugalDomain under a "Shopify" feature (admin tab + dashboard, billing with plans/payment methods, public `/shopify` page, home section, promo popup), with strong caching.

**Architecture:** Port Collectify's Laravel `Shopify` module into FrugalDomain's modular API and reconcile the modules both apps share (keeping every FrugalDomain addition); extend Billing with pluggable payment gateways; port Collectify-only dashboard pages into FrugalDomain's React admin (i18n via `useAdminT`, fg tokens); build the public page from Collectify's home content using the existing home `kit`.

**Tech Stack:** Laravel 12 / PHP 8.2 / Pest; React 19 + Vite 7 + Tailwind 4; MySQL (XAMPP locally).

**Spec:** `docs/superpowers/specs/2026-11-24-shopify-integration-design.md`

## Global Constraints

- Source A (read-only, will be deleted): backend `C:/Users/nagis/Desktop/shopify/frugaldomain`, frontend `C:/Users/nagis/Desktop/shopify/sydev-front`. Never modify A.
- Target B: API + site = worktree `C:/Users/nagis/Desktop/frugaldomain/.claude/worktrees/website-ui-colors-styling-8709e0` (branch `claude/shopify-integration`, created from `main` before Task 1); frontend source = `C:/Users/nagis/Desktop/frugaldomain/sydev-front` (branch `claude/shopify-integration`, created from `main` before Task 6). Never push; never deploy; the owner does both.
- Never copy A's `.env`; never print env values; never touch any remote DB or the live API. Tests use the local MySQL test DB from `api/phpunit.xml` (XAMPP on 127.0.0.1:3306).
- Nothing may drop or rename an existing FrugalDomain table, column, route or module. New migrations only add.
- No new composer or npm dependencies.
- The word "Collectify" and the domain `collectify.sbs` must not appear anywhere in B's source, lang files, docs or built output (Task 10 enforces).
- Feature name in UI: "Shopify" (admin tab), public URL `/shopify` plus `/ar/shopify`, `/hi/shopify`, `/zh/shopify`, `/tr/shopify`.
- Keep FrugalDomain's Core analytics; do not port A's `Analytics` module or its routes.
- Admin UI strings via `useAdminT` with keys in `api/resources/lang/{ar,hi,zh,tr}/admin.php`; site strings via `useLocale` dicts for en/ar/hi/zh/tr. Colours only from fg tokens; `node scripts/check-hardcoded-colors.mjs` must exit 0.
- Promo popup: desktop corner card, mobile bottom bar, never full-screen; shows after 4 s on first visit; dismiss → hidden 7 days; click → never again; hidden on `/shopify*`, `/admin*`, `/account*`, drawing board, whiteboard/simulators; text in 5 languages + on/off from Admin → Appearance.
- Billing: only active plan is free; every newly connected store gets the free plan; payment methods are pluggable drivers; ship `manual` driver now; no online gateway integration until the owner names one.
- Known pre-existing test failure to ignore: `DesktopSoftwareApiTest` "published software image…".

## Review Focus

1. **Existing FrugalDomain partner/admin flows after the User/Stores merge** — a partner who registered via FrugalDomain's Account module must still log in and see their store; covered by porting A's `AccessControlTest` plus a new B-flow test in Task 2.
2. **Webhook for an unknown or deleted store / bad HMAC** — must 401/404 quickly without queuing work; test in Task 3.
3. **Shopify tokens or password hashes reaching the cache** — must never be cached; test in Task 3.
4. **Manual payment confirmed twice, or a subscription for a plan whose payment method was removed** — idempotent activation; plan without active method cannot be checked out; tests in Task 4.
5. **Popup on excluded routes / after dismissal / language without text** — hidden on excluded routes, respects 7-day dismissal, falls back to en; node check in Task 9.

---

### Task 1: Shopify module and shared-module reconciliation (backend)

**Files:**
- Create: `api/app/Modules/Shopify/**` (copied from A, minus `Webhooks/_diag_webhooks2.php` and any `*_diag*`/one-off runner not referenced by a command or provider), `api/config/shopify.php`, `api/tests/Feature/{ShopifyAutoSyncTest,ShopifyFileUploadTest,ShopifyWebhookVerifierTest,StoreFullImportResetTest,ProductImportPaginationTest,ApiListEndpointsTest}.php` (ported from A).
- Modify: `api/bootstrap/providers.php` (+`ShopifyServiceProvider`), `api/routes/console.php` (A's queue-drain + `shopify:outbound-dispatch-due` schedule, both `->withoutOverlapping()`), `api/.env.example` (+`SHOPIFY_WEBHOOK_ENDPOINT`, `SHOPIFY_WEBHOOK_SECRET`), and merge from A into B: `app/Modules/{Stores,Catalog,Orders,CMS,Inventory,Marketing,Shipping,Fulfillment}/**` and A's Billing models/migrations for `plans` (`max_team_members`, `trial_days`, `billing_interval`) and `Services/FreePlanService`.

**Interfaces:**
- Produces: all A Shopify routes unchanged (e.g. `POST /api/shopify/webhooks`, `PUT /api/admin/shopify/webhook-secret`, store connect/import routes); artisan `shopify:outbound-dispatch-due`; `App\Modules\Billing\Services\FreePlanService`.

- [ ] **Step 1:** `git checkout -b claude/shopify-integration` in the worktree (from `main`).
- [ ] **Step 2:** Copy the ported A tests; run `cd api && php artisan test --filter='Shopify|StoreFullImport|ProductImport|ApiListEndpoints'` → FAIL (classes missing).
- [ ] **Step 3:** Copy the Shopify module + config + provider registration + scheduler + env keys.
- [ ] **Step 4:** Merge shared modules file by file: for each file differing between A and B, keep B's additions and take A's Shopify-aware logic; record every non-trivial decision in the report. Copy A-only migrations; skip any A migration whose table/column already exists in B (guard with `Schema::hasColumn`/`hasTable` rather than editing old migrations).
- [ ] **Step 5:** `php artisan migrate:fresh` on the test DB succeeds; the filtered tests PASS; full `php artisan test` shows no new failures.
- [ ] **Step 6:** Commit `feat(api): Shopify module and Collectify module updates`.

### Task 2: Store team and roles (User module)

**Files:**
- Modify/merge: `api/app/Modules/User/**` (A's `Team/EnforceStorePermissions`, `StorePermissions`, `StoreRole`, `TeamMemberController`, `TeamRoleController`, `Mail/VerificationCodeMail`, `users.store_id` + `store_roles` + `store_role_user` migrations; keep B's `AdminResetPasswordRequest` and every B auth path), `api/bootstrap/app.php` (append `EnforceStorePermissions` to `api` group).
- Do NOT bring `CollectifyDemoSeeder`.
- Test: port A's `StoreTeamTest`, `MyStoreTest`, `AccessControlTest`; new `api/tests/Feature/PartnerAccountFlowTest.php`.

**Interfaces:**
- Produces: team routes as in A (`/api/admin/team/members`, `/api/admin/team/roles` …), `User::store()` relation semantics unchanged for owners.

- [ ] **Step 1:** Write `PartnerAccountFlowTest`: `it lets a partner registered through the Account module log in and read their store` (register via B's existing register endpoint/factory, give `partner` role, create store with `owner_id`, `GET` the partner store endpoint → 200 with that store id); `it keeps admin-only routes forbidden to store staff` (staff user with `store_id` → admin plans endpoint → 403).
- [ ] **Step 2:** Run → FAIL where team classes are missing.
- [ ] **Step 3:** Merge User module + middleware; run `RolePermissionSeeder` in tests where A's tests expect it.
- [ ] **Step 4:** New + ported tests PASS; full suite no new failures.
- [ ] **Step 5:** Commit `feat(api): store team roles and permissions`.

### Task 3: Hardening and caching for shared hosting

**Files:**
- Modify: wherever A brought `Cache::flush()` or whole-table caches of users/stores (`all_Users`, `all_Stores`, Gesture) → remove/replace with keyed caches of public, non-secret data only; `Shopify/Controllers/ShopifyWebhookController` (verify HMAC, then dispatch a job and return 200 immediately; per-store rate limit via `RateLimiter` 120/min); public GET endpoints for plans and locale → `Cache::remember` with explicit `Cache::forget` on write; add indexes migration for sync lookup columns (`webhook_logs.store_id,created_at`, `sync_jobs.store_id,status`, `shopify_outbound_syncs.store_id,status,available_at` — only where missing).
- Test: `api/tests/Feature/ShopifyHardeningTest.php`.

- [ ] **Step 1:** Tests: `webhook with bad hmac returns 401 and queues nothing` (`Queue::fake()`, assert nothing pushed); `webhook for unknown shop returns 404 and queues nothing`; `valid webhook returns 200 and pushes one job`; `no cache entry contains a shopify access token or password hash` (seed a store with token `shpat_TESTTOKEN` and a user, hit store/user list endpoints as admin, then scan the cache table rows' values for `shpat_TESTTOKEN` and `$2y$` → none); `plans list is cached and refreshed after update` (GET twice → 1 query for plans; PUT plan; GET shows new name).
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** PASS; full suite no new failures. Add to `api/.env.example` a comment recommending `CACHE_STORE=file` for shared hosting.
- [ ] **Step 5:** Commit `fix(api): webhook fast-ack, safe caching, sync indexes`.

### Task 4: Billing — payment methods, gateway drivers, subscription flow

**Files:**
- Create: `api/app/Modules/Billing/Gateways/{PaymentGateway.php (interface), ManualGateway.php, GatewayRegistry.php}`, `Models/PaymentMethod.php`, migrations `payment_methods` (id, name, driver string, config encrypted json nullable, instructions json (per-language), is_active bool, sort int) and `plan_payment_method` (plan_id, payment_method_id), `Controllers/PaymentMethodController.php`, `Controllers/CheckoutController.php`, requests; `api/tests/Feature/BillingGatewayTest.php`.
- Modify: `Billing/Routes/api.php`, `Plan` (belongsToMany paymentMethods), `Subscription` (status `pending|active|failed|canceled`, `payment_method_id`, `reference`).

**Interfaces:**
- `interface PaymentGateway { public function key(): string; public function checkout(Subscription $s): array /* {type: 'redirect'|'instructions', url?: string, instructions?: array} */; public function confirm(Subscription $s, array $payload = []): void; public function cancel(Subscription $s): void; }`
- `GatewayRegistry::get(string $driver): PaymentGateway`; `GatewayRegistry::drivers(): array` (keys + config field schema for the admin form). Only `manual` registered now.
- Routes: admin (`role:admin`): CRUD `payment-methods`, `PUT plans/{id}/payment-methods` (sync ids), `POST subscriptions/{id}/confirm` (manual), `GET billing/drivers`. Partner: `GET billing/plans` (active plans + their active methods, cached), `POST billing/checkout` `{plan_id, payment_method_id}` → returns gateway payload.

- [ ] **Step 1:** Tests: `admin creates a manual payment method and links it to a plan`; `partner checkout with manual method creates a pending subscription and returns instructions`; `admin confirming twice activates once and is idempotent`; `checkout fails 422 when the method is not linked to the plan or inactive`; `free plan checkout activates immediately without a payment method`; `newly connected store gets the free plan` (existing FreePlanService); `non-admin cannot manage payment methods` (403).
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement; seed/ensure one active free plan exists (migration data step guarded by `exists()`).
- [ ] **Step 4:** PASS; full suite no new failures.
- [ ] **Step 5:** Commit `feat(api): billing payment methods and manual gateway`.

### Task 5: Promo popup setting (API)

**Files:**
- Modify: `api/app/Modules/Appearance/**` — new `site_settings` key `promo`: `{enabled: bool, link: "/shopify", i18n: {en:{title,text,button}, ar:…, hi:…, zh:…, tr:…}}`; included in `GET /api/site/appearance` as `promo`; `PUT /api/admin/appearance/promo` (`role:admin`, same text sanitising and link rule as sections). Defaults (enabled true, en + ar + hi + zh + tr copy for "Manage your Shopify store from one place — free") in `AppearanceDefaults::promo()`.
- Test: extend `AppearanceApiTest`.

- [ ] **Step 1:** Tests: GET includes default `promo.enabled === true` and `promo.i18n.ar.title` non-empty; admin PUT saves; `javascript:` link → 422; HTML stripped; non-admin 403.
- [ ] **Step 2:** FAIL → **Step 3:** implement → **Step 4:** PASS → **Step 5:** commit `feat(api): promo popup setting`.

### Task 6: Admin "Shopify" tab, dashboard and ported pages (frontend)

**Files (sydev-front, branch `claude/shopify-integration` from `main`):**
- Modify: `public/json/adminList.json` — one top-level group "Shopify" containing: Dashboard `/admin/shopify`, My store `/admin/my-store`, Products, Collections, Orders, Draft orders, Customers, Inventory, Fulfillments, Discounts, Content (existing B routes reused), Sync logs (`/admin/logs/{imports,import-errors,shopify-updates,webhooks}`), Team (`/admin/team/{members,roles}`), and for admin only: Stores, Plans, Payment methods, Subscriptions. Move existing commerce entries under it (don't duplicate). `src/router.jsx` lazy routes.
- Create/port from A: `src/page/partnerDahboard/{MyStore,Team,Logs,AccountsManage}/**`, `src/Components/ShopifySyncOverlay.jsx`, hooks `useMyStore`, `useTeam`, `useAccountsManage`, `useUserStoreStats`, utils `storeAccess.js`, `dashboardAccess.js`, `imageFallback.js`; new `src/page/partnerDahboard/Shopify/ShopifyDashboard.jsx` (cards: store connection status + reconnect, product/order/customer counts, last sync time, queue backlog, recent sync errors list, quick links) using existing dashboard stats endpoints from A's Core/Shopify.
- Convert every ported file: `useTranslation`/`t(key, fallback)` → `useAdminT` with English text keys; A's class tokens → fg tokens; remove brand strings.
- Modify: `api/resources/lang/{ar,hi,zh,tr}/admin.php` (worktree) with all new keys.

- [ ] **Step 1:** `git checkout -b claude/shopify-integration` in sydev-front.
- [ ] **Step 2:** Port + wire; `node scripts/check-admin-i18n.mjs` against the worktree lang files → 0 missing; `node scripts/check-hardcoded-colors.mjs` exit 0; `npm run lint` no new errors in touched files.
- [ ] **Step 3:** Browser check with local API (Task 11 setup may be started early): sidebar shows one "Shopify" group; dashboard renders with an empty store ("connect your store" state) without errors; Team and Logs pages load.
- [ ] **Step 4:** Commits (sydev-front `feat(admin): Shopify tab, dashboard and store pages`; worktree `feat(i18n): Shopify admin strings`).

### Task 7: Billing admin and partner upgrade pages (frontend)

**Files:** `src/page/adminDashboard/Billing/{PaymentMethods.jsx, PaymentMethodForm.jsx}` (driver select from `GET billing/drivers`, config fields from the driver schema, per-language instructions), extend existing Plans page (`/admin/stores/plans`) with a "Payment methods" multi-select (`PUT plans/{id}/payment-methods`) and the new plan fields; Subscriptions page: status badge + "Confirm payment" for pending manual ones; partner `src/page/partnerDahboard/Billing/Upgrade.jsx` (plans grid from `GET billing/plans`, current plan, checkout → show manual instructions or redirect). Hooks in `src/Apihooks/useBilling.js`. Admin strings as in Task 6.

- [ ] **Step 1:** Implement; checks as Task 6 Step 2.
- [ ] **Step 2:** Browser: create manual method → link to a test paid plan → as partner choose it → pending with instructions → as admin confirm → active.
- [ ] **Step 3:** Commit `feat(admin): billing payment methods and upgrade flow`.

### Task 8: Public `/shopify` page, home section, SEO

**Files:**
- Create: `src/page/site/shopify/ShopifyPage.jsx` (+ section components reusing `src/page/site/home/sections/kit.jsx`): hero, synced data, how it works, features, sync engine, trust/security, FAQ, final CTA — content adapted from A's `src/page/site/home/*` (rewritten to FrugalDomain voice, no brand name), copy in `src/i18n/{en,ar,hi,zh,tr}/shopify.js` (create the dict pattern used by home); `src/page/site/seo/SeoShopify.jsx` (title, description, canonical, hreflang for 5 langs, OG, JSON-LD `SoftwareApplication` + `FAQPage`).
- Modify: `src/router.jsx` (`/shopify` + localized), `scripts/routes.js` (prerender + sitemap for the 5 paths), `public/llms.txt` (entry), `src/page/site/home/sections/sectionSchemas.js` + `registry.js` (+type `shopify_showcase`: fields heading, text, bullets list, button_label, link default `/shopify`; defaults in 5 languages; inserted into `DEFAULT_SECTIONS` right after `stats_bar`), and the API's section type allow-list `AppearanceDefaults::SECTION_TYPES` (+`shopify_showcase`) with a Pest assert; `scripts/appearance-lib.php` crawlable text handling if the new type needs it.
- Test: extend `scripts/check-home-sections.mjs` (new type present, 5 languages, in defaults after stats_bar).

- [ ] **Step 1:** Extend check → FAIL; implement; PASS. Pest assert for the type → PASS.
- [ ] **Step 2:** `npm run build` with prerender: `dist/shopify/index.html` and `dist/ar/shopify/index.html` exist with the right `<title>`, canonical and hreflang; sitemap contains the 5 URLs.
- [ ] **Step 3:** Commits (sydev-front `feat: public Shopify page, home section and SEO`; worktree `feat(api): shopify_showcase section type`).

### Task 9: Promo popup (frontend) + admin controls

**Files:** `src/layout/components/site/PromoPopup.jsx` (rendered in `SiteLayout`), `src/layout/components/site/promoRules.js` (pure: `shouldShowPromo({path, now, storage, promo}) → boolean`, `PROMO_EXCLUDED` prefixes), admin controls in `src/page/adminDashboard/Appearance/PromoSettings.jsx` (route `/admin/appearance/promo`, sidebar under Appearance), hook in `useAdminAppearance` (`savePromo`); `scripts/check-promo.mjs`.

- [ ] **Step 1:** `check-promo.mjs` asserts: hidden on `/shopify`, `/ar/shopify`, `/admin/x`, `/account`, drawing board and whiteboard/simulator paths (read exact paths from `scripts/routes.js`/router); shown on `/` first visit; hidden within 7 days of dismissal, shown after; never after click; hidden when `promo.enabled === false`; language without text falls back to en. → FAIL.
- [ ] **Step 2:** Implement (localStorage keys `fg_promo_dismissed_at`, `fg_promo_clicked`, wrapped in try/catch; 4 s delay; desktop ≥768 px corner card, mobile bottom bar; accessible: `role="dialog"` non-modal with labelled close button, focus not stolen). PASS.
- [ ] **Step 3:** Browser: appears after 4 s on `/`, not on `/shopify`; close persists; mobile layout is a bottom bar; admin edits text in ar → shows in `/ar`.
- [ ] **Step 4:** Commit `feat: promo popup for the Shopify feature`.

### Task 10: Brand removal and domain sweep

**Files:** `sydev-front/scripts/check-no-collectify.mjs` (fails on `/collectify/i` in `src`, `public`, `index.html`, `scripts` excluding itself), and the same grep over the worktree (`api/app`, `api/resources`, `api/database`, `docs`, root PHP, built output after Task 11).
- [ ] **Step 1:** Run → FAIL listing hits; replace with FrugalDomain wording or remove; PASS in both repos.
- [ ] **Step 2:** Commit in each repo `chore: remove Collectify branding`.

### Task 11: Build, deploy into worktree, end-to-end check

- [ ] **Step 1:** Local stack as in the previous project's Task 12 (local DB `frugaldomain_e2e`, API via `php -S`, XAMPP Apache with its own config, `FG_API_BASE`), `migrate:fresh --seed` on the e2e DB only.
- [ ] **Step 2:** E2E (report PASS/FAIL): admin sidebar Shopify group + dashboard; partner without a store sees connect state; billing manual flow end to end; webhook endpoint with bad HMAC → 401 fast; `/shopify` in 5 languages with SEO tags in view-source; home shows `shopify_showcase`; popup rules; no "collectify" in built output; all `check-*` scripts and `php artisan test` (no new failures). A real Shopify OAuth/connection cannot be exercised locally (needs a public URL) — state so.
- [ ] **Step 3:** Production build deployed into the worktree root exactly like before (don't clobber root PHP, `.htaccess`, `api/`); commit `build: Shopify feature`.
- [ ] **Step 4:** Write `docs/shopify-go-live.md` for the owner: cron line, `.env` keys to add (names only) incl. `CACHE_STORE=file`, run migrations, reconnect the test store, re-register webhooks (command name), 301 rules to put on `collectify.sbs`, Shopify Partner dashboard URLs to update (app URL, redirect URLs, webhook URL), then delete the `shopify` folder.
