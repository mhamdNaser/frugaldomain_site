Apps published under frugaldomain.site/apps/<slug>/
===================================================

Each subfolder here is an independent build (a React/Vite bundle, or plain
HTML/CSS/JS). The site's .htaccess excludes /apps/<slug>/... from the SPA
rewrite, so an app keeps its own routing and its own assets.

Publishing an app
-----------------
1. In the dashboard open Apps, fill the entry in, and copy the generated JSON
   into sydev-front/public/json/apps.json.
2. Rebuild the frontend (npm run build) and copy dist into the repository root,
   exactly as DEPLOYMENT.md describes. This publishes the gallery entry, the
   documentation page and the preview link.
3. Build the app itself and upload its output into:
      public_html/apps/<slug>/
   Upload the CONTENTS of the app's dist/ folder, not the folder itself, so
   index.html lands at public_html/apps/<slug>/index.html.

Getting asset paths right
-------------------------
A Vite/React app served from a subfolder must be built with a matching base,
otherwise it requests /assets/... from the site root and renders a blank page:

    // vite.config.js of the app being published
    export default defineConfig({ base: "/apps/<slug>/" })

    # or without touching the config
    npx vite build --base=/apps/<slug>/

Plain HTML apps only need relative paths ("./style.css", not "/style.css").

Client-side routing inside an app
---------------------------------
If the app has its own router, give it its own .htaccess so its deep links
resolve to its own index.html. Place this in public_html/apps/<slug>/:

    <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteBase /apps/<slug>/
      RewriteCond %{REQUEST_FILENAME} -f [OR]
      RewriteCond %{REQUEST_FILENAME} -d
      RewriteRule ^ - [L]
      RewriteRule . /apps/<slug>/index.html [L]
    </IfModule>

Subdomain instead of a subfolder
--------------------------------
Create the subdomain in hPanel, point its document root at
public_html/apps/<slug>, and build the app with base "/" since it then serves
from its own root. Set the app's preview mode to "subdomain" in the dashboard
and put the full URL in the Preview URL field.
