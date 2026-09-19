# Frugaldomain — deployment repository

This repository mirrors the server's `public_html` directory exactly:

```
public_html/
├── index.html, assets/, IconsGalary/, …   → frugaldomain.site      (built React SPA)
├── .htaccess                              → SPA fallback rewrite
├── api/                                   → api.frugaldomain.site  → api/public
└── cdn/                                   → cdn.frugaldomain.site  → cdn/public
```

The subdomain document roots are already configured in hPanel:

| Subdomain               | Document root                          |
|-------------------------|----------------------------------------|
| `api.frugaldomain.site` | `…/public_html/api/public`             |
| `cdn.frugaldomain.site` | `…/public_html/cdn/public`             |

## The frontend source is not in this repository

`sydev-front/` is developed locally and deliberately excluded — only its compiled
output is deployed. To publish a frontend change:

```bash
cd sydev-front
npm install
npm run build            # writes sydev-front/dist
cp -r dist/. ..          # copy the build into the repository root
```

`npm run build` reads `sydev-front/.env.production`, which points the bundle at
`https://api.frugaldomain.site`. Prerendering needs a local Chrome or Edge; set
`PUPPETEER_EXECUTABLE_PATH` if it is installed somewhere unusual, or skip the
SEO snapshots entirely with `PRERENDER=0 npm run build`.

## Publishing an app under /apps/<slug>/

The site can host self-contained applications alongside the SPA. Each one gets
a gallery card, a documentation page and a live preview at
`frugaldomain.site/apps/<slug>`.

The catalogue is a static file, `sydev-front/public/json/apps.json`. The
dashboard page **Apps** (`/admin/apps`) is an editor for it: fill in the entry,
choose the type and the preview mode, then copy or download the generated JSON
over that file and rebuild the frontend.

```
public_html/
├── apps/
│   ├── index.html          → the gallery (prerendered, part of the SPA build)
│   └── my-app/             → an uploaded application
│       ├── index.html
│       └── assets/
```

Two steps publish an app:

1. **The catalogue entry** — edit `apps.json` from the dashboard, then rebuild
   and copy `dist` into the repository root as described above.
2. **The app itself** — build it and upload the contents of its `dist/` into
   `public_html/apps/<slug>/`.

A Vite/React app served from a subfolder must be built with a matching base,
otherwise it asks for `/assets/...` at the site root and renders blank:

```bash
npx vite build --base=/apps/my-app/
```

Plain HTML apps only need relative asset paths (`./style.css`). If the app has
its own client-side router, give it its own `.htaccess` inside its folder with a
fallback to its own `index.html`; `public/apps/README.txt` carries a template.

The site `.htaccess` excludes `/apps/<slug>/...` from the SPA rewrite
(`RewriteRule ^apps/.+ - [L]`), so an app keeps its own routing while `/apps`
itself still resolves to the gallery. Until a folder is uploaded, that URL falls
back to the site and the app page says the app is not published yet rather than
embedding the site in its own preview frame.

To serve an app from a subdomain instead, create it in hPanel, point its
document root at `public_html/apps/<slug>`, build with base `/`, and set the
entry's preview mode to *subdomain* with the full URL.

## First-time server setup

Both PHP applications ship without `vendor/`, so install dependencies over SSH:

```bash
cd ~/domains/frugaldomain.site/public_html/api
cp .env.example .env          # then fill in DB_DATABASE / DB_USERNAME / DB_PASSWORD
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force   # includes the full icon library
php artisan config:cache && php artisan route:cache

cd ../cdn
cp .env.example .env          # same database credentials as the API
composer install --no-dev --optimize-autoloader
php artisan key:generate
```

Writable directories:

```bash
chmod -R 775 api/storage api/bootstrap/cache cdn/storage cdn/bootstrap/cache
```

## Icons

The artwork (519 SVG + 519 PNG) lives in `api/public/icons/` and is committed,
so it deploys with the repository. `IconSeeder` only records the metadata rows
and is safe to re-run — it matches on the icon title and updates in place:

```bash
php artisan db:seed --class="App\Modules\Icon\database\seeders\IconSeeder" --force
```

The CDN builds `https://cdn.frugaldomain.site/icons.css` from those same rows and
reads the artwork out of the API's public directory. If the two applications are
ever deployed somewhere other than side by side, point `ICONS_PUBLIC_PATH` in
`cdn/.env` at the API's `public` folder.

## Subsequent deploys

Pull the repository, then only when PHP dependencies changed:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache
```
