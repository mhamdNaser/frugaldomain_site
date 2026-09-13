# Drawing editor — roadmap to a Canva-class tool

Target: turn `/Drower/Board` from an SVG shape editor into a real design
surface — multi-element documents, images, templates, export presets.

Each phase is independently shippable: the editor keeps working after every
one. **A phase is deleted from this file once it is finished**, so whatever
remains below is the work still outstanding.

---

## Where the editor stands today

Audited `sydev-front/src/page/site/drower/` (1,874 lines across 15 files).

**What already works**

- 13 tools: select, pen, curve, line, rect, circle, triangle, star,
  pentagon, hexagon, text, plus grouping
- Raw `<svg>` canvas driven by React state (`DrawBoard.jsx`, 707 lines)
- Zoom, toggleable grid, snap-to-grid
- Resize handles (`ResizableElement.jsx`), inline text editing
  (`EditableText.jsx`), font family/size pickers, colour picker
- SVG import (`useImportSVG.js`) and SVG export
- Boolean operations (union / subtract / intersect)
- Interaction logic already split into 5 hooks

**What is missing — the honest gaps**

| Gap | Impact |
|---|---|
| **No undo/redo anywhere** | Confirmed: zero `undo`/`redo` matches in the whole folder. The single biggest usability hole. |
| No layers panel | `shapeGroups` exists in state but there is no z-order UI |
| No image support | Cannot place a photo on the canvas at all |
| SVG-only export | No PNG, JPG or PDF output |
| No persistence | Reloading the page loses the entire document |
| No templates or presets | Every document starts empty at a fixed size |
| All state in one component | 707-line file holding ~12 `useState` calls; every new feature compounds the cost |

**Already installed but unused:** `konva` (1.7 MB) and `react-konva`
(224 KB) are in `package.json` and `node_modules` but **imported nowhere**.
They are shipped as dead weight. Phase 1 decides their fate.

---

## Phase 1 — Foundation *(prerequisite for everything after)*

Nothing else is safe to build until this lands.

### The architectural decision (settle first)

**Option A — stay on SVG, add a state layer *(recommended)***
Keep the existing `<svg>` renderer; add a document model, undo/redo, and
split the god component.
- *For:* no rewrite; SVG export stays lossless; text stays selectable and
  accessible; crisp at any zoom; all 13 tools keep working; uninstalling
  `konva`/`react-konva` cuts ~2 MB
- *Against:* hand-rolled hit-testing; slows past ~1,000 nodes; raster
  effects (blur, filters, brushes) are harder

**Option B — migrate the canvas to Konva**
- *For:* built-in transformer handles, hit detection, layers; comfortable
  into tens of thousands of nodes; PNG/JPG export built in
- *Against:* a real rewrite of all 13 tools and 5 hooks; **SVG export
  becomes lossy** — Konva is a raster engine; text becomes pixels

**Recommendation: Option A.** This product is an *icon* tool — its value is
clean vector output feeding the icon library, which is exactly what Option B
trades away. Revisit only if a document ever needs 1,000+ elements.

> **Decision: Option A.** Taken 2026-09-11. The SVG renderer stays.

### Tasks

- [x] Settle the renderer decision — Option A
- [ ] Define the document model — a serialisable tree:
      `{ id, version, width, height, background, elements[], groups[] }`,
      each element `{ id, type, x, y, w, h, rotation, opacity, z, style, data }`
- [ ] Move editor state out of `DrawBoard.jsx` into a store.
      Use **Zustand + Immer** — small, hook-shaped, and Immer's structural
      sharing is what makes cheap history snapshots work
- [x] **Undo/redo**, 50 steps, on <kbd>Ctrl/Cmd+Z</kbd> and
      <kbd>Ctrl/Cmd+Shift+Z</kbd>, plus toolbar buttons. Implemented in
      `hooks/useHistory.js` as a watcher over `paths`/`textItems` rather than
      a store rewrite, so no existing hook or call site had to change
