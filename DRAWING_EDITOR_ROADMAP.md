# Drawing editor — roadmap to a Canva-class tool

Target: turn `/Drower/Board` from an SVG shape editor into a real design
surface — multi-element documents, images, templates, export presets.

Each phase is independently shippable: the editor keeps working after every
one. **A phase is deleted from this file once it is finished**, so whatever
remains below is the work still outstanding.

---

## Where the editor stands today

Audited `sydev-front/src/page/site/drower/` after the Phase 2 / Phase 3 pass.

**What already works**

- 19 tools: select, pan, pen, line, arrow, curve, rect, circle, ellipse,
  triangle, diamond, star, pentagon, hexagon, octagon, text, plus place-image,
  grouping and boolean ops
- Raw `<svg>` canvas driven by React state, split across `utils/`, six hooks
  and eleven components
- Undo/redo (50 steps), layers panel with rename / reorder / lock / hide /
  duplicate, groups
- Per-element **fill, stroke, stroke width, opacity, dash, cap and join**;
  with nothing selected the same panel sets the style for the next shape
- Multi-select (marquee + Shift-click) that drags as one rigid group
- Align 6 ways, distribute on both axes, four z-order moves, flip, rotate
- Copy / cut / paste / duplicate, arrow-key nudge, right-click context menu
- Eight-handle resize with a rotation handle, Shift to keep the ratio, and a
  live size readout — works on **every** shape type, paths and images included
- Images: file picker, drag-and-drop and clipboard paste, stored as data URIs
- Artboard presets (icon / social / print), orientation swap, background colour
  including transparent
- Zoom to 10-800%, Ctrl+wheel zoom, fit-to-window, space-drag panning
- Autosave to `localStorage` with a recover-on-reload prompt
- Export: SVG, PNG, JPG, WebP at 1-4x, and PDF, plus copy-image-to-clipboard
- SVG import, keyboard shortcuts dialog

**What is missing — the honest gaps**

| Gap | Impact |
|---|---|
| No central store | State still lives in `DrawBoard.jsx` + hooks. It is now behind a shared `utils/` layer, so this is maintenance debt rather than a blocker |
| No smart guides | Alignment is button-driven; nothing snaps to other elements while dragging |
| Multi-select resizes one at a time | Dragging moves the group as one, but the handles only bind to a single selection |
| No gradients, shadows or blur | Flat fills and strokes only |
| No icon-library integration | The 324-icon gallery still cannot be placed on the canvas |
| No templates or server persistence | Every document starts blank, and autosave is per-browser |
| Rotated shapes hit-test as unrotated | Selection uses the axis-aligned box, so a heavily rotated shape has a slightly off click target |

---

## Phase 1 — Foundation

- [x] Settle the renderer decision — Option A, SVG stays
- [x] **Undo/redo**, 50 steps, on <kbd>Ctrl/Cmd+Z</kbd> and
      <kbd>Ctrl/Cmd+Shift+Z</kbd>, plus toolbar buttons (`hooks/useHistory.js`)
- [x] Split `DrawBoard.jsx` — geometry, styling, serialisation and resizing now
      live in `utils/`; arrange, clipboard, autosave and images in `hooks/`;
      the toolbar, inspector, menus and context menu in `components/`
- [ ] Define a serialisable document model as a first-class type. The autosave
      payload (`{ version, paths, textItems, width, height, background }`) is
      the de-facto schema — promote it and version it properly
- [ ] Move editor state into a store (**Zustand + Immer**)

**Done when:** every canvas action goes through one owner of state.

---

## Phase 2 — Editor essentials

- [x] **Layers panel**: reorder, rename, lock, hide, duplicate
- [ ] Nest layers into groups in the panel (groups exist, the tree does not)
- [x] **Copy / paste / duplicate** (<kbd>Ctrl+C/V/D</kbd>) and cut
- [ ] Clipboard across tabs — it is in-memory, so it does not survive a reload
- [x] **Alignment**: align 6 ways, distribute on both axes
- [ ] Smart guides against other elements and canvas centre
- [x] **Multi-select**: marquee drag, <kbd>Shift</kbd>+click, moves as one
- [ ] Transform a multi-selection as one (resize/rotate the whole group)
- [x] **Canvas presets**: icon, social and print sizes, custom, orientation swap
- [x] **Autosave to `localStorage`** plus a recover-on-reload prompt

---

## Phase 3 — Content

- [x] **Image support** — upload, drag-drop, paste from clipboard; opacity,
      flip and free resize
- [ ] Image crop and corner radius
- [ ] **Icon library integration** — pull from the existing 324-icon gallery
      straight onto the canvas. This is the feature only *this* product can
      offer, and it is why Option A matters
- [x] **Text**: family, size, weight, italic, underline, alignment, letter
      spacing, rotation, and a per-item colour
- [ ] Line height, lists, and a curated web-font set (subset the files; do not
      ship whole families)
- [x] **Stroke styles** — dashed, dotted, dash-dot, cap and join
- [ ] **Gradients & effects** — linear/radial fills, drop shadow, blur
- [x] **Export presets** — SVG (lossless, rebuilt from the model), PNG/JPG/WebP
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

**Next action:** smart guides and group transforms (the two Phase 2 gaps a
user notices first), then the icon-library integration from Phase 3.
