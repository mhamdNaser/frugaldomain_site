#!/usr/bin/env node
/**
 * Generates the second-generation icon set: a matched outline/solid pair for
 * every glyph.
 *
 * The first-generation artwork in api/public/icons is Heroicons-style outline
 * only - 24x24, stroke-width 1.5, currentColor - so a design that needs a
 * filled weight has nothing to reach for. Every glyph here is authored once as
 * geometry and emitted twice: an outline file that strokes it and a solid file
 * that fills it.
 *
 * Output: api/public/icons/v2/<slug>-outline.svg and <slug>-solid.svg, plus a
 * manifest the seeder reads so the icon list is not hand-maintained.
 *
 *   node tools/generate-icons.cjs
 */

const fs = require("fs");
const path = require("path");

const OUT_DIR = path.join(__dirname, "..", "api", "public", "icons", "v2");
const MANIFEST = path.join(OUT_DIR, "manifest.json");

/**
 * Each glyph gives its geometry as a list of primitives. They are rendered
 * differently per style rather than being drawn twice:
 *
 *   outline - stroked at 1.5, never filled
 *   solid   - filled, never stroked; `hole: true` parts are punched out with
 *             the even-odd rule so a solid glyph still reads at small sizes
 *
 * A primitive is one of:
 *   { p: "<path data>" }                     a path
 *   { p: "...", hole: true }                 punched out of the solid weight
 *   { p: "...", outlineOnly: true }          drawn only in the outline weight
 *   { p: "...", solidOnly: true }            drawn only in the solid weight
 *   { c: [cx, cy, r] }                       circle
 *   { r: [x, y, w, h, rx] }                  rounded rectangle
 *   { l: [x1, y1, x2, y2] }                  line (outline weight only)
 */

