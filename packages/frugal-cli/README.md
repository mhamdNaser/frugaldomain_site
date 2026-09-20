# frugal

Install Frugal components, drawing templates and icons from the terminal - and
serve the same library to a coding agent over MCP.

Zero dependencies. Node 18 or newer.

```bash
npx frugal-cli add users-directory-table
```

---

## Components

Every component is **one self-contained HTML file**: styles and behaviour are
inside it, nothing is fetched at runtime, and there is no build step. It
carries both themes and mirrors correctly in right-to-left layouts.

```bash
# Browse
frugal list
frugal list --category forms
frugal list --search pricing

# Read the details before installing
frugal show pricing-cards

# Install into ./frugal (or anywhere)
frugal add pricing-cards
frugal add users-directory-table --dir ./src/ui
frugal add sign-in-form --dir ./src/ui --force
```

Categories: `tables`, `forms`, `dashboards`, `ui-elements` - fifty-plus each.

Once a file is installed, open it in a browser:

| URL | What you see |
|---|---|
| `file.html` | the default, light or dark by system preference |
| `file.html?theme=dark` | pinned dark |
| `file.html?dir=rtl` | the right-to-left layout |

## Drawing templates

Starter artwork for the vector editor - logos, social posts, badges, frames,
diagrams and basics. Each one downloads as a `.fruga.json` document that opens
directly in the drawing board as **editable geometry**, not a flattened image.

```bash
frugal templates --category Logos
frugal templates --search badge
frugal template hexagon-badge-midnight --dir ./art
```

## Icons

```bash
frugal icons bell
frugal icon bell.svg            # prints the SVG
frugal icon bell.svg --jsx      # prints a React component
frugal icon bell.svg --dir ./src/icons
```

---

## For coding agents (MCP)

`frugal mcp` speaks the Model Context Protocol over stdio, so an agent can
search the library and pull a component into the project itself - without
copying anything through the chat window.

### Claude Code

```bash
claude mcp add frugal -- npx -y frugal-cli mcp
```

### Cursor, or any client that reads a JSON config

```json
{
  "mcpServers": {
    "frugal": {
      "command": "npx",
      "args": ["-y", "frugal-cli", "mcp"]
    }
  }
}
```

### The tools it exposes

| Tool | What it does |
|---|---|
| `list_components` | Search the catalogue by category, name or tag |
| `get_component` | Fetch one component with its full HTML source |
| `list_drawing_templates` | Search the starter artwork |
| `get_drawing_template` | Fetch a template's document, ready to save as `.fruga.json` |
| `search_icons` | Find icons by name |
| `get_icon` | Fetch an icon as SVG or as a React component |

Then ask for what you need in plain language:

> Find a pricing table component and add it to `src/components`.

---

## Environment

| Variable | Purpose |
|---|---|
| `FRUGAL_API` | API base URL. Defaults to `https://api.frugaldomain.site/api`. |
| `FRUGAL_TIMEOUT` | Request timeout in milliseconds. Defaults to 20000. |
| `NO_COLOR` | Set to anything to disable coloured output. |

Point `FRUGAL_API` at a local instance to work against your own copy:

```bash
FRUGAL_API=http://127.0.0.1:8001/api frugal list
```

---

## Notes

- `add` refuses to overwrite an existing file unless you pass `--force`.
- Installing a component counts a download, which is what the gallery's
  "popular" sort uses. Nothing else is recorded - there is no account, no key
  and no telemetry.
- The MCP server writes protocol messages to stdout and everything else to
  stderr, so it can be piped safely.

MIT licensed. The library itself is free to use commercially, modify and ship
without attribution.
