# Site appearance settings + home section builder — design

Date: 2026-11-23 · Status: draft for approval

## Intent

The owner wants a visual identity that pulls visitors in, and wants to run the
site's look and its home page from the admin panel without touching code:

1. A redesigned home page (the most important page).
2. An **Appearance** admin tab: logo, colours, fonts — applied site-wide.
3. A **Home sections** sub-tab: reorder, hide, edit and add home sections.

The site serves three audiences with equal weight: **designers, developers and
learners**. Everything is free; there is no booking or paid course flow.

## Goals

- First screen speaks to all three audiences and routes each to their tools.
- Identity "Midnight & Champagne" (chosen in the visual companion, option 1):
  deep navy `#0a0f1c` with a soft top glow, a single champagne-gold accent
  `#c9a96e` used sparingly (one word in the headline, icons, "Start" links),
  IBM Plex Sans Arabic, line icons (no emoji), generous whitespace.
- Dark is the default; a light variant (ivory canvas, darker gold for contrast)
  stays available through the existing theme toggle.
- Admin changes show to visitors immediately after saving, with no flash of the
  old colours and with the new home content visible to search engines.

## Non-goals

- A free-form drag-and-drop page builder (rejected: much bigger, and it lets
  sections break the identity).
- Faceted filters and distraction-free reading mode (they belong to the gallery
  and lesson pages — a later phase).
- Booking, consultations, payments.
- Theming any page other than through the shared tokens (no per-page themes).

## Anti-goals (failure even if it "works")

- Visitors see a flash of the default colours before the saved theme applies.
- Search engines see stale home content after the admin edits it.
- A section added from the library looks off-brand next to the others.
- Text that is not readable in one of the two modes (contrast below WCAG AA).
- Any language (en, ar, hi, zh, tr) showing an empty section.

## Constraints (from the codebase)

- Frontend is the separate repo `frugaldomain/sydev-front` (React 19, Vite 7,
  Tailwind 4, React Router 7). This repo (`public_html`) holds the built output,
  the Laravel 12 API under `api/`, and root PHP helpers.
- The home page is prerendered into `index.html`; Apache rewrites unknown paths
  to it. `learn-preview.php` / `form-preview.php` already inject per-page head
  and body content server-side with a file cache — the pattern we reuse.
- Colours are already tokens in `src/index.css` (`--fg-*`, space-separated RGB,
  remapped under `:root[data-theme="dark"]`). The theme toggle lives in
  `provider/ThemeProvider.jsx` + `localStorage.theme`.
- Logo is hardcoded as `/images/logo.svg` in Header, Footer, AdminLayout,
  AdminLogin, ErrorMessage, PublicFormLayout, SettingsRail.
- Admin panel is the React `/admin` area (`layout/AdminLayout.jsx`, sidebar
  config in `layout/components/dashboard/SidebarAdmin.jsx` with `perId`
  permission gating). Existing reusable pieces: `Components/Media/
  MediaFilePickerModal.jsx` (file picker/upload), `page/adminDashboard/Apps/
  TranslationsFields.jsx` (per-language fields), `react-colorful` (installed).
- API modules live in `api/app/Modules/*`; admin routes use
  `auth:sanctum` + `role:admin`.
- Five site languages: en, ar, hi, zh, tr (ar is RTL).

## Approaches considered

| | How settings reach the visitor | Trade-off |
|--|--|--|
| **1 — Server injection (chosen)** | Settings stored via API; a root PHP script (like `learn-preview.php`) injects theme CSS variables, font link, logo URL and the home-sections JSON into `index.html` before it is sent. | No flash, SEO sees new content at once, instant after save. Cost: one more PHP entry point plus a cache. |
| 2 — Client fetch at boot | App fetches settings from the API on load. | Simplest, but first-time visitors see default colours briefly and crawlers see stale sections until the next build. |
| 3 — Rebuild on save | Saving triggers a Vite build + deploy. | Fully static, but each save waits for a full build and the host likely has no Node. |

Owner picked **1**.

## Design

### Data (API, new `Appearance` module or inside `Core`)

One table `site_settings` (`key` string unique, `value` JSON, timestamps) with
two keys:

- `theme`:
  ```json
  {
    "logo_url": "...", "logo_dark_url": "...|null", "favicon_url": "...|null",
    "font_body": "IBM Plex Sans Arabic", "font_heading": "IBM Plex Sans Arabic",
    "dark":  { "canvas": "#0a0f1c", "surface": "#111827", "text": "#e8e6e1",
               "muted": "#9aa3b5", "accent": "#c9a96e", "hairline": "#ffffff14" },
    "light": { "canvas": "#f7f5f0", "surface": "#ffffff", "text": "#1a1a1a",
               "muted": "#6b6b6b", "accent": "#80632c", "hairline": "#e7e2d6" },
    "radius": 14,
    "default_mode": "dark"
  }
  ```

Note: the light accent default is `#80632c` (was `#9a7a3f`) so gold links and active items meet WCAG AA: 5.15:1 on the canvas and 5.62:1 on the surface (the old value was 3.69:1 and 4.02:1). Dark values are unchanged.
- `home_sections`: ordered array of
  `{ "id": "uuid", "type": "<template>", "visible": true, "props": {...},
     "i18n": { "en": {...}, "ar": {...}, ... } }` —
  `props` holds language-neutral fields (image, link, icon, count source),
  `i18n` holds the text fields per language; missing language → `en`.

Endpoints:
- `GET  /api/site/appearance` — public, returns both keys (cached).
- `PUT  /api/admin/appearance/theme` — `role:admin`, validated.
- `PUT  /api/admin/appearance/home-sections` — `role:admin`, validated per
  template schema (unknown `type` rejected; hex colours validated; URLs must be
  http(s) or site-relative; text stripped of HTML).
- On save, write `public_html/appearance.json` (or bust the PHP cache) so the
  injector picks the change up immediately.

### Delivery

The ~205 prerendered pages are served straight from `<dir>/index.html` by the
`-f` rule in `.htaccess`, so routing every page through PHP is avoided
(builder-check finding; *my call*):

- **Theme, all pages:** the Vite HTML template gets one render-blocking
  `<link rel="stylesheet" href="/appearance.css">` in `<head>`. A root
  `appearance.php` (rewritten from `/appearance.css`) emits the `--fg-*`
  variables for both modes, `--fg-font-*`, `--fg-logo` / `--fg-logo-dark`
  (`url(...)`), and an `@import` of the chosen Google Fonts. Being
  render-blocking CSS, there is no flash and no per-page PHP.
- **Data source:** `appearance.php` fetches `GET /api/site/appearance` with a
  file cache, like `learn-preview.php` (the API runs from a separate subdomain,
  so it does not write into the site root). TTL 60 s, so "immediately" means
  within a minute of saving; on API failure serve the last good copy, then
  built-in defaults — never fail. *(My call.)*
- **Home sections, SEO:** only the five home routes (`/`, `/ar`, `/hi`, `/zh`,
  `/tr`) go through a `home-preview.php` (new `.htaccess` rules placed before
  the `-f` rule) that renders visible sections' headings/text into `#root` and
  sets `window.__HOME_SECTIONS__`; React then takes over.
- **Theme mode before paint:** a tiny inline script in the template sets
  `data-theme` from `localStorage.theme`, else the saved `default_mode`
  (emitted by `appearance.php` as a CSS custom property is not readable
  pre-paint, so the default lives in the same inline script, filled at
  build time as `dark`; changing the default mode needs a rebuild — accepted).

### Frontend tokens and fonts

- Map the saved colours onto the existing `--fg-*` tokens (and retire the old
  "Abdullah colors" block where unused). Tokens the admin does not set
  (`surface-raised`, `text-subtle`, `accent-bright`, `accent-deep`) are
  derived in CSS with `color-mix()` from the saved ones, so nothing stays
  sky-blue beside a gold accent. *(My call.)*
- Only pages already on `fg-*` tokens restyle automatically (~143 of 416
  files); scope of the rest: see "Open with owner".
- Font stacks always end in fallbacks covering Devanagari and CJK
  (`"Noto Sans Devanagari", "Noto Sans SC", system-ui`), since the Arabic
  fonts lack those scripts. *(My call.)*