const GLYPHS = [
  // ---- Arrows & Navigation -------------------------------------------------
  { slug: "arrow-up", title: "Arrow Up", cat: "arrows-and-navigation",
    tags: ["arrow", "up", "north", "direction"],
    parts: [{ p: "M12 19.5V4.5" }, { p: "M5.5 11 12 4.5 18.5 11" }] },
  { slug: "arrow-down", title: "Arrow Down", cat: "arrows-and-navigation",
    tags: ["arrow", "down", "south", "direction"],
    parts: [{ p: "M12 4.5v15" }, { p: "M5.5 13 12 19.5 18.5 13" }] },
  { slug: "arrow-left", title: "Arrow Left", cat: "arrows-and-navigation",
    tags: ["arrow", "left", "west", "back"],
    parts: [{ p: "M19.5 12h-15" }, { p: "M11 5.5 4.5 12 11 18.5" }] },
  { slug: "arrow-right", title: "Arrow Right", cat: "arrows-and-navigation",
    tags: ["arrow", "right", "east", "forward"],
    parts: [{ p: "M4.5 12h15" }, { p: "M13 5.5 19.5 12 13 18.5" }] },
  { slug: "chevron-up", title: "Chevron Up", cat: "arrows-and-navigation",
    tags: ["chevron", "up", "collapse"], parts: [{ p: "M4.5 15.5 12 8l7.5 7.5" }] },
  { slug: "chevron-down", title: "Chevron Down", cat: "arrows-and-navigation",
    tags: ["chevron", "down", "expand"], parts: [{ p: "M4.5 8.5 12 16l7.5-7.5" }] },
  { slug: "chevron-left", title: "Chevron Left", cat: "arrows-and-navigation",
    tags: ["chevron", "left", "previous"], parts: [{ p: "M15.5 4.5 8 12l7.5 7.5" }] },
  { slug: "chevron-right", title: "Chevron Right", cat: "arrows-and-navigation",
    tags: ["chevron", "right", "next"], parts: [{ p: "M8.5 4.5 16 12l-7.5 7.5" }] },
  { slug: "refresh", title: "Refresh", cat: "arrows-and-navigation",
    tags: ["refresh", "reload", "sync", "rotate"],
    parts: [
      { p: "M20 12a8 8 0 1 1-2.34-5.66" },
      { p: "M20 4v4.5h-4.5" },
    ] },
  { slug: "expand", title: "Expand", cat: "arrows-and-navigation",
    tags: ["expand", "fullscreen", "maximise"],
    parts: [
      { p: "M9 4.5H4.5V9" }, { p: "M15 4.5h4.5V9" },
      { p: "M9 19.5H4.5V15" }, { p: "M15 19.5h4.5V15" },
    ] },
  { slug: "collapse", title: "Collapse", cat: "arrows-and-navigation",
    tags: ["collapse", "minimise", "shrink"],
    parts: [
      { p: "M4.5 9H9V4.5" }, { p: "M19.5 9H15V4.5" },
      { p: "M4.5 15H9v4.5" }, { p: "M19.5 15H15v4.5" },
    ] },
  { slug: "swap", title: "Swap", cat: "arrows-and-navigation",
    tags: ["swap", "exchange", "transfer", "convert"],
    parts: [
      { p: "M4 8.5h13l-3.5-3.5" },
      { p: "M20 15.5H7l3.5 3.5" },
    ] },

  // ---- Interface & Layout --------------------------------------------------
  { slug: "menu", title: "Menu", cat: "interface-and-layout",
    tags: ["menu", "hamburger", "navigation", "list"],
    parts: [{ p: "M4 7h16" }, { p: "M4 12h16" }, { p: "M4 17h16" }] },
  { slug: "close", title: "Close", cat: "interface-and-layout",
    tags: ["close", "cross", "cancel", "dismiss"],
    parts: [{ p: "M6 6l12 12" }, { p: "M18 6 6 18" }] },
  { slug: "plus", title: "Plus", cat: "interface-and-layout",
    tags: ["plus", "add", "new", "create"],
    parts: [{ p: "M12 5v14" }, { p: "M5 12h14" }] },
  { slug: "minus", title: "Minus", cat: "interface-and-layout",
    tags: ["minus", "remove", "subtract"], parts: [{ p: "M5 12h14" }] },
  { slug: "grid", title: "Grid", cat: "interface-and-layout",
    tags: ["grid", "layout", "dashboard", "tiles"],
    parts: [
      { r: [4, 4, 7, 7, 1.5] }, { r: [13, 4, 7, 7, 1.5] },
      { r: [4, 13, 7, 7, 1.5] }, { r: [13, 13, 7, 7, 1.5] },
    ] },
  { slug: "list", title: "List", cat: "interface-and-layout",
    tags: ["list", "rows", "bullets"],
    parts: [
      { c: [5, 7, 1.2] }, { p: "M9.5 7H20" },
      { c: [5, 12, 1.2] }, { p: "M9.5 12H20" },
      { c: [5, 17, 1.2] }, { p: "M9.5 17H20" },
    ] },
  { slug: "columns", title: "Columns", cat: "interface-and-layout",
    tags: ["columns", "layout", "split"],
    parts: [{ r: [4, 4.5, 6.5, 15, 1.5] }, { r: [13.5, 4.5, 6.5, 15, 1.5] }] },
  { slug: "sidebar", title: "Sidebar", cat: "interface-and-layout",
    tags: ["sidebar", "panel", "drawer", "layout"],
    parts: [{ r: [3.5, 4.5, 17, 15, 2] }, { p: "M9.5 4.5v15", outlineOnly: true }] },
  { slug: "filter", title: "Filter", cat: "interface-and-layout",
    tags: ["filter", "funnel", "sort", "refine"],
    parts: [{ p: "M4 5.5h16l-6.2 7.3v5.4l-3.6 1.8v-7.2z" }] },
  { slug: "search", title: "Search", cat: "interface-and-layout",
    tags: ["search", "find", "magnifier", "zoom"],
    parts: [{ c: [10.5, 10.5, 6] }, { p: "M15 15l4.5 4.5" }] },
  { slug: "settings", title: "Settings", cat: "interface-and-layout",
    tags: ["settings", "gear", "preferences", "config"],
    parts: [
      { p: "M10.3 3.5h3.4l.5 2.4 2 1.16 2.3-.8 1.7 2.94-1.8 1.6v2.4l1.8 1.6-1.7 2.94-2.3-.8-2 1.16-.5 2.4h-3.4l-.5-2.4-2-1.16-2.3.8-1.7-2.94 1.8-1.6v-2.4L3.8 9.2l1.7-2.94 2.3.8 2-1.16z" },
      { c: [12, 12, 2.8], hole: true },
    ] },
  { slug: "sliders", title: "Sliders", cat: "interface-and-layout",
    tags: ["sliders", "adjust", "controls", "tune"],
    parts: [
      { p: "M4 8h16", outlineOnly: true }, { c: [9, 8, 2.2] },
      { p: "M4 16h16", outlineOnly: true }, { c: [15, 16, 2.2] },
    ] },
  { slug: "more-horizontal", title: "More Horizontal", cat: "interface-and-layout",
    tags: ["more", "ellipsis", "options", "menu"],
    parts: [{ c: [5.5, 12, 1.5] }, { c: [12, 12, 1.5] }, { c: [18.5, 12, 1.5] }] },
  { slug: "more-vertical", title: "More Vertical", cat: "interface-and-layout",
    tags: ["more", "ellipsis", "options", "kebab"],
    parts: [{ c: [12, 5.5, 1.5] }, { c: [12, 12, 1.5] }, { c: [12, 18.5, 1.5] }] },
  { slug: "drag-handle", title: "Drag Handle", cat: "interface-and-layout",
    tags: ["drag", "handle", "reorder", "move"],
    parts: [
      { c: [9, 6, 1.3] }, { c: [15, 6, 1.3] }, { c: [9, 12, 1.3] },
      { c: [15, 12, 1.3] }, { c: [9, 18, 1.3] }, { c: [15, 18, 1.3] },
    ] },

  // ---- Status & Alerts -----------------------------------------------------
  { slug: "check", title: "Check", cat: "status-and-alerts",
    tags: ["check", "tick", "done", "success"], parts: [{ p: "M4.5 12.5l5 5 10-11" }] },
  { slug: "check-circle", title: "Check Circle", cat: "status-and-alerts",
    tags: ["check", "circle", "success", "confirmed"],
    parts: [{ c: [12, 12, 8.5] }, { p: "M8 12.3l2.8 2.8L16 9.4", hole: true }] },
  { slug: "alert-circle", title: "Alert Circle", cat: "status-and-alerts",
    tags: ["alert", "warning", "error", "attention"],
    parts: [{ c: [12, 12, 8.5] }, { p: "M12 7.5v5.5", hole: true }, { c: [12, 16.3, 1], hole: true }] },
  { slug: "alert-triangle", title: "Alert Triangle", cat: "status-and-alerts",
    tags: ["alert", "warning", "caution", "danger"],
    parts: [
      { p: "M12 4.2 21 19.5H3z" },
      { p: "M12 10v4", hole: true }, { c: [12, 16.8, 0.9], hole: true },
    ] },
  { slug: "info", title: "Info", cat: "status-and-alerts",
    tags: ["info", "information", "help", "about"],
    parts: [{ c: [12, 12, 8.5] }, { p: "M12 11v5.5", hole: true }, { c: [12, 7.8, 1], hole: true }] },
  { slug: "help-circle", title: "Help Circle", cat: "status-and-alerts",
    tags: ["help", "question", "support", "faq"],
    parts: [
      { c: [12, 12, 8.5] },
      { p: "M9.6 9.6a2.4 2.4 0 1 1 2.4 2.7v1.4", hole: true },
      { c: [12, 16.6, 0.9], hole: true },
    ] },
  { slug: "loader", title: "Loader", cat: "status-and-alerts",
    tags: ["loader", "spinner", "loading", "progress"],
    parts: [
      { p: "M12 3.5v3.2" }, { p: "M12 17.3v3.2" },
      { p: "M3.5 12h3.2" }, { p: "M17.3 12h3.2" },
      { p: "M6 6l2.3 2.3" }, { p: "M15.7 15.7 18 18" },
      { p: "M18 6l-2.3 2.3" }, { p: "M8.3 15.7 6 18" },
    ] },

  // ---- Users & Accounts ----------------------------------------------------
  { slug: "user", title: "User", cat: "users-and-accounts",
    tags: ["user", "person", "profile", "account"],
    parts: [{ c: [12, 8, 3.8] }, { p: "M4.8 20a7.2 7.2 0 0 1 14.4 0" }] },
  { slug: "users", title: "Users", cat: "users-and-accounts",
    tags: ["users", "team", "group", "people"],
    parts: [
      { c: [9, 8, 3.4] }, { p: "M2.8 19.5a6.2 6.2 0 0 1 12.4 0" },
      { c: [17, 8.6, 2.6], outlineOnly: true },
      { p: "M16.4 13.6a5.6 5.6 0 0 1 4.8 5.9", outlineOnly: true },
      { c: [17, 8.6, 2.6], solidOnly: true },
      { p: "M15.6 13.6a5.6 5.6 0 0 1 5.6 5.9h-3.4a7.8 7.8 0 0 0-3.2-5.6z", solidOnly: true },
    ] },
  { slug: "user-plus", title: "User Plus", cat: "users-and-accounts",
    tags: ["user", "add", "invite", "signup"],
    parts: [
      { c: [10, 8, 3.6] }, { p: "M3.5 19.8a6.6 6.6 0 0 1 13 0" },
      { p: "M18 8.5v5", hole: true }, { p: "M15.5 11h5", hole: true },
    ] },
  { slug: "shield", title: "Shield", cat: "security-and-privacy",
    tags: ["shield", "protection", "secure", "guard"],
    parts: [{ p: "M12 3.5 19.5 6v6c0 4.4-3 7.3-7.5 8.5C7.5 19.3 4.5 16.4 4.5 12V6z" }] },
  { slug: "lock", title: "Lock", cat: "security-and-privacy",
    tags: ["lock", "secure", "private", "password"],
    parts: [
      { r: [4.8, 10.5, 14.4, 9.5, 2] },
      { p: "M8 10.5V8a4 4 0 0 1 8 0v2.5", outlineOnly: true },
      { p: "M8 10.5V8a4 4 0 0 1 8 0v2.5h-2V8a2 2 0 0 0-4 0v2.5z", solidOnly: true },
      { c: [12, 15.2, 1.3], hole: true },
    ] },
  { slug: "unlock", title: "Unlock", cat: "security-and-privacy",
    tags: ["unlock", "open", "access"],
    parts: [
      { r: [4.8, 10.5, 14.4, 9.5, 2] },
      { p: "M8 10.5V8a4 4 0 0 1 7.5-1.9", outlineOnly: true },
      { p: "M8 10.5V8a4 4 0 0 1 7.5-1.9l-1.7 1A2 2 0 0 0 10 8v2.5z", solidOnly: true },
      { c: [12, 15.2, 1.3], hole: true },
    ] },
  { slug: "eye", title: "Eye", cat: "security-and-privacy",
    tags: ["eye", "view", "visible", "preview"],
    parts: [{ p: "M2.5 12S6 6.3 12 6.3 21.5 12 21.5 12 18 17.7 12 17.7 2.5 12 2.5 12z" }, { c: [12, 12, 2.8], hole: true }] },
  { slug: "eye-off", title: "Eye Off", cat: "security-and-privacy",
    tags: ["eye", "hidden", "invisible", "private"],
    parts: [
      { p: "M2.5 12S6 6.3 12 6.3c1.5 0 2.9.36 4.1.93M21.5 12s-1.5 2.4-4.2 4.1" },
      { c: [12, 12, 2.8], outlineOnly: true },
      { p: "M4 4l16 16" },
    ] },
  { slug: "key", title: "Key", cat: "security-and-privacy",
    tags: ["key", "access", "password", "credential"],
    parts: [{ c: [8, 12, 4] }, { p: "M12 12h8.5v3M17 12v2.5" }] },

  // ---- Files & Documents ---------------------------------------------------
  { slug: "file", title: "File", cat: "files-and-documents",
    tags: ["file", "document", "page"],
    parts: [{ p: "M6 3.5h7.5L19 9v11.5H6z" }, { p: "M13.5 3.5V9H19", hole: true }] },
  { slug: "folder", title: "Folder", cat: "files-and-documents",
    tags: ["folder", "directory", "files"],
    parts: [{ p: "M3.5 6.5h6l2 2.5h9v10.5h-17z" }] },
  { slug: "folder-open", title: "Folder Open", cat: "files-and-documents",
    tags: ["folder", "open", "browse"],
    parts: [{ p: "M3.5 6.5h6l2 2.5h9v2h-17z" }, { p: "M3.5 11h17l-2 8.5h-15z" }] },
  { slug: "download", title: "Download", cat: "files-and-documents",
    tags: ["download", "save", "export", "arrow"],
    parts: [{ p: "M12 4v10" }, { p: "M7.5 10 12 14.5 16.5 10" }, { p: "M4.5 18.5h15" }] },
  { slug: "upload", title: "Upload", cat: "files-and-documents",
    tags: ["upload", "import", "arrow", "share"],
    parts: [{ p: "M12 14.5v-10" }, { p: "M7.5 9 12 4.5 16.5 9" }, { p: "M4.5 18.5h15" }] },
  { slug: "copy", title: "Copy", cat: "files-and-documents",
    tags: ["copy", "duplicate", "clipboard"],
    parts: [{ r: [8.5, 8.5, 11, 11, 2] }, { p: "M15.5 8.5v-3h-11v11h3", outlineOnly: true },
            { p: "M4.5 5.5h11v3h-7v7h-4z", solidOnly: true }] },
  { slug: "trash", title: "Trash", cat: "files-and-documents",
    tags: ["trash", "delete", "remove", "bin"],
    parts: [
      { p: "M4.5 6.5h15" },
      { p: "M6.5 6.5 7.5 20h9l1-13.5" },
      { p: "M9.5 6.5V4h5v2.5", outlineOnly: true },
      { p: "M9.5 6.5V4h5v2.5h-2V5.5h-1v1z", solidOnly: true },
    ] },
  { slug: "save", title: "Save", cat: "files-and-documents",
    tags: ["save", "disk", "store"],
    parts: [{ p: "M4.5 4.5h12L19.5 7.5v12h-15z" }, { r: [8, 4.5, 8, 5, 0.5], hole: true }, { r: [7.5, 13, 9, 6.5, 0.5], hole: true }] },
  { slug: "clipboard", title: "Clipboard", cat: "files-and-documents",
    tags: ["clipboard", "paste", "notes"],
    parts: [{ r: [5, 5, 14, 15, 2] }, { r: [9, 3, 6, 4, 1], hole: true }] },

  // ---- Communication -------------------------------------------------------
  { slug: "mail", title: "Mail", cat: "communication",
    tags: ["mail", "email", "message", "envelope"],
    parts: [{ r: [3, 5.5, 18, 13, 2] }, { p: "M3.6 7 12 13 20.4 7", hole: true }] },
  { slug: "message", title: "Message", cat: "communication",
    tags: ["message", "chat", "comment", "bubble"],
    parts: [{ p: "M4 5.5h16v10.5H9.5L5.5 19.5V16H4z" }] },
  { slug: "bell", title: "Bell", cat: "communication",
    tags: ["bell", "notification", "alert", "reminder"],
    parts: [
      { p: "M6.5 17V10.5a5.5 5.5 0 0 1 11 0V17l1.6 2H4.9z" },
      { p: "M10 20a2 2 0 0 0 4 0", hole: true },
    ] },
  { slug: "phone", title: "Phone", cat: "communication",
    tags: ["phone", "call", "contact", "telephone"],
    parts: [{ p: "M5 4.5h4l1.6 4-2 1.6a11 11 0 0 0 5.3 5.3l1.6-2 4 1.6v4h-1A14.5 14.5 0 0 1 4 6.5z" }] },
  { slug: "send", title: "Send", cat: "communication",
    tags: ["send", "paper plane", "submit", "share"],
    parts: [{ p: "M20.5 3.5 3.5 10.5l6.5 2.5 2.5 6.5z" }, { p: "M10 13.5 20.5 3.5", hole: true }] },
  { slug: "at-sign", title: "At Sign", cat: "communication",
    tags: ["at", "email", "mention", "handle"],
    parts: [
      { c: [12, 12, 3.4], outlineOnly: true },
      { p: "M15.4 8.6v4.6a2.8 2.8 0 0 0 5.1 1.4A9 9 0 1 0 17 20", outlineOnly: true },
      // The solid weight is the ring and the inner bowl as areas, so the
      // counter between them stays open at small sizes.
      { p: "M12 2.6A9.4 9.4 0 1 0 17.6 19.6l-1.2-1.6A7.4 7.4 0 1 1 19.4 12c0 1.5-.6 2.3-1.5 2.3s-1.5-.8-1.5-2.3V8.2h-2V12a3.4 3.4 0 0 0 6.3 1.9A9.4 9.4 0 0 0 12 2.6Z", solidOnly: true },
      { c: [12, 12, 3.4], solidOnly: true },
      { c: [12, 12, 1.6], hole: true, solidOnly: true },
    ] },

  // ---- Media & Playback ----------------------------------------------------
  { slug: "play", title: "Play", cat: "media-and-playback",
    tags: ["play", "start", "video", "media"],
    parts: [{ p: "M7.5 4.8 19 12 7.5 19.2z" }] },
  { slug: "pause", title: "Pause", cat: "media-and-playback",
    tags: ["pause", "stop", "media"],
    parts: [{ r: [7, 5, 3.6, 14, 1] }, { r: [13.4, 5, 3.6, 14, 1] }] },
  { slug: "stop", title: "Stop", cat: "media-and-playback",
    tags: ["stop", "halt", "media"], parts: [{ r: [6, 6, 12, 12, 2] }] },
  { slug: "camera", title: "Camera", cat: "media-and-playback",
    tags: ["camera", "photo", "picture", "capture"],
    parts: [{ p: "M3.5 7.5h4l1.5-2.5h6l1.5 2.5h4v12h-17z" }, { c: [12, 13, 3.6], hole: true }] },
  { slug: "image", title: "Image", cat: "media-and-playback",
    tags: ["image", "photo", "picture", "gallery"],
    parts: [
      { r: [3.5, 4.5, 17, 15, 2] },
      { c: [8.8, 10, 1.8], hole: true },
      { p: "M4 17.5 9.5 12l3.5 3.5L16.5 12l4 4.2", hole: true },
    ] },
  { slug: "video", title: "Video", cat: "media-and-playback",
    tags: ["video", "movie", "film", "record"],
    parts: [{ r: [3, 6, 12.5, 12, 2] }, { p: "M15.5 12l5.5-3.5v7z" }] },
  { slug: "music", title: "Music", cat: "media-and-playback",
    tags: ["music", "audio", "note", "sound"],
    parts: [{ p: "M9.5 17.5V5.5l9-2v12" }, { c: [7, 17.5, 2.5] }, { c: [16, 15.5, 2.5] }] },
  { slug: "volume", title: "Volume", cat: "media-and-playback",
    tags: ["volume", "sound", "speaker", "audio"],
    parts: [
      { p: "M4.5 9.5h3.5L12.5 6v12L8 14.5H4.5z" },
      { p: "M15.5 9.8a3.5 3.5 0 0 1 0 4.4", hole: true },
      { p: "M18 7.4a7 7 0 0 1 0 9.2", hole: true },
    ] },
  { slug: "mic", title: "Microphone", cat: "media-and-playback",
    tags: ["mic", "microphone", "record", "voice"],
    parts: [
      { r: [9, 3, 6, 11, 3] },
      { p: "M5.8 11.5a6.2 6.2 0 0 0 12.4 0", hole: true },
      { p: "M12 17.7V21", hole: true },
    ] },

  // ---- Commerce & Finance --------------------------------------------------
  { slug: "shopping-cart", title: "Shopping Cart", cat: "commerce-and-finance",
    tags: ["cart", "shop", "basket", "buy"],
    parts: [
      { p: "M2.5 4.5h2.8l2.4 10h9.4l2.4-7.5H6.4" },
      { c: [9, 19, 1.5] }, { c: [17, 19, 1.5] },
    ] },
  { slug: "tag", title: "Tag", cat: "commerce-and-finance",
    tags: ["tag", "label", "price", "discount"],
    parts: [{ p: "M4 4h7.5l8.5 8.5-7.5 7.5L4 11.5z" }, { c: [8.2, 8.2, 1.5], hole: true }] },
  { slug: "credit-card", title: "Credit Card", cat: "commerce-and-finance",
    tags: ["card", "payment", "credit", "checkout"],
    parts: [{ r: [2.5, 5, 19, 14, 2] }, { p: "M2.5 9.8h19", hole: true }] },
  { slug: "wallet", title: "Wallet", cat: "commerce-and-finance",
    tags: ["wallet", "money", "purse", "balance"],
    parts: [{ r: [3, 6, 18, 13, 2] }, { c: [17, 12.5, 1.4], hole: true }] },
  { slug: "receipt", title: "Receipt", cat: "commerce-and-finance",
    tags: ["receipt", "invoice", "bill", "order"],
    parts: [
      { p: "M5.5 3.5h13v17l-2.2-1.5-2.2 1.5-2.1-1.5-2.2 1.5-2.1-1.5-2.2 1.5z" },
      { p: "M9 8.5h6", hole: true }, { p: "M9 12.5h6", hole: true },
    ] },
  { slug: "gift", title: "Gift", cat: "commerce-and-finance",
    tags: ["gift", "present", "reward", "bonus"],
    parts: [
      { r: [3.5, 8.5, 17, 4, 1] }, { r: [5, 12.5, 14, 8, 1] },
      { p: "M12 8.5v12", hole: true },
      { p: "M12 8.5C10.5 5 8 4 6.8 5.2 5.6 6.4 7 8.5 12 8.5z" },
      { p: "M12 8.5c1.5-3.5 4-4.5 5.2-3.3 1.2 1.2-.2 3.3-5.2 3.3z" },
    ] },

  // ---- Charts & Data -------------------------------------------------------
  { slug: "chart-bar", title: "Chart Bar", cat: "charts-and-data",
    tags: ["chart", "bar", "analytics", "statistics"],
    parts: [
      { r: [4, 13, 4, 7, 1] }, { r: [10, 8.5, 4, 11.5, 1] }, { r: [16, 4.5, 4, 15.5, 1] },
    ] },
  { slug: "chart-line", title: "Chart Line", cat: "charts-and-data",
    tags: ["chart", "line", "trend", "growth"],
    parts: [{ p: "M3.5 19.5V4" }, { p: "M3.5 19.5H21" }, { p: "M6.5 16l4-5 3.5 3 5-7" }] },
  { slug: "chart-pie", title: "Chart Pie", cat: "charts-and-data",
    tags: ["chart", "pie", "share", "distribution"],
    parts: [{ p: "M12 3.5a8.5 8.5 0 1 0 8.5 8.5H12z" }, { p: "M14.5 3.9A8.5 8.5 0 0 1 20.1 9.5h-5.6z" }] },
  { slug: "database", title: "Database", cat: "charts-and-data",
    tags: ["database", "storage", "server", "data"],
    parts: [
      { p: "M4.5 6.5c0-1.7 3.4-3 7.5-3s7.5 1.3 7.5 3v11c0 1.7-3.4 3-7.5 3s-7.5-1.3-7.5-3z" },
      { p: "M4.5 6.5c0 1.7 3.4 3 7.5 3s7.5-1.3 7.5-3", hole: true },
      { p: "M4.5 12c0 1.7 3.4 3 7.5 3s7.5-1.3 7.5-3", hole: true },
    ] },
  { slug: "trending-up", title: "Trending Up", cat: "charts-and-data",
    tags: ["trending", "up", "growth", "increase"],
    parts: [{ p: "M3.5 17 9.5 11l3.5 3.5L20.5 7" }, { p: "M15.5 7h5v5" }] },

  // ---- Time & Calendar -----------------------------------------------------
  { slug: "clock", title: "Clock", cat: "time-and-calendar",
    tags: ["clock", "time", "schedule", "hour"],
    parts: [{ c: [12, 12, 8.5] }, { p: "M12 7.2V12l3.4 2.2", hole: true }] },
  { slug: "calendar", title: "Calendar", cat: "time-and-calendar",
    tags: ["calendar", "date", "schedule", "event"],
    parts: [
      { r: [3.5, 5, 17, 15, 2] },
      { p: "M3.5 9.5h17", hole: true },
      { p: "M8 3.5v3", outlineOnly: true }, { p: "M16 3.5v3", outlineOnly: true },
    ] },
  { slug: "timer", title: "Timer", cat: "time-and-calendar",
    tags: ["timer", "stopwatch", "countdown"],
    parts: [
      { c: [12, 13.5, 7.5] }, { p: "M12 9.5v4", hole: true },
      { p: "M9.5 3h5", outlineOnly: true },
    ] },
  { slug: "history", title: "History", cat: "time-and-calendar",
    tags: ["history", "recent", "undo", "past"],
    parts: [
      { p: "M3.6 12a8.4 8.4 0 1 0 2.5-6" },
      { p: "M3.5 4.5V10H9" },
      { p: "M12 8v4.3l3 1.8", hole: true },
    ] },

  // ---- Devices & Technology ------------------------------------------------
  { slug: "monitor", title: "Monitor", cat: "devices-and-technology",
    tags: ["monitor", "screen", "desktop", "display"],
    parts: [{ r: [2.5, 4, 19, 12.5, 2] }, { p: "M8 20h8", outlineOnly: true }, { p: "M12 16.5V20", outlineOnly: true }] },
  { slug: "smartphone", title: "Smartphone", cat: "devices-and-technology",
    tags: ["phone", "mobile", "device", "smartphone"],
    parts: [{ r: [6.5, 2.5, 11, 19, 2.5] }, { p: "M10.5 18.5h3", hole: true }] },
  { slug: "tablet", title: "Tablet", cat: "devices-and-technology",
    tags: ["tablet", "ipad", "device"],
    parts: [{ r: [4.5, 2.5, 15, 19, 2.5] }, { p: "M10.5 18.8h3", hole: true }] },
  { slug: "cpu", title: "CPU", cat: "devices-and-technology",
    tags: ["cpu", "processor", "chip", "hardware"],
    parts: [
      { r: [6, 6, 12, 12, 2] }, { r: [9.5, 9.5, 5, 5, 1], hole: true },
      { p: "M9 3v3", outlineOnly: true }, { p: "M15 3v3", outlineOnly: true },
      { p: "M9 18v3", outlineOnly: true }, { p: "M15 18v3", outlineOnly: true },
      { p: "M3 9h3", outlineOnly: true }, { p: "M3 15h3", outlineOnly: true },
      { p: "M18 9h3", outlineOnly: true }, { p: "M18 15h3", outlineOnly: true },
    ] },
  { slug: "wifi", title: "Wifi", cat: "devices-and-technology",
    tags: ["wifi", "signal", "network", "wireless"],
    parts: [
      { p: "M2.5 9.2a14 14 0 0 1 19 0" },
      { p: "M6 12.6a9 9 0 0 1 12 0" },
      { p: "M9.3 16a4.4 4.4 0 0 1 5.4 0" },
      { c: [12, 19.3, 1.1] },
    ] },
  { slug: "cloud", title: "Cloud", cat: "devices-and-technology",
    tags: ["cloud", "storage", "server", "sync"],
    parts: [{ p: "M7 18.5a4.2 4.2 0 0 1-.3-8.4 5.6 5.6 0 0 1 10.8-1.3A3.9 3.9 0 0 1 17.6 18.5z" }] },
  { slug: "code", title: "Code", cat: "devices-and-technology",
    tags: ["code", "developer", "programming", "brackets"],
    parts: [{ p: "M8.5 7 3.5 12l5 5" }, { p: "M15.5 7l5 5-5 5" }, { p: "M13.6 4.5 10.4 19.5" }] },
  { slug: "terminal", title: "Terminal", cat: "devices-and-technology",
    tags: ["terminal", "console", "command", "shell"],
    parts: [{ r: [2.5, 4.5, 19, 15, 2] }, { p: "M6.5 9.5 9.5 12l-3 2.5", hole: true }, { p: "M12.5 15h5", hole: true }] },

  // ---- Maps & Places -------------------------------------------------------
  { slug: "map-pin", title: "Map Pin", cat: "maps-and-places",
    tags: ["map", "pin", "location", "marker"],
    parts: [{ p: "M12 21.5S4.8 15 4.8 10.2a7.2 7.2 0 1 1 14.4 0C19.2 15 12 21.5 12 21.5z" }, { c: [12, 10, 2.6], hole: true }] },
  { slug: "globe", title: "Globe", cat: "maps-and-places",
    tags: ["globe", "world", "language", "international"],
    parts: [
      { c: [12, 12, 8.5] },
      { p: "M3.5 12h17", hole: true },
      { p: "M12 3.5a13 13 0 0 1 0 17 13 13 0 0 1 0-17z", hole: true },
    ] },
  { slug: "home", title: "Home", cat: "maps-and-places",
    tags: ["home", "house", "dashboard", "main"],
    parts: [{ p: "M3.5 11 12 3.5 20.5 11v9.5h-17z" }, { r: [9.5, 13.5, 5, 7, 0.5], hole: true }] },
  { slug: "building", title: "Building", cat: "maps-and-places",
    tags: ["building", "office", "company", "city"],
    parts: [
      { p: "M5 20.5V4.5h9v16" }, { p: "M14 10.5h5v10" },
      { p: "M8 8h3", hole: true }, { p: "M8 12h3", hole: true }, { p: "M8 16h3", hole: true },
    ] },
  { slug: "compass", title: "Compass", cat: "maps-and-places",
    tags: ["compass", "direction", "navigate", "explore"],
    parts: [{ c: [12, 12, 8.5] }, { p: "M15.5 8.5 13.5 13.5 8.5 15.5 10.5 10.5z", hole: true }] },

  // ---- Text & Editing ------------------------------------------------------
  { slug: "edit", title: "Edit", cat: "text-and-editing",
    tags: ["edit", "pencil", "write", "modify"],
    parts: [{ p: "M4 20h4l11-11-4-4L4 16z" }, { p: "M14 5.5 18.5 10", hole: true }] },
  { slug: "bold", title: "Bold", cat: "text-and-editing",
    tags: ["bold", "text", "format", "strong"],
    parts: [{ p: "M7 4.5h6a3.8 3.8 0 0 1 0 7.5H7z" }, { p: "M7 12h6.8a3.8 3.8 0 0 1 0 7.5H7z" }] },
  { slug: "italic", title: "Italic", cat: "text-and-editing",
    tags: ["italic", "text", "format", "emphasis"],
    parts: [{ p: "M10 4.5h7" }, { p: "M7 19.5h7" }, { p: "M14 4.5 10 19.5" }] },
  { slug: "align-left", title: "Align Left", cat: "text-and-editing",
    tags: ["align", "left", "text", "format"],
    parts: [{ p: "M4 6h16" }, { p: "M4 10.7h10" }, { p: "M4 15.3h16" }, { p: "M4 20h10" }] },
  { slug: "align-center", title: "Align Center", cat: "text-and-editing",
    tags: ["align", "center", "text", "format"],
    parts: [{ p: "M4 6h16" }, { p: "M7 10.7h10" }, { p: "M4 15.3h16" }, { p: "M7 20h10" }] },
  { slug: "link", title: "Link", cat: "text-and-editing",
    tags: ["link", "chain", "url", "hyperlink"],
    parts: [
      { p: "M10.5 13.5a4 4 0 0 0 5.7 0l2.8-2.8a4 4 0 0 0-5.7-5.7l-1.4 1.4" },
      { p: "M13.5 10.5a4 4 0 0 0-5.7 0L5 13.3a4 4 0 0 0 5.7 5.7l1.4-1.4" },
    ] },
  { slug: "type", title: "Type", cat: "text-and-editing",
    tags: ["type", "text", "font", "typography"],
    parts: [{ p: "M4.5 6.5V4.5h15v2" }, { p: "M12 4.5v15" }, { p: "M8.5 19.5h7" }] },

  // ---- Weather & Nature ----------------------------------------------------
  { slug: "sun", title: "Sun", cat: "weather-and-nature",
    tags: ["sun", "light", "day", "bright"],
    parts: [
      { c: [12, 12, 4.4] },
      { p: "M12 2.5v2.4", outlineOnly: true }, { p: "M12 19.1v2.4", outlineOnly: true },
      { p: "M2.5 12h2.4", outlineOnly: true }, { p: "M19.1 12h2.4", outlineOnly: true },
      { p: "M5.3 5.3 7 7", outlineOnly: true }, { p: "M17 17l1.7 1.7", outlineOnly: true },
      { p: "M18.7 5.3 17 7", outlineOnly: true }, { p: "M7 17l-1.7 1.7", outlineOnly: true },
      { p: "M12 2.5v2.4M12 19.1v2.4M2.5 12h2.4M19.1 12h2.4M5.3 5.3 7 7M17 17l1.7 1.7M18.7 5.3 17 7M7 17l-1.7 1.7", solidOnly: true },
    ] },
  { slug: "moon", title: "Moon", cat: "weather-and-nature",
    tags: ["moon", "night", "dark", "sleep"],
    parts: [{ p: "M20.5 14.2A8.8 8.8 0 0 1 9.8 3.5a8.8 8.8 0 1 0 10.7 10.7z" }] },
  { slug: "droplet", title: "Droplet", cat: "weather-and-nature",
    tags: ["droplet", "water", "rain", "liquid"],
    parts: [{ p: "M12 3.5s6 6.7 6 10.3a6 6 0 0 1-12 0C6 10.2 12 3.5 12 3.5z" }] },
  { slug: "leaf", title: "Leaf", cat: "weather-and-nature",
    tags: ["leaf", "nature", "eco", "plant"],
    parts: [{ p: "M20 4c0 9-5.2 13.5-11.5 13.5A4.5 4.5 0 0 1 4 13C4 7.5 12 4 20 4z" }, { p: "M4.5 20 12 12", hole: true }] },
  { slug: "star", title: "Star", cat: "objects-and-misc",
    tags: ["star", "favourite", "rating", "bookmark"],
    parts: [{ p: "M12 3.8l2.6 5.4 5.9.8-4.3 4.2 1 5.9-5.2-2.8-5.2 2.8 1-5.9L3.5 10l5.9-.8z" }] },
  { slug: "heart", title: "Heart", cat: "objects-and-misc",
    tags: ["heart", "love", "like", "favourite"],
    parts: [{ p: "M12 20.3 4.6 13a4.8 4.8 0 0 1 7.4-6 4.8 4.8 0 0 1 7.4 6z" }] },
  { slug: "bookmark", title: "Bookmark", cat: "objects-and-misc",
    tags: ["bookmark", "save", "read later"],
    parts: [{ p: "M6.5 3.5h11v17L12 16.5 6.5 20.5z" }] },
  { slug: "flag", title: "Flag", cat: "objects-and-misc",
    tags: ["flag", "report", "milestone"],
    parts: [{ p: "M5.5 20.5V4" }, { p: "M5.5 4.5h13l-2.5 4 2.5 4h-13z" }] },
  { slug: "zap", title: "Zap", cat: "objects-and-misc",
    tags: ["zap", "lightning", "fast", "energy"],
    parts: [{ p: "M13.5 2.5 5 13.5h6L10.5 21.5 19 10.5h-6z" }] },
  { slug: "package", title: "Package", cat: "commerce-and-finance",
    tags: ["package", "box", "shipping", "delivery"],
    parts: [
      { p: "M12 3 20.5 7.5v9L12 21 3.5 16.5v-9z" },
      { p: "M3.5 7.5 12 12l8.5-4.5", hole: true },
      { p: "M12 12v9", hole: true },
    ] },
];

