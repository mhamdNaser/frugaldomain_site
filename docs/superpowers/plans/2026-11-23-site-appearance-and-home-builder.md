# Site Appearance + Home Section Builder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give the admin an Appearance tab (logo, colours, fonts) and a Home sections builder, redesign the home page in the "Midnight & Champagne" identity, and move the whole public site onto theme tokens with a visitor light/dark toggle.

**Architecture:** Settings live in one `site_settings` table behind a new Laravel `Appearance` module. A root `appearance.php` (served as `/appearance.css`, render-blocking, linked from every page) turns the saved theme into `--fg-*` CSS variables; a root `home-preview.php` injects the home sections into the five home routes. React renders sections from a registry of templates; the admin edits them through schema-driven forms.

**Tech Stack:** Laravel 12 / PHP 8.2 / Pest (API), plain PHP (root injectors), React 19 + Vite 7 + Tailwind 4 + framer-motion + react-colorful (frontend, all already installed).

**Spec:** `docs/superpowers/specs/2026-11-23-site-appearance-and-home-builder-design.md`

## Global Constraints

- Two repos. API + root PHP + `.htaccess` + built output: this worktree (`public_html`). Frontend source: `C:/Users/nagis/Desktop/frugaldomain/sydev-front` (its own git repo, not in the worktree). **Before Task 5, create branch `claude/site-appearance` in `sydev-front`**; commit frontend tasks there.
- No new npm or composer dependencies. Drag-and-drop = framer-motion `Reorder`; colour pickers = `react-colorful`.
- Frontend has no test runner. Pure logic is tested with node assert scripts in `sydev-front/scripts/check-*.mjs` (existing pattern; run with `node scripts/<file>.mjs`, exit 0 = pass). Root PHP is tested with `php scripts/check-*.php` in this worktree.
- Colour tokens are space-separated RGB triplets (`--fg-accent: 201 169 110;`) consumed as `rgb(var(--fg-accent) / a)`. Saved colours are 6-digit hex only (`/^#[0-9a-f]{6}$/i`).
- Default theme (verbatim from spec, hairlines made opaque so they fit the triplet format):
  - dark: canvas `#0a0f1c`, surface `#111827`, text `#e8e6e1`, muted `#9aa3b5`, accent `#c9a96e`, hairline `#1c2438`
  - light: canvas `#f7f5f0`, surface `#ffffff`, text `#1a1a1a`, muted `#6b6b6b`, accent `#9a7a3f`, hairline `#e7e2d6`
  - fonts: body + heading `IBM Plex Sans Arabic`; radius `14`; default_mode `dark`
- Curated fonts (only these accepted): `IBM Plex Sans Arabic`, `Readex Pro`, `Noto Kufi Arabic`, `Tajawal`, `Cairo`, `Noto Naskh Arabic`. Every stack ends with `"Noto Sans Devanagari", "Noto Sans SC", system-ui, sans-serif`.
- Languages: `en, ar, hi, zh, tr`. Missing language text → `en`; missing `en` → section not rendered.
- Section types (the only accepted values): `hero_paths, stats_bar, continue_learning, feature_cards, image_text, cta_banner, testimonials, faq, forms_showcase, learn_showcase, icons_showcase, draw_simulate, image_tools, dev_tools, components_showcase, apps_showcase, programs_showcase`.
- Admin endpoints: `auth:sanctum` + `role:admin`. Public read endpoint is unauthenticated and cached.
- New admin UI strings go through `useAdminT`; `node scripts/check-admin-i18n.mjs` must pass. New site strings go through `useLocale` dicts for all five languages; `node scripts/check-i18n.mjs` must pass.
- Colours in new/changed UI come only from `fg-*` tokens/utilities — no Tailwind palette colours, no hex in className/style.

## Review Focus

1. **API unreachable or returns garbage** → every page still renders with the default theme and the bundled default sections (Task 3, Task 4, Task 6 tests).
2. **Section saved without text for the visitor's language, or without `en`** → falls back to `en`; with no `en` the section is skipped, never an empty box (Task 6 test, Task 4 test).
3. **Admin saves colours with unreadable text/background contrast** → contrast badge shows fail and Save asks for confirmation (Task 7 test on `contrastRatio`).
4. **Hostile content in a section** (`javascript:` link, `<script>` in text) → API rejects the link and strips tags; `home-preview.php` escapes output (Task 2 test, Task 4 test).
5. **Saved JSON contains a type the frontend no longer knows** → the section is skipped, the rest render (Task 6 test).

