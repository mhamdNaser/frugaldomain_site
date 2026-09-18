# SEO — what is done and what is left

Written for: whoever picks up FrugalDomain's search work next (likely Muhammed).

## Where the site stands

43 prerendered routes, all verified to have exactly one `<h1>`, one meta
description, one self-referencing canonical, valid JSON-LD, reciprocal
hreflang, and — where a page shows an FAQ — schema answers that match the
visible copy word for word.

| | Before | Now |
|---|---|---|
| Indexable routes | 15 | 43 |
| Arabic routes | 0 | 21 |
| Pages with a tool/FAQ schema | 0 | 27 |
| Thinnest tool page | 237 chars | 761 chars (`/Drower/Board`, an editor) |

The technical layer is finished. Nothing further in metadata, structured data,
prerendering or sitemap generation will move rankings much. What is left is
content depth, and links from other sites.

---

## 1. Backlinks — the real ceiling

Google measures authority largely by who links to you. The site currently has
essentially none, which caps every page regardless of how good its markup is.
This is the single highest-value remaining task and it cannot be automated —
it needs a person posting from real accounts.

Realistic sources, roughly in order of effort-to-reward:

- **Product Hunt** — launch the icon library or the converter suite. One good
  launch is worth more than months of small links.
- **Reddit** — r/webdev, r/SideProject, r/design_critiques. Post the tool as a
  useful free thing, not as an advert; these communities punish marketing tone.
- **"Free tools" roundup lists** — search for `"free image converter" list` or
  `best free svg icons`, find the roundups that rank, and email the author.
  Many actively want submissions.
- **Dev.to / Hashnode** — write up something the codebase already solved. Two
  posts with a real story in them:
  - how the prerenderer silently filled `sitemap.xml` and `robots.txt` with
    NUL bytes, and how the build now restores them (see `vite.config.js`)
  - why an accordion that unmounts its answers breaks FAQ rich results
- **GitHub** — the icon library is plausible as an open repo; repo links are
  followed and carry weight.

Do not buy links. Google devalues them and a manual penalty is far harder to
undo than slow growth is to wait out.

---

## 2. Content still worth writing

These pages are indexed and correct, but too thin to compete:

| Page | Text | What it needs |
|---|---|---|
| `/Drower/Board` | 761 | Nothing — it is the editor. `/vector-editor` carries its copy. |
| `/Contact-Us` | 902 | Fine as-is; contact pages do not need depth. |
| `/ImageConvert` | 1,006 | Format-comparison copy, like the `/convert/*` pages have. |
| `/apps` | 1,478 | A sentence per app; currently just cards. |
| `/ar/ImageConvert` | 1,504 | Arabic equivalent of the same. |
| `/About-Us` | 1,329 | Team and story; matters for E-E-A-T. |

`/AudioConvert`, `/FileConvert`, `/Drower/TouchSimulator` and `/vector-editor`
were the thin ones and have been expanded (roughly 750 chars each to
2,300–3,600), each with an FAQ rendered on the page and mirrored as
structured data.

### More conversion landing pages

`src/data/conversions.js` is the only file to edit — a new entry generates the
route, the prerendered snapshot, the sitemap entry and the structured data.
Worth adding once the first six show traffic:

`gif-to-png` · `webp-to-jpg` · `jpg-to-gif` · `bmp-to-png` · `heic-to-jpg`

`heic-to-jpg` has the highest search volume of these by a wide margin — iPhone
photos — but check the API supports HEIC input before adding the page.

### Audio and document conversions

The same landing-page pattern would work for `/convert/mp3-to-wav`,
`/convert/wav-to-mp3` and `/convert/docx-to-pdf`. The components are already
generic; they currently assume the image converter, so `ConversionLanding.jsx`
would need a `tool` field on each entry to pick which converter to render.

---

## 3. Arabic — 21 of 43 routes

Every English route now has an Arabic counterpart except `/Drower/Board`, and
that one is deliberate: the editor fills the viewport and has no room for copy,
so `/ar/vector-editor` carries its Arabic text instead.

The Arabic pages carry copy rather than duplicating the tools. Each interactive
tool is one shared component at its English URL; what ranks in Arabic is the
explanatory text, and each Arabic page routes into the shared tool. That avoids
a second copy of every converter.

To add or extend one: Arabic copy goes in `src/data/i18n/ar.js`, the page uses
`ArabicPage.jsx` (header, sections, FAQ, related links), then a route in
`router.jsx`, an entry in `scripts/routes.js`, and `altPath` on the English
counterpart's `<Seo>` so the hreflang pair stays reciprocal — Google ignores a
one-sided annotation entirely.

Thinnest Arabic pages, if you want to go further: `/ar/documentation` (834),
`/ar/Privacy-Policy` (1,038), `/ar/about-us/muhammed-nasser-edden` (1,077).
The documentation page is a summary that routes to the full English reference;
translating the whole technical reference is a much larger job and probably
not worth it before the Arabic pages show traffic.

## 4. After deploying — do this

1. **Search Console** — resubmit `sitemap.xml` (43 URLs now).
2. **URL Inspection → Request Indexing** for the new pages. Cuts recrawl from
   weeks to days. Start with `/convert/jpg-to-png`, `/ar`, `/vector-editor`.
3. **Rich Results Test** — check `/convert/jpg-to-png`, `/Faq` and
   `/ar/IconsGalary`. FAQ rich results usually appear in one to two weeks.
4. **International Targeting** in Search Console — confirm no hreflang errors.
5. **Two weeks later**, open the Performance report and filter to positions
   8–20. Those are queries already on the edge of page one; a paragraph or two
   aimed at each is the cheapest ranking gain available.

---

## Things that will break if you are not careful

- **Never add a static `<link rel="canonical">` to `index.html`.** The
  prerenderer waits for that element to decide a page has finished rendering.
  A static one satisfies the wait instantly, before react-helmet commits, and
  the route gets snapshotted with the bare fallback `<head>` — silently, and a
  different set of pages each build. This already happened once.
- **Never render an FAQ answer conditionally** (`{isOpen && <p>…</p>}`). It
  keeps the answer out of the prerendered HTML while the schema still claims
  it, and Google drops the rich result. Collapse with CSS height instead; see
  `FAQPage.jsx`.
- **Do not set `document.documentElement.lang` outside `/ar` handling.** It
  races Helmet and can mislabel an Arabic page as English. `TranslationProvider`
  now stands aside under `/ar` for exactly this reason.
- **`sitemap.xml` and `robots.txt` are restored after prerendering** by a
  plugin in `vite.config.js`. If you change the build, verify both still have
  zero NUL bytes before deploying — the files keep their correct size when
  corrupted, so the damage is invisible in a directory listing.
- **`npm run build` returns before the prerenderer has written its files.**
  The route directories under `dist/` appear a few minutes after the command
  exits, and starting another build in the meantime wipes what was written. So
  wait for every route directory to exist before copying `dist/` anywhere, and
  never run two builds back to back.
