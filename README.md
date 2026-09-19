# FrugalDomain

[frugaldomain.site](https://frugaldomain.site) — a free SVG icon library, a set
of browser-based conversion tools, and a modular Laravel platform for running
catalog, inventory, content and order operations from one place.

This repository mirrors the production server's `public_html` directory. What
you see at the root is what is served.

```
public_html/
├── index.html, assets/, IconsGalary/, convert/, ar/, …   → frugaldomain.site
├── .htaccess                                             → SPA fallback + redirects
├── api/                                                  → api.frugaldomain.site
└── cdn/                                                  → cdn.frugaldomain.site
```

| Part | Stack | Lives in |
|---|---|---|
| Frontend | React 19, Vite 7, Tailwind 4, React Router 7 | `sydev-front/` (not committed) |
| API | Laravel 12, PHP 8.2, Sanctum | `api/` |
| CDN | Laravel 12 — serves `icons.css` and icon artwork | `cdn/` |

## What the site offers

**Icon library** — 519 icons as both SVG and PNG, searchable and filterable by
category and style. Colour, size and stroke width are editable in the browser
before download, and the file you download is the one you previewed. No account
is required; signing up only adds favourites, download history and custom sets.

**Conversion tools** — images (JPG, PNG, WebP, GIF), audio (MP3, WAV, OGG, AAC)
and documents (DOCX and XLSX to PDF). Each common image conversion also has its
own page under `/convert/<from>-to-<to>` explaining what the conversion does to
quality, file size and transparency.

**Vector draw board** — an in-browser editor for shapes and paths with layers
and node editing, exporting clean SVG.

**Touch simulator** — generates synthetic touch input for testing gesture
handlers, and captures handwriting stroke data (ordered points with timing) for
training and evaluating recognition models.

**Apps gallery** — four applications published on the platform, each with a
documentation page and a live in-browser preview: Kanaf (charity management),
Saydalati (pharmacy and POS), frugal (accounting, inventory and POS) and Manex
(organisation management).

## The API

Seventeen modules under `api/app/Modules/`, each self-contained with its own
routes, controllers, models, migrations and seeders:

```
App  Billing  CMS  Catalog  Core  Fulfillment  Gesture  Icon  Inventory
Locale  Marketing  MobileApp  Orders  Shipping  Stores  Tax  User
```

Public endpoints cover the icon library (`/icons`, `/download-icon/{file}`,
`/get-icon-svg/{file}`), conversion (`/convert-image`), localisation
(`/locale/{lang}`, `/active-languages`), gesture data (`/gestures`) and
analytics (`/track/visit`, `/track/icon`). Everything under `/admin` is behind
Sanctum authentication.

`/documentation` on the site is the integration reference: connecting a store,
Flutter mobile integration, publishing applications and the REST API.

## Working on the frontend

The frontend source is developed locally and deliberately excluded from this
repository — only its compiled output is deployed.

```bash
cd sydev-front
npm install
npm run dev              # local dev server
npm run build            # writes sydev-front/dist
cp -r dist/. ..          # copy the build into the repository root
```

Prerendering needs a local Chrome or Edge. Two things to know about the build:

- **It returns before the prerenderer has finished writing.** The route
  directories appear under `dist/` a few minutes after the command exits, and
  starting a second build in the meantime wipes what was written. Wait for every
  route directory to exist before copying `dist/` anywhere.
- `PRERENDER=0 npm run build` skips the static snapshots entirely, and
  `PUPPETEER_EXECUTABLE_PATH` points it at a browser in an unusual location.

## Routes, prerendering and SEO

`sydev-front/scripts/routes.js` is the single source of truth for crawlable
routes. Every entry there is prerendered into a static HTML snapshot by
`vite.config.js` and written into `sitemap.xml` by `scripts/generate-sitemap.js`,
so a page can never be prerendered but missing from the sitemap, or listed in
the sitemap with no snapshot behind it.

43 routes are currently prerendered — 22 English and 21 Arabic. The Arabic site
lives under `/ar/` with reciprocal `hreflang` annotations, rather than behind a
language toggle: one URL cannot be indexed in two languages, and an
API-fetched translation arrives after the prerenderer has taken its snapshot.
Arabic copy for those pages therefore lives in `sydev-front/src/data/i18n/ar.js`.

Adding a conversion landing page means editing
`sydev-front/src/data/conversions.js` and nothing else — the route, the
snapshot, the sitemap entry and the structured data are all generated from it.

[SEO_NEXT_STEPS.md](SEO_NEXT_STEPS.md) documents what has been done, what is
worth doing next, and four mistakes that silently break prerendering if you are
not expecting them.

## Deployment

[DEPLOYMENT.md](DEPLOYMENT.md) covers it in full: first-time server setup for
both Laravel applications, publishing an app under `/apps/<slug>/`, the icon
seeder, and what to run on subsequent deploys.

Neither PHP application ships with `vendor/`, and `.env` files are created
directly on the server — never committed.

## Repository layout

| Path | |
|---|---|
| `index.html`, `assets/`, `images/` | built frontend |
| `IconsGalary/`, `convert/`, `ar/`, … | prerendered route snapshots |
| `api/` | Laravel API — modules under `app/Modules/` |
| `cdn/` | Laravel CDN — builds `icons.css` from the icon rows |
| `apps/<slug>/` | published applications, each self-contained |
| `json/apps.json` | the apps catalogue, edited from the dashboard |
| `sitemap.xml`, `robots.txt` | generated at build time, restored after prerendering |

## Author

Muhammed Nasser Edden — full-stack developer, Laravel and React.
[Profile](https://frugaldomain.site/about-us/muhammed-nasser-edden) ·
[Contact](https://frugaldomain.site/Contact-Us)
