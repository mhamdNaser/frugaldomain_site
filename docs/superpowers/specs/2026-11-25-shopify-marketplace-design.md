# Shopify marketplace (themes and apps for sale) — design

## Goal
Sell Shopify themes and apps on frugaldomain.site. The super admin adds each item
from the dashboard; a signed-in visitor pays with the site's payment methods and
then downloads the zip. Theme files can come from a private GitHub repo; buyers
never see the repo.

## Decisions (from the owner)
- A site account is required to buy. Purchases live under Account → "My purchases".
- Item content in English and Arabic (hi / zh / tr show the English).
- One logo per item (no screenshot gallery).
- Updates: a buyer always downloads the latest version. The latest version is
  either the uploaded zip, or the newest GitHub Release of a private repo.
- Same payment methods as plans (PayPal, Stripe, Sham Cash, manual); every
  active method is offered for marketplace items.

## Data
`market_items`: type (theme | app), slug, name/tagline/description/features/seo_title/seo_description
for en and ar, price, logo_path (public), preview_url, preview_password (public on the page),
source (upload | github), file_path (private), github_repo (owner/name), github_token (encrypted, hidden),
version, release_notes, released_at, status (draft | published), sort.

`market_purchases`: user_id, market_item_id, amount, payment_method_id, status (pending | paid | canceled),
reference, receipt_path, proof_submitted_at, paid_at. A user has at most one paid purchase per item.

## Payments
The gateways today take a `Subscription`. They change to take a small `Payable`
interface (id, amount, title, return path). `Subscription` and `MarketPurchase`
both implement it, so PayPal / Stripe / Sham Cash / manual work for both with no
duplicated gateway code. Online gateways return to `/shopify/purchase/return`
(marketplace) or `/admin/billing/upgrade` (plans); proof upload and admin
confirmation work the same way as for plans.

## Files and GitHub releases
- Upload source: the zip is stored privately (`storage/app/private/market/...`).
- GitHub source: the server keeps a read-only fine-grained token. "Check for updates"
  (button + hourly scheduler) reads `GET /repos/{repo}/releases/latest`, downloads the
  first `.zip` asset once per tag into private storage, and records tag, notes and date.
  A Release asset is used (not the source zipball) because Shopify wants theme folders
  at the zip root; a GitHub Action builds that zip on every tag (docs give the workflow).
- Download: `GET /api/market/items/{slug}/download` — only a buyer with a paid purchase
  (or the admin) gets the stream; nobody gets the repo URL or token.

## Pages
- Header: new "Shopify" menu → Store management (/shopify), Themes (/shopify/themes), Apps (/shopify/apps).
- Listing pages (prerendered, SEO): cards with logo, name, tagline, price.
- Item page `/shopify/themes/:slug` and `/shopify/apps/:slug`: logo, name, price, Buy / Download,
  preview link with its password (copy button), features, README description (markdown),
  version and release notes; title/description/JSON-LD Product from the item's SEO fields.
- Account → My purchases: items, status, download button, pending proof form.
- Admin → Shopify → Marketplace: items CRUD (zip, README, logo, GitHub repo + token, check updates),
  Purchases (confirm payment, receipt).

## Footer
Too many columns already (one per tool group). New layout: four main columns
(Products, Shopify, Apps, Company) and one compact "All tools" band below with
each tool group as a short inline list, so every tool keeps a crawlable footer
link without a wall of columns. On phones the columns fold into accordions.

## Out of scope
Licence keys, coupons, refunds, VAT, item reviews, dynamic sitemap entries for items
(listings are prerendered; item pages are reachable from them).
