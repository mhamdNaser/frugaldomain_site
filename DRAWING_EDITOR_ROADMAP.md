# Drawer — roadmap to a Canva-class tool

Target: turn `/Drower/Board` from an SVG shape editor into a real design
surface — multi-element documents, images, templates, export presets.

The tab is called **Drawer**, not "Icon Drawer": it is a general vector editor
that happens to ship an icon library, not a tool for drawing icons. The route
stays `/Drower/Board` so existing links and the sitemap keep working.

Each phase is independently shippable: the editor keeps working after every
one. **A phase is deleted from this file once it is finished**, so whatever
remains below is the work still outstanding.

---

## Where the editor stands today

Audited `sydev-front/src/page/site/drower/` after the Phase 2 / Phase 3 pass.

**What already works**

- 32 tools: select, pan, pen, line, arrow, curve, rect, circle, ellipse,
  triangle, diamond, star, pentagon, hexagon, octagon, text, plus the vector
  set — rounded rect, squircle, arc, pie, donut, burst, sparkle, speech
  bubble, cross, heart, chevron, parallelogram, trapezoid — and place-image,
  the icon library, grouping and boolean ops
- **Clip and mask**: stack a shape over artwork and clip it to that shape, or
  mask it for a luminance fade. One-click crop to square, rounded, circle or
  inset. Survives save/reload and exports losslessly (`utils/clipping.js`)
- **Path operations**: convert a primitive to an editable path, outline a
  stroke into a filled shape, simplify a dense freehand path, and grow/shrink
  a shape by a fixed distance (`utils/pathOps.js`)
- Raw `<svg>` canvas driven by React state, split across `utils/`, nine hooks
  and nineteen components
- **Menu bar** (File / Edit / Insert / Arrange / View / Help) carrying every
  action, so the toolbar stays short. Nothing is menu-only by necessity: the
  frequent actions are still on the toolbar, the status bar or a shortcut
- **Show or hide any region** from View > Panels - toolbar, inspector, icon
  library, status bar - plus the rulers. The arrangement is remembered
- **Rulers** on both edges, in px, cm, mm, inches or points, shading the
  artboard, marking the selection's extent and tracking the cursor
- **Save and open** a drawing as an editable `.fruga.json` file
  (<kbd>Ctrl/Cmd+S</kbd>), separate from image export
- Undo/redo (50 steps), layers panel with rename / reorder / lock / hide /
  duplicate, groups
- Per-element **fill, stroke, stroke width, opacity, dash, cap and join**;
  with nothing selected the same panel sets the style for the next shape
- **Gradients** (linear / radial, presets), **drop shadow** and **blur**, all
  built from one set of descriptors so canvas and exported file cannot diverge
- **Smart guides**: dragging snaps to other elements' edges and centres and to
  the artboard's edges and centre lines, with guide lines drawn live.
  <kbd>Alt</kbd> suspends them; the threshold is in screen pixels, so it feels
  identical at any zoom
- **Icon library**: all 324 icons from the site's own gallery, searchable and
  filterable by category, dropped on the canvas as inlined vector artwork -
  recolourable, resizable and losslessly exportable
- Multi-select (marquee + Shift-click) that drags *and resizes* as one object
- Align 6 ways, distribute on both axes, four z-order moves, flip, rotate
- Copy / cut / paste / duplicate, arrow-key nudge, right-click context menu
- **Command palette** (<kbd>Ctrl/Cmd+K</kbd>) over every action
- **Status bar**: cursor position, selection size, artboard size, grid / snap /
  guide toggles and a zoom slider
- Eight-handle resize with a rotation handle, Shift to keep the ratio, and a
  live size readout - works on **every** shape type, paths, icons and images
- Images: file picker, drag-and-drop and clipboard paste, stored as data URIs
- Artboard presets (icon / social / print), orientation swap, background colour
  including transparent
- Zoom to 10-800%, Ctrl+wheel zoom, fit-to-window, space-drag panning
- Autosave to `localStorage` with a recover-on-reload prompt
- Export: SVG, PNG, JPG, WebP at 1-4x, and PDF, plus copy-image-to-clipboard
- SVG import, keyboard shortcuts dialog

**What is missing - the honest gaps**