---

### Task 1: Appearance module — storage, public read, theme save

**Files:**
- Create: `api/app/Modules/Appearance/Providers/AppearanceServiceProvider.php`, `Models/SiteSetting.php`, `Controllers/AppearanceController.php`, `Requests/UpdateThemeRequest.php`, `Routes/api.php`, `Support/AppearanceDefaults.php`, `database/migrations/2026_11_23_100000_create_site_settings_table.php`
- Modify: `api/bootstrap/providers.php` (register provider; follow `RatingServiceProvider` + how other modules load `Routes/api.php`)
- Test: `api/tests/Feature/AppearanceApiTest.php`

**Interfaces:**
- Produces:
  - Table `site_settings(id, key string unique, value json, timestamps)`; `SiteSetting::get(string $key): ?array`, `SiteSetting::put(string $key, array $value): void`.
  - `AppearanceDefaults::theme(): array` (Global Constraints values; keys `logo_url, logo_dark_url, favicon_url, font_body, font_heading, dark{canvas,surface,text,muted,accent,hairline}, light{…same}, radius, default_mode`), `AppearanceDefaults::FONTS` (curated list), `AppearanceDefaults::homeSections(): array` (returns `[]`; frontend owns the default list).
  - `GET /api/site/appearance` → `{"theme": {...merged over defaults}, "home_sections": [...], "version": <int unix ts of latest update>}`, `Cache-Control: public, max-age=60`.
  - `PUT /api/admin/appearance/theme` → 200 with saved theme.

- [ ] **Step 1: Write failing Pest tests**
  - `it returns defaults when nothing is saved`: GET → `theme.dark.accent == '#c9a96e'`, `theme.font_body == 'IBM Plex Sans Arabic'`, `home_sections == []`.
  - `it lets an admin save the theme`: PUT with `dark.accent = '#112233'` → 200; subsequent GET → `theme.dark.accent == '#112233'` and `theme.light.accent == '#9a7a3f'` (unsent keys keep defaults).
  - `it rejects non-admins`: guest → 401; user without admin role → 403.
  - `it validates colours and fonts`: `dark.accent = 'red'` → 422; `'#12345'` → 422; `font_body = 'Comic Sans'` → 422; `logo_url = 'javascript:alert(1)'` → 422; `logo_url = '/images/logo.svg'` → 200; `radius = 99` → 422 (range 0–32); `default_mode = 'blue'` → 422.
- [ ] **Step 2: Run** `cd api && php artisan test --filter=AppearanceApiTest` → FAIL (route missing).
- [ ] **Step 3: Implement** migration, model, defaults, provider, routes, `UpdateThemeRequest` (all fields `sometimes`; colours `regex:/^#[0-9a-fA-F]{6}$/`; URLs nullable and must start with `https://`, `http://` or `/`), controller `show()` / `updateTheme()` (deep-merge request over stored over defaults, then `SiteSetting::put('theme', …)`).
- [ ] **Step 4: Run tests** → PASS.
- [ ] **Step 5: Commit** `feat(api): appearance module with theme settings`.

### Task 2: Home sections save + sanitising

**Files:**
- Create: `api/app/Modules/Appearance/Requests/UpdateHomeSectionsRequest.php`, `api/app/Modules/Appearance/Support/SectionSanitizer.php`
- Modify: `AppearanceController.php` (add `updateHomeSections`), `Routes/api.php`
- Test: `api/tests/Feature/AppearanceApiTest.php`

**Interfaces:**
- Consumes: Task 1 model + routes.
- Produces: `PUT /api/admin/appearance/home-sections` body `{"sections":[{id,type,visible,props,i18n}]}` → 200 with sanitised list. `SectionSanitizer::clean(array $sections): array`.
  Section shape: `id` string ≤64, `type` ∈ section types, `visible` bool, `props` object, `i18n` object keyed by language ∈ {en,ar,hi,zh,tr}, each an object. Max 40 sections.
  Generic field rules (recursive over `props` and `i18n`, including nested list items): any key ending in `url`, `link`, `href` or `image` must be `''`, `/…`, `http://…` or `https://…`; key `color` must be 6-digit hex; every other string is `strip_tags`'d and trimmed to 2000 chars.

