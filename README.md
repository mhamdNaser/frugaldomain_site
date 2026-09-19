<div align="center">

<img src="https://frugaldomain.site/images/logo.svg" width="96" alt="FrugalDomain">

# FrugalDomain

**A free SVG icon library, browser-based conversion tools, and a modular Laravel platform for catalog, inventory, content and order operations.**

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)](https://php.net)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)](https://react.dev)
[![Vite](https://img.shields.io/badge/Vite-7-646CFF?logo=vite&logoColor=white)](https://vite.dev)
[![Tailwind](https://img.shields.io/badge/Tailwind-4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)

[![Icons](https://img.shields.io/badge/icons-519-38BDF8)](https://frugaldomain.site/IconsGalary)
[![Routes](https://img.shields.io/badge/prerendered%20routes-43-38BDF8)](https://frugaldomain.site/sitemap.xml)
[![Languages](https://img.shields.io/badge/languages-EN%20%7C%20AR-38BDF8)](https://frugaldomain.site/ar)

**[Live site](https://frugaldomain.site)** ·
[Icon library](https://frugaldomain.site/IconsGalary) ·
[Documentation](https://frugaldomain.site/documentation)

**English** · [العربية](README.ar.md)

<img src="docs/screenshots/home.png" width="860" alt="The FrugalDomain home page">

</div>

---

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
|:--|:--|:--|
| **Frontend** | React 19 · Vite 7 · Tailwind 4 · React Router 7 | `sydev-front/` *(not committed)* |
| **API** | Laravel 12 · PHP 8.2 · Sanctum | `api/` |
| **CDN** | Laravel 12 — serves `icons.css` and icon artwork | `cdn/` |

---

## What the site offers

### 🎨 Icon library

519 icons as both SVG and PNG, searchable and filterable by category and style.
Colour, size and stroke width are editable in the browser before download, and
the file you download is the one you previewed. No account required — signing
up only adds favourites, download history and custom sets.

<img src="docs/screenshots/icons.png" width="860" alt="The icon gallery with search, category and style filters">

### 🔄 Conversion tools

| Tool | Formats |
|:--|:--|
| [Images](https://frugaldomain.site/ImageConvert) | JPG · PNG · WebP · GIF |
| [Audio](https://frugaldomain.site/AudioConvert) | MP3 · WAV · OGG · AAC |
| [Documents](https://frugaldomain.site/FileConvert) | DOCX → PDF · XLSX → PDF |

Each common image conversion also has its own page under
`/convert/<from>-to-<to>` — the converter with the target format preselected,
followed by copy explaining what that conversion does to quality, file size and
transparency, and an FAQ that is mirrored as structured data.

<img src="docs/screenshots/landing.png" width="860" alt="The JPG to PNG conversion page: converter, explanation and related conversions">

### ✏️ Vector draw board

An in-browser editor for shapes and paths with layers, node editing and clean
SVG export.

<img src="docs/screenshots/drawboard.png" width="860" alt="The vector draw board with its shape library and canvas">

### 👆 Touch simulator

Generates synthetic touch input for testing gesture handlers, and captures
handwriting stroke data — ordered points with timing — for training and
evaluating recognition models.

### 📦 Apps gallery

Four applications published on the platform, each with a documentation page and
a live in-browser preview:

| App | What it does |
|:--|:--|
| **Kanaf** | Charity management — donations, beneficiaries, projects, restricted-fund accounting |
| **Saydalati** | Multi-branch pharmacy — dispensing, expiry-aware stock, accounting |
| **frugal** | Accounting, inventory and POS, running offline on a local network |
| **Manex** | Organisation management — employees, branches, circulars, file archive |

---

## The API

Seventeen self-contained modules under `api/app/Modules/`, each with its own
routes, controllers, models, migrations and seeders:

```
App        Billing   CMS        Catalog   Core      Fulfillment
Gesture    Icon      Inventory  Locale    Marketing MobileApp
Orders     Shipping  Stores     Tax       User
```

<details>
<summary><b>Public endpoints</b></summary>

<br>

| Area | Endpoints |
|:--|:--|
| Icons | `/icons` · `/download-icon/{file}` · `/get-icon-svg/{file}` · `/get-icon-jsx/{file}` |
| Conversion | `/convert-image` · `/download-image/{file}` |
| Localisation | `/locale/{lang}` · `/active-languages` |
| Gestures | `/gestures` · `/gestures/count/{character}` |
| Analytics | `/track/visit` · `/track/icon` |
| Contact | `/site/contact-us` |

Everything under `/admin` is behind Sanctum authentication.

</details>

[`/documentation`](https://frugaldomain.site/documentation) on the site is the
integration reference: connecting a store, Flutter mobile integration,
publishing applications and the REST API.

---

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

> [!IMPORTANT]
> **`npm run build` returns before the prerenderer has finished writing.** The
> route directories appear under `dist/` a while after the command exits.
> Copying `dist/` too early ships HTML that points at a JS bundle from a
> different build, and every page fails to boot — while still looking fine in a
> directory listing. Wait until every route directory exists, and verify the
> asset hashes match, before copying anything.

Prerendering needs a local Chrome or Edge, and no other Chrome instance running
— the prerenderer cannot terminate its own child processes otherwise, and
silently writes nothing. `PRERENDER=0 npm run build` skips the snapshots, and
`PUPPETEER_EXECUTABLE_PATH` points it at a browser in an unusual location.

---

## Routes, prerendering and SEO

`sydev-front/scripts/routes.js` is the single source of truth for crawlable
routes. Every entry there is prerendered into a static HTML snapshot by
`vite.config.js` and written into `sitemap.xml` by `scripts/generate-sitemap.js`,
so a page can never be prerendered but missing from the sitemap, or listed in
the sitemap with no snapshot behind it.

**43 routes** are currently prerendered — 22 English and 21 Arabic.

<details>
<summary><b>Why the Arabic site lives under <code>/ar/</code></b></summary>

<br>

Not behind a language toggle, for two reasons: one URL cannot be indexed in two
languages and `hreflang` needs a distinct address per language; and an
API-fetched translation arrives *after* the prerenderer has taken its snapshot,
so a toggle-driven translation would never reach a crawler at all.

Arabic copy for those pages therefore lives in
`sydev-front/src/data/i18n/ar.js`, inside the bundle. Every page carries
reciprocal `hreflang` annotations — Google ignores a one-sided one entirely.

</details>

Adding a conversion landing page means editing
`sydev-front/src/data/conversions.js` and nothing else — the route, the
snapshot, the sitemap entry and the structured data are all generated from it.

📄 **[SEO_NEXT_STEPS.md](SEO_NEXT_STEPS.md)** — what has been done, what is
worth doing next, and four mistakes that silently break prerendering.

---

## Deployment

📄 **[DEPLOYMENT.md](DEPLOYMENT.md)** — first-time server setup for both Laravel
applications, publishing an app under `/apps/<slug>/`, the icon seeder, and what
to run on subsequent deploys.

Neither PHP application ships with `vendor/`, and `.env` files are created
directly on the server — never committed.

---

## Repository layout

| Path | |
|:--|:--|
| `index.html`, `assets/`, `images/` | built frontend |
| `IconsGalary/`, `convert/`, `ar/`, … | prerendered route snapshots |
| `api/` | Laravel API — modules under `app/Modules/` |
| `cdn/` | Laravel CDN — builds `icons.css` from the icon rows |
| `apps/<slug>/` | published applications, each self-contained |
| `json/apps.json` | the apps catalogue, edited from the dashboard |
| `sitemap.xml`, `robots.txt` | generated at build time, restored after prerendering |
| `docs/screenshots/` | the images in this README |

---

<div align="center">

**Muhammed Nasser Edden** — Full-Stack Developer · Laravel &amp; React

[Profile](https://frugaldomain.site/about-us/muhammed-nasser-edden) ·
[Contact](https://frugaldomain.site/Contact-Us) ·
[GitHub](https://github.com/mhamdNaser) ·
[LinkedIn](https://www.linkedin.com/in/muhammed-naser-edden)

<sub>© 2026 FrugalDomain. All rights reserved.</sub>

</div>