| Gap | Impact |
|---|---|
| No central store | State still lives in `DrawBoard.jsx` + hooks. It is now behind a shared `utils/` layer, so this is maintenance debt rather than a blocker |
| Menus are mouse-driven | They open on click and hover and close on Escape, but arrow-key navigation between rows is not wired yet |
| Group rotation | A multi-selection resizes as one but still rotates one member at a time |
| No equal-spacing hints | Guides snap to edges and centres; they do not yet suggest matching gaps between three or more elements |
| Icons need the API | The library is fetched live, so the panel shows an error state offline. Placed icons are inlined and keep working |
| No templates or server persistence | Every document starts blank, and autosave is per-browser |
| Rotated shapes hit-test as unrotated | Selection uses the axis-aligned box, so a heavily rotated shape has a slightly off click target |
| Rotated clip re-align | A clip follows its element through resize and move; rotating the element afterwards does not rotate the window with it |

---

## Phase 1 - Foundation

- [x] Settle the renderer decision - Option A, SVG stays
- [x] **Undo/redo**, 50 steps, on <kbd>Ctrl/Cmd+Z</kbd> and
      <kbd>Ctrl/Cmd+Shift+Z</kbd>, plus toolbar buttons (`hooks/useHistory.js`)
- [x] Split `DrawBoard.jsx` - geometry, styling, serialisation, resizing,
      effects, guides and icon parsing now live in `utils/`; arrange,
      clipboard, autosave, images and the icon library in `hooks/`; the
      toolbar, inspector, menus, palette and status bar in `components/`
- [ ] Define a serialisable document model as a first-class type. The autosave
      payload (`{ version, paths, textItems, width, height, background }`) is
      the de-facto schema - promote it and version it properly
- [ ] Move editor state into a store (**Zustand + Immer**)

**Done when:** every canvas action goes through one owner of state.

---

## Phase 2 - Editor essentials

- [x] **Layers panel**: reorder, rename, lock, hide, duplicate
- [ ] Nest layers into groups in the panel (groups exist, the tree does not)
- [x] **Copy / paste / duplicate** (<kbd>Ctrl+C/V/D</kbd>) and cut
- [ ] Clipboard across tabs - it is in-memory, so it does not survive a reload
- [x] **Alignment**: align 6 ways, distribute on both axes
- [x] **Smart guides** against other elements and the artboard centre
- [ ] Equal-spacing hints between three or more elements
- [x] **Multi-select**: marquee drag, <kbd>Shift</kbd>+click, moves and resizes
      as one object
- [ ] Rotate a multi-selection as one
- [x] **Canvas presets**: icon, social and print sizes, custom, orientation swap
- [x] **Autosave to `localStorage`** plus a recover-on-reload prompt
- [x] **Menu bar** over every action, with the toolbar cut back to the
      constantly-used handful
- [x] **Show/hide every panel** from the menu, remembered between sessions
- [x] **Rulers** with a selectable unit (px / cm / mm / in / pt)
- [x] **Save and open** an editable document file

---

## Phase 3 - Content

- [x] **Image support** - upload, drag-drop, paste from clipboard; opacity,
      flip and free resize
- [x] **Image crop and corner radius** — via clip/mask: crop to square,
      rounded, circle or inset, or clip to any shape on the canvas
- [x] **Icon library integration** - all 324 icons from the gallery, searchable
      and filterable, inlined as editable vectors. Fetched through the API's
      `/download-icon/` route, which is the only one that answers with CORS
      headers; the static `/icons/` path does not
- [x] **Text**: family, size, weight, italic, underline, alignment, letter
      spacing, rotation, and a per-item colour
- [ ] Line height, lists, and a curated web-font set (subset the files; do not
      ship whole families)
- [x] **Stroke styles** - dashed, dotted, dash-dot, cap and join
- [x] **Gradients & effects** - linear/radial fills, drop shadow, blur
- [x] **Export presets** - SVG (lossless, rebuilt from the model), PNG/JPG/WebP
      at 1x-4x, PDF via the lazily imported `jspdf`, and copy-to-clipboard
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

**Next action:** templates (Phase 4) - the last thing between a blank canvas
and a finished piece. The document file format added alongside the menu bar is
most of the groundwork: a template is a saved document with a thumbnail.