- [ ] **Step 1: Failing tests**
  - `it saves and returns sections in order`: two sections → GET returns same `id`s in same order.
  - `it rejects unknown section types` → 422.
  - `it rejects javascript links anywhere`: `props.cards[0].link = 'javascript:x'` → 422.
  - `it strips html from text`: `i18n.en.headline = '<b>Hi</b><script>x</script>'` → saved as `'Hix'`.
  - `it rejects unknown languages`: `i18n.fr` → 422.
- [ ] **Step 2: Run** `php artisan test --filter=AppearanceApiTest` → new tests FAIL.
- [ ] **Step 3: Implement** request rules (shape) + `SectionSanitizer` (link/colour checks throw `ValidationException`, text stripping) + controller action.
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** `feat(api): home sections save with sanitising`.

### Task 3: `/appearance.css` injector

**Files:**
- Create: `appearance.php` (worktree root), `scripts/appearance-lib.php` (pure functions), `scripts/check-appearance.php`
- Modify: `.htaccess` — add `RewriteRule ^appearance\.css$ /appearance.php [L]` before the `-f` rule (line ~46).

**Interfaces:**
- Consumes: `GET https://api.frugaldomain.site/api/site/appearance` (Task 1).
- Produces (in `scripts/appearance-lib.php`, mirrored in JS by Task 5):
  - `fg_triplet(string $hex): string` — `'#c9a96e'` → `'201 169 110'`.
  - `fg_mix(string $a, string $b, float $t): string` — per-channel `round(a + (b-a)*t)`, returns hex.
  - `fg_tokens(array $mode, array $light, array $dark): array` — map of token name → triplet:
    `canvas, surface, text, text-muted (=muted), hairline, accent`, plus derived
    `surface-raised = mix(surface, text, .04)`, `text-subtle = mix(muted, canvas, .35)`,
    `accent-bright = mix(accent, #ffffff, .2)`, `accent-deep = mix(accent, #000000, .2)`,
    and the ink scale `ink-N = mix(light.canvas, dark.canvas, f)` for N/f = 0/0, 50/.04, 100/.1, 300/.35, 500/.55, 700/.75, 800/.85, 900/.93, 950/1.
  - `fg_css(array $theme): string` — `:root{…light tokens…; --fg-font-body; --fg-font-heading; --fg-radius; --fg-logo:url(…); --fg-logo-dark:url(…)}` and `:root[data-theme="dark"]{…dark tokens…}`, preceded by an `@import` of Google Fonts for the chosen families (weights 300;400;600;700).
- Caching like `learn-preview.php` (`sys_get_temp_dir()` file, TTL 60 s); on fetch failure use the stale cache, else built-in defaults. Response: `Content-Type: text/css`, `Cache-Control: public, max-age=60`.

- [ ] **Step 1: Write `scripts/check-appearance.php`** asserting: `fg_triplet('#c9a96e') === '201 169 110'`; `fg_mix('#000000','#ffffff',0.5) === '#808080'`; `fg_css(defaults)` contains `--fg-accent: 154 122 63` (light) and, inside the dark block, `--fg-accent: 201 169 110`; contains `IBM+Plex+Sans+Arabic`; `fg_css` with a theme whose JSON is `null`/garbage falls back to defaults (no exception).
- [ ] **Step 2: Run** `php scripts/check-appearance.php` → FAIL (functions missing).
- [ ] **Step 3: Implement** `scripts/appearance-lib.php` and `appearance.php` (fetch + cache + `echo fg_css($theme)`).
- [ ] **Step 4: Run** check → prints `ok`, exit 0. Then `php -S localhost:8099` in the worktree root and `curl -s localhost:8099/appearance.php | head` shows `:root{`.
- [ ] **Step 5: Commit** `feat: appearance.css theme injector`.

### Task 4: `home-preview.php` for the five home routes