// ---------------------------------------------------------------------------

const round = (n) => Math.round(n * 100) / 100;

const partToPath = (part) => {
  if (part.p) return part.p;
  if (part.c) {
    const [cx, cy, r] = part.c;
    // Two arcs, so a circle can live in the same `d` as everything else and
    // participate in the even-odd fill rule.
    return `M${round(cx - r)} ${round(cy)}a${r} ${r} 0 1 0 ${round(r * 2)} 0a${r} ${r} 0 1 0 ${round(-r * 2)} 0Z`;
  }
  if (part.r) {
    const [x, y, w, h, rx = 0] = part.r;
    if (!rx) return `M${x} ${y}h${w}v${h}h${-w}Z`;
    return (
      `M${round(x + rx)} ${y}h${round(w - rx * 2)}a${rx} ${rx} 0 0 1 ${rx} ${rx}` +
      `v${round(h - rx * 2)}a${rx} ${rx} 0 0 1 ${-rx} ${rx}` +
      `h${round(-(w - rx * 2))}a${rx} ${rx} 0 0 1 ${-rx} ${-rx}` +
      `v${round(-(h - rx * 2))}a${rx} ${rx} 0 0 1 ${rx} ${-rx}Z`
    );
  }
  if (part.l) {
    const [x1, y1, x2, y2] = part.l;
    return `M${x1} ${y1}L${x2} ${y2}`;
  }
  return null;
};