- Logo: `<SiteLogo/>` renders the `--fg-logo` / `--fg-logo-dark` CSS
  variable, so it also arrives without a flash.
- Font family via `--fg-font-body` / `--fg-font-heading`, curated list of
  Arabic-capable Google Fonts (IBM Plex Sans Arabic, Readex Pro, Noto Kufi
  Arabic, Tajawal, Cairo, Noto Naskh Arabic for headings).
- Replace hardcoded `/images/logo.svg` with one `<SiteLogo/>` component
  (fallback `/images/logo.svg`).
- `ThemeProvider`: if the visitor has no saved choice, use `default_mode`.

### Public theme toggle (owner decision)

Today only the admin panel has a light/dark switch. Add a sun/moon button to
the site header (`layout/components/site/Header.jsx`), sharing
`ThemeProvider` + `localStorage.theme` with the admin; default is dark.

### Full-site restyle (owner decision)

All public pages move to the new identity in this project, not later: the
~122 files that hardcode Tailwind palette colours or hex values are swept onto
`fg-*` tokens/utilities so they follow the saved theme in both modes. Tool
internals that must keep literal colours (e.g. a colour picker's swatches, code
syntax themes, canvas drawing colours) stay as they are. Work it page group by
page group (header/footer → home → tools → learn → gallery/account), checking
each in both modes for AA contrast.

### Admin: Appearance tab (`/admin/appearance`)

Sidebar group "Appearance" with two children:

1. **Brand & colours** — logo (light/dark) and favicon through
   `MediaFilePickerModal`; body/heading font selects with live sample text;
   colour pickers (`react-colorful`) for each token in dark and light; a live
   preview panel (mini header + hero + card) that switches dark/light; a
   contrast badge (AA pass/fail) next to text/background pairs; Save and
   "Reset to defaults".
2. **Home sections** — list of current sections: drag to reorder, eye toggle
   to hide/show, edit, duplicate, delete. "Add section" opens the template
   library (thumbnail + name). Editing opens a form generated from the
   template's field schema, with text fields per language via
   `TranslationsFields`. A preview of the section renders beside the form
   using the real section component. Save publishes.

### Template library (each = one React component + a field schema)

Ported from today's home and new:

| Template | Fields |
|--|--|
| `hero_paths` | eyebrow, headline (with highlighted part), subtitle, search on/off, 3 path cards (icon, title, subtitle, link) |
| `stats_bar` | up to 4 items: number source (icons / courses / users / custom) + label |
| `continue_learning` | heading; shown only to visitors with saved course progress (`learn/progress.js`) |
| `feature_cards` | heading, subtitle, 3–6 cards (icon, title, text, link) |
| `image_text` | heading, text, image, CTA, image side |
| `cta_banner` | heading, text, button label + link |
| `testimonials` | heading; source = site ratings (min stars) or manual quotes |
| `faq` | heading, Q&A list |
| Existing showcases | `forms_showcase`, `learn_showcase`, `icons_showcase`, `draw_simulate`, `image_tools`, `dev_tools`, `components_showcase`, `apps_showcase`, `programs_showcase` — each keeps its live data and gains editable heading/subtitle |

All templates use only theme tokens (no hardcoded colours), so any added
section matches the identity in both modes.

### Default home (seeded `home_sections`)

1. `hero_paths` (designer / developer / learner)
2. `stats_bar`
3. `continue_learning`
4. `forms_showcase` → `learn_showcase` → `icons_showcase` → `draw_simulate` →
   `image_tools` → `dev_tools` → `components_showcase` → `apps_showcase` →
   `programs_showcase` (today's order, restyled)
5. `testimonials`
6. `cta_banner`

If the setting is empty, the frontend renders this same list from a bundled
default so the home never goes blank.

## Left to the builder

- Exact component split and file names inside `sydev-front`.
- Whether the API lives in a new `Appearance` module or in `Core`.
- Drag-and-drop implementation (framer-motion `Reorder` is already installed —
  prefer it over a new dependency).
- Exact light-mode shades, as long as AA contrast holds.
- Permission id for the new sidebar entries.