**Files:**
- Create: `home-preview.php`, `scripts/check-home-preview.php`
- Modify: `.htaccess` — before the `-f` rule: `RewriteRule ^(?:(ar|hi|zh|tr)/?)?$ /home-preview.php?lang=$1 [L,QSA]` (verify it does not catch `/apps`, `/learn`, assets).

**Interfaces:**
- Consumes: appearance JSON (shares fetch+cache with Task 3 via `scripts/appearance-lib.php`: add `fg_fetch_appearance(): array`).
- Produces: `fg_home_html(array $sections, string $lang): string` (in `appearance-lib.php`) — for each visible section, `<section><h2>…</h2><p>…</p></section>` from `i18n[$lang]` falling back to `i18n.en`, skipped when neither has a headline/heading; all text `htmlspecialchars`'d. Page = the language's own prerendered `index.html` (`/index.html` or `/<lang>/index.html`) with `#root` contents replaced and `<script>window.__HOME_SECTIONS__=<json, JSON_HEX_TAG>;</script>` added before `</head>`. Empty/failed sections → serve the prerendered file unchanged.

- [ ] **Step 1: Add asserts to `scripts/check-home-preview.php`**: ar text used when present; en used when ar missing; section with no en and no ar omitted; `visible:false` omitted; headline `<img onerror=x>` comes out as `&lt;img onerror=x&gt;`; JSON containing `</script>` is emitted as `\u003C/script\u003E`.
- [ ] **Step 2: Run** `php scripts/check-home-preview.php` → FAIL.
- [ ] **Step 3: Implement** `fg_home_html`, `fg_fetch_appearance`, `home-preview.php`.
- [ ] **Step 4: Run** check → `ok`. With `php -S` + a stub index, `curl localhost:8099/home-preview.php?lang=ar` contains `__HOME_SECTIONS__`.
- [ ] **Step 5: Commit** `feat: home-preview injects home sections`.

### Task 5: Frontend tokens, fonts, mode toggle, logo

*(sydev-front, branch `claude/site-appearance`)*

**Files:**
- Modify: `index.html` (add `<link rel="stylesheet" href="/appearance.css">` after the favicon links; inline script before it: `document.documentElement.dataset.theme = localStorage.getItem('theme') || 'dark';`), `src/index.css` (set `:root` / `[data-theme="dark"]` token defaults to the Global Constraints values so dev and prerender match; add `--fg-font-body/heading`, `--fg-radius`, `--fg-logo*`; `body{font-family:var(--fg-font-body)}`, headings use `--fg-font-heading`; delete the "Abdullah colors" variables that no file references — grep each before deleting), `src/provider/ThemeProvider.jsx` (default `'dark'`, write `data-theme` + `localStorage.theme` on change), `src/layout/components/site/Header.jsx` (sun/moon button, `aria-label` from i18n, `FiSun`/`FiMoon`), `AdminLayout.jsx` (use ThemeProvider instead of its own `sessionStorage.mode` so both share one setting).
- Create: `src/Components/SiteLogo.jsx`, `src/utils/themeTokens.js`, `scripts/check-theme-tokens.mjs`
- Modify (logo): Header, Footer, AdminLayout, AdminLogin, ErrorMessage, PublicFormLayout, SettingsRail → `<SiteLogo className=… />`.

**Interfaces:**
- Produces:
  - `SiteLogo({ className, alt = "Fruga" })` — renders `<span role="img" aria-label={alt}>` with `background-image: var(--fg-logo, url(/images/logo.svg))`, switching to `--fg-logo-dark` under dark mode via CSS.
  - `src/utils/themeTokens.js`: `hexToTriplet(hex)`, `mixHex(a, b, t)`, `themeTokens(theme) → { light: {name: triplet}, dark: {...} }`, `contrastRatio(hexA, hexB) → number` (WCAG 2.1 relative luminance), `DEFAULT_THEME`, `FONTS`. Same math and token names as Task 3 (used by the admin live preview).