- [ ] Split `DrawBoard.jsx` into `<Canvas>`, `<Toolbar>`, `<Inspector>`,
      `<LayersPanel>`
- [ ] If Option A: `npm uninstall konva react-konva` (−2 MB)

**Done when:** every existing tool still works, and every action is undoable.

---

## Phase 2 — Editor essentials

The things users assume exist.

- [ ] **Layers panel**: reorder, rename, lock, hide, nest into groups
- [ ] **Copy / paste / duplicate** (<kbd>Ctrl+C/V/D</kbd>), including across tabs
- [ ] **Alignment**: align 6 ways, distribute, smart guides against other
      elements and canvas centre
- [ ] **Multi-select**: marquee drag, <kbd>Shift</kbd>+click, transform as one
- [ ] **Canvas presets**: A4, square, story, banner, custom, with orientation
- [ ] **Autosave to `localStorage`** plus a recover-on-reload prompt

**Done when:** a user can build a multi-element layout without losing work.

---

## Phase 3 — Content

Where it starts feeling like Canva.

- [ ] **Image support** — upload, drag-drop, paste from clipboard; crop,
      flip, corner radius, opacity
- [ ] **Icon library integration** — pull from the existing 324-icon gallery
      straight onto the canvas. This is the feature only *this* product can
      offer, and it is why Option A matters
- [ ] **Rich text** — line height, letter spacing, alignment, lists, and a
      curated web-font set (subset the files; do not ship whole families)
- [ ] **Gradients & effects** — linear/radial fills, drop shadow, blur,
      stroke styles (dashed, dotted, join/cap)
- [ ] **Export presets** — SVG (lossless), PNG/JPG at 1×/2×/3×, PDF. Reuse
      the **already dynamically imported** `jspdf`; keep it lazy
- [ ] **Background remover** for placed images *(optional, evaluate cost)*

**Done when:** a complete social post or icon sheet can be produced end to end.

---

## Phase 4 — Templates & reuse

- [ ] Template gallery, seeded with ~20 starters
- [ ] "Save as template" for signed-in users
- [ ] Brand kit: saved palettes, fonts, logos
- [ ] Duplicate / version a document
- [ ] Server-side persistence — new Laravel module, documents owned by a user
- [ ] Public share link (view-only)

**Done when:** a user starts from a template instead of a blank canvas.

---

## Phase 5 — Collaboration *(only if genuinely wanted)*

- [ ] Real-time multi-user editing (Yjs or Liveblocks — significant scope)
- [ ] Comments pinned to elements
- [ ] Version history with restore
- [ ] Mobile/tablet touch editing

---

## Non-negotiables for every phase

Carried over from problems already fixed in this codebase:

1. **Never interpolate a JS variable into a Tailwind class.**
   `` className={`bg-${c}`} `` and `bg-[${VAR}]` compile to *nothing*. This
   bug silently broke the site header, the mobile menu, the icon preview
   panel, and made three pages' headings invisible. Use static strings or a
   lookup object. Inline `style={{}}` is fine for genuinely dynamic values.
2. **Keep heavy libraries lazily imported**, as `jspdf`/`xlsx` already are.
   The initial payload is ~400 KB after cutting it 89% from 3.6 MB — the
   editor must not undo that. Load it as a route chunk.
3. **Static assets must live in `sydev-front/public/`.** Anything outside it
   is never copied into the build — this is what broke the dashboard sidebar
   and the entire Help Center.
4. **Write JSON without a BOM.** `response.json()` rejects it.
5. **Use the brand layer** (`.fg-*` utilities and `fg-*` colours in
   `src/index.css`) plus the theme tokens (`bg-blocks-color`,
   `text-primary-text`, `border-main-border`) so dark mode keeps working.
6. **Every canvas action goes through the store** once Phase 1 lands, or
   undo/redo silently rots.
7. **Accessibility:** keyboard-reachable tools, real focus states, ARIA
   labels on icon-only buttons.

---

**Next action:** settle the renderer decision, then start Phase 1 with the
document model and undo/redo.