const buildOutline = (glyph) => {
  const body = glyph.parts
    .filter((part) => !part.solidOnly)
    .map((part) => {
      const d = partToPath(part);
      return d ? `  <path stroke-linecap="round" stroke-linejoin="round" d="${d}" />` : null;
    })
    .filter(Boolean)
    .join("\n");

  return (
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" ` +
    `stroke="currentColor" stroke-width="1.5" aria-hidden="true">\n${body}\n</svg>\n`
  );
};

const buildSolid = (glyph) => {
  const filled = glyph.parts.filter((part) => !part.outlineOnly);

  const isArea = (part) => part.c || part.r || /[Zz]\s*$/.test(part.p || "");

  // Closed shapes are filled. Everything else is an open run, which carries no
  // area and so cannot be filled at all.
  const areas = filled.filter((part) => isArea(part) && !(part.hole && !isArea(part)));
  const openRuns = filled.filter((part) => !isArea(part));

  // An open run marked as a hole is interior detail - the tick inside a circle,
  // the stem of an exclamation mark. Stroking it in currentColor over a
  // same-coloured fill would make it vanish, so it is knocked out through a
  // mask instead, which is what gives a solid glyph its counters.
  const knockouts = openRuns.filter((part) => part.hole);
  const overlays = openRuns.filter((part) => !part.hole);

  const maskId = `${glyph.slug}-cut`;
  const lines = [];

  if (knockouts.length && areas.length) {
    const cuts = knockouts
      .map(partToPath)
      .filter(Boolean)
      .map(
        (d) =>
          `      <path stroke="#000" stroke-width="2.2" stroke-linecap="round" ` +
          `stroke-linejoin="round" fill="none" d="${d}" />`
      )
      .join("\n");
    lines.push(
      `  <defs>\n    <mask id="${maskId}">\n` +
      `      <rect width="24" height="24" fill="#fff" />\n${cuts}\n` +
      `    </mask>\n  </defs>`
    );
  }

  if (areas.length) {
    const d = areas.map(partToPath).filter(Boolean).join(" ");
    const mask = knockouts.length ? ` mask="url(#${maskId})"` : "";
    lines.push(
      `  <path fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"${mask} d="${d}" />`
    );
  } else {
    // A glyph made only of open runs - arrows, chevrons - has no area to fill,
    // so its solid weight is the same strokes drawn heavier.
    for (const part of [...knockouts, ...overlays]) {
      const d = partToPath(part);
      if (d) {
        lines.push(
          `  <path fill="none" stroke="currentColor" stroke-width="2.4" ` +
          `stroke-linecap="round" stroke-linejoin="round" d="${d}" />`
        );
      }
    }
  }

  for (const part of overlays) {
    if (!areas.length) break; // already emitted above
    const d = partToPath(part);
    if (d) {
      lines.push(
        `  <path fill="none" stroke="currentColor" stroke-width="2.4" ` +
        `stroke-linecap="round" stroke-linejoin="round" d="${d}" />`
      );
    }
  }

  return (
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">\n` +
    `${lines.join("\n")}\n</svg>\n`
  );
};