- [ ] **Step 1: Write `scripts/check-theme-tokens.mjs`**: `hexToTriplet('#c9a96e') === '201 169 110'`; `mixHex('#000000','#ffffff',.5) === '#808080'`; `themeTokens(DEFAULT_THEME).dark.accent === '201 169 110'`; token names equal the PHP list from Task 3; `contrastRatio('#000000','#ffffff') === 21`; `contrastRatio('#e8e6e1','#0a0f1c') >= 4.5`; `contrastRatio('#1a1a1a','#f7f5f0') >= 4.5`.
- [ ] **Step 2: Run** `node scripts/check-theme-tokens.mjs` → FAIL.
- [ ] **Step 3: Implement** `themeTokens.js`, then the CSS/template/provider/header/logo changes.
- [ ] **Step 4: Verify**: check script passes; `npm run lint` clean; `npm run dev`, open `/` — dark navy by default, toggle switches to ivory and persists on reload; no light flash on reload (throttle network in devtools to confirm); logo shows in header/footer/admin.
- [ ] **Step 5: Commit** `feat: theme tokens, fonts, visitor mode toggle, SiteLogo`.

### Task 6: Section registry + data-driven Home

**Files:**
- Create: `src/page/site/home/sections/registry.js`, `src/page/site/home/sections/defaultSections.js`, `src/page/site/home/sections/resolveSections.js`, `src/Apihooks/useAppearance.js`, `scripts/check-home-sections.mjs`
- Modify: `src/page/site/home/Home.jsx`

**Interfaces:**
- Produces:
  - Field schema: `{ key, kind: 'text'|'textarea'|'image'|'link'|'icon'|'number'|'select'|'toggle'|'list', label, translatable?: bool, options?: string[], fields?: Field[] (for list), max?: number }`.
  - `registry[type] = { component, label, thumbnail, fields: Field[], defaults: { props, i18n: { en, ar } } }` for all 17 types (components added in Tasks 8–9; register placeholders that render `null` until then).
  - `DEFAULT_SECTIONS` — spec order: `hero_paths, stats_bar, continue_learning, forms_showcase, learn_showcase, icons_showcase, draw_simulate, image_tools, dev_tools, components_showcase, apps_showcase, programs_showcase, testimonials, cta_banner`, each `{id, type, visible:true, props, i18n}` built from `registry[type].defaults`.
  - `resolveSections(saved, lang) → [{ id, type, Component, props, text }]` — `saved` empty/invalid → `DEFAULT_SECTIONS`; drops `visible:false` and unknown types; `text = { ...i18n.en, ...i18n[lang] }`; drops a section whose `text` is empty **only when its fields declare translatable keys**.
  - `useAppearance()` — react-query `GET /site/appearance` via `requestHandler`, `initialData` from `window.__HOME_SECTIONS__`, `staleTime` 60 s. Called once in `SiteLayout`; when `theme.favicon_url` is set it updates the `<link rel="icon">` hrefs (crawlers keep using `/favicon.ico` — accepted).
  - `Home.jsx` maps `resolveSections(...)` to `<Component props={props} text={text} />` after `<SeoHome/>`.

- [ ] **Step 1: Write `scripts/check-home-sections.mjs`**: `resolveSections([], 'en').length === DEFAULT_SECTIONS.length`; `resolveSections(null,'ar')` same; unknown type skipped; `visible:false` skipped; ar falls back to en per key; section with translatable fields but no en/ar text skipped; every `DEFAULT_SECTIONS` type exists in `registry`; registry keys equal the 17 types in Global Constraints.
- [ ] **Step 2: Run** `node scripts/check-home-sections.mjs` → FAIL.
- [ ] **Step 3: Implement** registry (placeholders), defaults, resolver, hook, Home.jsx.
- [ ] **Step 4: Run** check → pass; `npm run dev` home renders (still old components wired through the registry for the 9 showcases).
- [ ] **Step 5: Commit** `feat: data-driven home sections`.

### Task 7: Admin — Brand & colours

**Files:**
- Create: `src/page/adminDashboard/Appearance/BrandAndColours.jsx`, `src/page/adminDashboard/Appearance/ThemePreview.jsx`, `src/Apihooks/useAdminAppearance.js`
- Modify: `src/router.jsx` (lazy route `/admin/appearance`), `public/json/adminList.json` (group "Appearance" → "Brand & colours" `/admin/appearance`, "Home sections" `/admin/appearance/home`; reuse the admin permission id used by other admin-only entries)

**Interfaces:**
- Consumes: `themeTokens`, `contrastRatio`, `DEFAULT_THEME`, `FONTS` (Task 5); `PUT /admin/appearance/theme` (Task 1); `MediaFilePickerModal` (read its props before use).
- Produces: `useAdminAppearance()` → `{ data, saveTheme(theme), saveSections(sections) }` (react-query mutations invalidating `useAppearance`).
- Behaviour: logo light/dark + favicon pickers; two font selects with a sample line in each font; colour pickers for the 6 tokens × 2 modes; `ThemePreview` sets the tokens from `themeTokens(draft)` as inline CSS variables on its own wrapper and shows a mini header, hero and card, with a dark/light switch; contrast badges for text/canvas, muted/canvas, text/surface in both modes (pass ≥ 4.5); if any fails, Save asks for confirmation; "Reset to defaults" restores `DEFAULT_THEME`.

- [ ] **Step 1: Verify** contrast logic is covered by Task 5 checks (no new pure logic here).
- [ ] **Step 2: Implement** page, preview, hook, route, sidebar entry, admin i18n keys.
- [ ] **Step 3: Verify**: `node scripts/check-admin-i18n.mjs` passes; in the browser as admin, change accent → preview updates live; Save → reload `/` within 60 s shows the new accent; set text = canvas → badge "fail" and Save asks to confirm.
- [ ] **Step 4: Commit** `feat(admin): brand & colours tab`.

### Task 8: New section templates + hero redesign

**Files:**
- Create in `src/page/site/home/sections/`: `HeroPaths.jsx`, `StatsBar.jsx`, `ContinueLearning.jsx`, `FeatureCards.jsx`, `ImageText.jsx`, `CtaBanner.jsx`, `Testimonials.jsx`, `Faq.jsx`
- Modify: `registry.js` (real components, fields, defaults in en + ar)

**Interfaces:**
- Consumes: registry contract (Task 6); `learn/progress.js` (read its exports) for `ContinueLearning`; site ratings public endpoint used by the existing SiteRatings feature for `Testimonials` (source `ratings` with `min_stars`, or `manual` list).
- Every component: `({ props, text })`, tokens only, line icons from `react-icons/fi`, RTL-safe (logical `ms-/me-/ps-/pe-`), mobile-first.
- `HeroPaths` matches the chosen mockup (`.superpowers/brainstorm/*/content/visual-identity-v2.html`, option 1): eyebrow pill, headline with highlighted part in accent, subtitle, search box with `Ctrl K` hint (opens existing site search if one exists, else navigates to `/tools?q=`), three path cards (designer → icons/SVG, developer → JSON/JWT/Cron, learner → learn).
- `StatsBar` sources: `icons` (519 until an endpoint exists), `courses`, `users`, `custom`.
- `ContinueLearning` renders `null` when there is no saved progress.

- [ ] **Step 1: Extend `check-home-sections.mjs`**: every registry entry has `fields` and `defaults.i18n.en`; every translatable field key in `fields` exists in `defaults.i18n.en`.
- [ ] **Step 2: Run** → FAIL for the new types.
- [ ] **Step 3: Implement** the eight components + registry entries.
- [ ] **Step 4: Verify**: check passes; home in `/` and `/ar`, both modes, 375 px and 1280 px wide — matches the mockup direction, no horizontal scroll, contrast OK; `ContinueLearning` appears only after opening a lesson.
- [ ] **Step 5: Commit** `feat: new home section templates and hero redesign`.

### Task 9: Restyle existing showcases as templates

**Files:**
- Modify: the nine files in `src/page/site/home/components/` (`FormsSection, LearnSection, IconSections, DrowAndSimulate, ImageSection, DevToolsSection, ComponentsSection, AppsSection, ProgramsSection`); `registry.js`; remove the old `Hero.jsx` once `HeroPaths` replaces it.