function main() {
  fs.mkdirSync(OUT_DIR, { recursive: true });

  const seen = new Set();
  const manifest = [];

  for (const glyph of GLYPHS) {
    if (seen.has(glyph.slug)) {
      throw new Error(`Duplicate slug: ${glyph.slug}`);
    }
    seen.add(glyph.slug);

    for (const style of ["outline", "solid"]) {
      const svg = style === "outline" ? buildOutline(glyph) : buildSolid(glyph);
      const name = `${glyph.slug}-${style}.svg`;
      fs.writeFileSync(path.join(OUT_DIR, name), svg, "utf8");

      manifest.push({
        slug: `${glyph.slug}-${style}`,
        title: `${glyph.title} ${style === "solid" ? "Solid" : "Outline"}`,
        category: glyph.cat,
        style,
        file: `icons/v2/${name}`,
        tags: [...glyph.tags, style],
      });
    }
  }

  fs.writeFileSync(MANIFEST, JSON.stringify(manifest, null, 2) + "\n", "utf8");

  console.log(`glyphs: ${GLYPHS.length}`);
  console.log(`files written: ${manifest.length} (${GLYPHS.length} x 2 styles)`);
  console.log(`categories: ${new Set(GLYPHS.map((g) => g.cat)).size}`);
  console.log(`output: ${OUT_DIR}`);
}

main();