- Each takes `({ props, text })`, uses `text.heading` / `text.subtitle` (defaults = today's copy, en + ar), keeps its live data, and uses tokens only.

- [ ] **Step 1: Extend check**: the nine showcase registry entries declare translatable `heading` and `subtitle`.
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Verify**: check passes; each showcase renders in both modes and in `/ar`.
- [ ] **Step 5: Commit** `refactor: home showcases as editable templates`.

### Task 10: Admin — Home sections builder

**Files:**
- Create: `src/page/adminDashboard/Appearance/HomeSections.jsx`, `SectionLibraryModal.jsx`, `SectionFieldsForm.jsx`
- Modify: `src/router.jsx` (`/admin/appearance/home`)

**Interfaces:**
- Consumes: `registry`, `DEFAULT_SECTIONS` (Task 6); `saveSections` (Task 7); `TranslationsFields` (read its props first; if it cannot host arbitrary field kinds, render per-language tabs `en ar hi zh tr` with the same look).
- Behaviour: list = saved sections or `DEFAULT_SECTIONS`; `Reorder.Group` drag; eye toggle (`visible`); edit; duplicate (new `crypto.randomUUID()` id); delete with confirm; "Add section" → library modal (label + thumbnail per registry entry) → appends with `registry[type].defaults`; `SectionFieldsForm` generated from `fields` (`list` kind = repeatable group with add/remove/reorder; `image` via `MediaFilePickerModal`; `icon` = select from a fixed set of `react-icons/fi` names); live preview renders `registry[type].component` beside the form inside the site token scope; "Publish" saves; unsaved-changes warning on leave.

- [ ] **Step 1: Implement.**
- [ ] **Step 2: Verify** in the browser as admin: add `cta_banner`, fill en + ar, move it to 2nd place, hide `programs_showcase`, Publish → `/` and `/ar` show the change within 60 s; `curl https://<local>/ar` HTML contains the new heading (home-preview); API 422 (e.g. `javascript:` link) shows the error message, nothing lost.
- [ ] **Step 3:** `node scripts/check-admin-i18n.mjs` passes.
- [ ] **Step 4: Commit** `feat(admin): home sections builder`.

### Task 11: Full-site sweep onto tokens

**Files:**
- Create: `scripts/check-hardcoded-colors.mjs` with an allowlist array of files whose literal colours are content (colour pickers/swatches, code syntax themes, canvas/drawing engines, favicon/OG/code-image generators' output).
- Modify: every file it reports, in groups, one commit per group: (a) `src/layout/**` + shared `src/Components/**`, (b) `src/page/site/tools/**` + converters, (c) `src/page/site/learn/**`, (d) icons gallery, components gallery, apps, downloads, (e) account, auth pages, static pages (about/faq/privacy/terms/contact), 404.

- Detection regex (className strings and style objects): `\b(bg|text|border|from|via|to|ring|fill|stroke|divide|outline|shadow)-(slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|white|black)(-\d{2,3})?\b` and `#[0-9a-fA-F]{3,8}\b` inside `className=`/`style=`. Status colours (errors/success) map to new tokens `--fg-danger`, `--fg-success` added in `index.css` (light/dark values chosen for AA on canvas) — not to accent.

- [ ] **Step 1: Write the check script**; run → lists current offenders (expect ~120 files), exit 1.
- [ ] **Step 2–6:** for each group (a)–(e): replace with `fg-*` utilities; run the check (group's files gone from the list); open each page of the group in dark and light at 375 px and 1280 px; commit `style: move <group> onto theme tokens`.
- [ ] **Step 7:** check exits 0; `npm run lint` clean.

### Task 12: Build, deploy to worktree, end-to-end check

- [ ] **Step 1:** `cd sydev-front && npm run build` → success, prerender ran (Chrome found).
- [ ] **Step 2:** Copy `dist/` into the worktree root as described in `DEPLOYMENT.md` / `scripts/deploy-build.sh` (read it first; do not delete root PHP files or `api/`).
- [ ] **Step 3:** Serve the worktree root with Apache rules (or `php -S` + manual checks) and verify: `/appearance.css` served as CSS; `/`, `/ar`, `/tr` go through home-preview (view-source has `__HOME_SECTIONS__`); a deep page (`/IconsGalary`) still served from its own prerendered file but restyled by `/appearance.css`; stopping the API → pages still render with defaults.
- [ ] **Step 4:** Run `php scripts/check-appearance.php`, `php scripts/check-home-preview.php`, `cd api && php artisan test --filter=Appearance`, all `node scripts/check-*.mjs` → all pass.
- [ ] **Step 5:** Commit built output in the worktree: `build: site appearance + home builder`.
