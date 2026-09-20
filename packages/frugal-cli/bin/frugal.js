#!/usr/bin/env node

/**
 * frugal - install components, drawing templates and icons from the terminal.
 *
 * Zero dependencies: Node 18 brought `fetch`, and everything else here is
 * string handling. A tool that installs single-file components has no business
 * dragging in a dependency tree of its own.
 */

import { mkdir, writeFile, access } from "node:fs/promises";
import { dirname, join, resolve } from "node:path";
import { constants } from "node:fs";
import {
  BASE_URL,
  getComponent,
  getIconCode,
  getTemplate,
  iconFileName,
  listComponents,
  listIcons,
  listTemplates,
  registerDownload,
} from "../lib/api.js";
import { startMcpServer } from "../lib/mcp.js";

const VERSION = "1.0.0";

/* ------------------------------------------------------------- output */

const useColour = process.stdout.isTTY && !process.env.NO_COLOR;
const paint = (code, text) => (useColour ? `\u001b[${code}m${text}\u001b[0m` : text);

const bold = (text) => paint("1", text);
const dim = (text) => paint("2", text);
const cyan = (text) => paint("36", text);
const green = (text) => paint("32", text);
const red = (text) => paint("31", text);

const out = (line = "") => process.stdout.write(line + "\n");
const fail = (message) => {
  process.stderr.write(red("✖ ") + message + "\n");
  process.exitCode = 1;
};

/* ---------------------------------------------------------- arguments */

/** A tiny flag parser: `--dir ./ui`, `--force`, and bare positionals. */
function parseArgs(argv) {
  const flags = {};
  const positional = [];

  for (let i = 0; i < argv.length; i++) {
    const token = argv[i];

    if (token.startsWith("--")) {
      const [name, inline] = token.slice(2).split("=");
      const next = argv[i + 1];

      if (inline !== undefined) {
        flags[name] = inline;
      } else if (next && !next.startsWith("-")) {
        flags[name] = next;
        i++;
      } else {
        flags[name] = true;
      }
      continue;
    }

    positional.push(token);
  }

  return { flags, positional };
}

async function exists(path) {
  try {
    await access(path, constants.F_OK);
    return true;
  } catch {
    return false;
  }
}

/** Writes a file, refusing to clobber unless --force was passed. */
async function write(path, contents, { force }) {
  if (!force && (await exists(path))) {
    throw new Error(`${path} already exists. Pass --force to overwrite it.`);
  }

  await mkdir(dirname(path), { recursive: true });
  await writeFile(path, contents, "utf8");
}

/* ----------------------------------------------------------- commands */

async function cmdList({ flags }) {
  const { components, categories } = await listComponents({
    category: flags.category,
    search: flags.search,
  });

  if (flags.json) {
    out(JSON.stringify(components, null, 2));
    return;
  }

  if (!components.length) {
    out(dim("Nothing matched. Categories: " + categories.map((c) => c.slug).join(", ")));
    return;
  }

  const width = Math.max(...components.map((component) => component.slug.length));

  for (const component of components) {
    const tags = (component.categories || []).map((item) => item.slug).join(", ");
    out(`${cyan(component.slug.padEnd(width))}  ${component.name}  ${dim(tags)}`);
  }

  out("");
  out(dim(`${components.length} components · frugal add <slug>`));
}

async function cmdAdd({ flags, positional }) {
  const slug = positional[0];
  if (!slug) throw new Error("Which component? Try: frugal add users-directory-table");

  const component = await getComponent(slug);
  if (!component) throw new Error(`No component with the slug "${slug}".`);
  if (!component.source) throw new Error(`"${slug}" has no source file to install.`);

  const directory = resolve(process.cwd(), flags.dir || "frugal");
  const path = join(directory, `${component.slug}.html`);

  await write(path, component.source, { force: Boolean(flags.force) });
  await registerDownload(component.slug);

  out(`${green("✔")} ${bold(component.name)}`);
  out(`  ${dim("→")} ${path}`);
  if (component.tagline) out(`  ${dim(component.tagline)}`);
  out(`  ${dim("Open it with ?dir=rtl for the right-to-left layout, ?theme=dark for dark mode.")}`);
}

async function cmdShow({ positional }) {
  const slug = positional[0];
  if (!slug) throw new Error("Which component? Try: frugal show users-directory-table");

  const component = await getComponent(slug);
  if (!component) throw new Error(`No component with the slug "${slug}".`);

  out(bold(component.name) + dim(` (${component.slug})`));
  if (component.name_ar) out(dim(component.name_ar));
  out("");
  if (component.tagline) out(component.tagline);
  if (component.summary) out(dim(component.summary));
  out("");
  for (const feature of component.features || []) out(`  • ${feature.label}`);
  out("");
  out(dim(`tags: ${(component.tags || []).join(", ")}`));
  out(dim(`size: ${component.file?.size || 0} bytes · downloads: ${component.downloads || 0}`));
}

async function cmdTemplates({ flags }) {
  const { templates, categories } = await listTemplates({
    category: flags.category,
    search: flags.search,
  });

  if (flags.json) {
    out(JSON.stringify(templates, null, 2));
    return;
  }

  if (!templates.length) {
    out(dim("Nothing matched. Categories: " + categories.join(", ")));
    return;
  }

  const width = Math.max(...templates.map((template) => String(template.slug).length));

  for (const template of templates) {
    out(
      `${cyan(String(template.slug).padEnd(width))}  ${template.title}  ` +
        dim(`${template.category} · ${template.width}x${template.height}`),
    );
  }

  out("");
  out(dim(`${templates.length} templates · frugal template <slug>`));
}

async function cmdTemplate({ flags, positional }) {
  const slug = positional[0];
  if (!slug) throw new Error("Which template? Try: frugal template hexagon-badge-midnight");

  const template = await getTemplate(slug);
  if (!template) throw new Error(`No drawing template matching "${slug}".`);

  // Written in the editor's own document format, so the file opens directly
  // in the drawing board rather than needing a conversion step.
  const document = {
    format: "fruga-drawboard",
    version: 1,
    savedAt: new Date().toISOString(),
    width: template.document?.width,
    height: template.document?.height,
    background: template.document?.background,
    paths: template.document?.paths || [],
    textItems: template.document?.textItems || [],
  };

  const directory = resolve(process.cwd(), flags.dir || "frugal");
  const path = join(directory, `${template.slug}.fruga.json`);

  await write(path, JSON.stringify(document, null, 2), { force: Boolean(flags.force) });

  out(`${green("✔")} ${bold(template.title)}`);
  out(`  ${dim("→")} ${path}`);
  out(`  ${dim(`${document.paths.length} shapes · ${document.textItems.length} text items · open it in the drawing board`)}`);
}

async function cmdIcons({ flags, positional }) {
  const search = positional[0] || flags.search;
  const { icons } = await listIcons({ search, limit: flags.limit || 20 });

  if (flags.json) {
    out(JSON.stringify(icons, null, 2));
    return;
  }

  if (!icons.length) {
    out(dim("No icons matched."));
    return;
  }

  for (const icon of icons) {
    out(`${cyan(iconFileName(icon))}  ${icon.title}  ${dim(icon.style || "")}`);
  }

  out("");
  out(dim(`${icons.length} icons · frugal icon <file> [--jsx]`));
}

async function cmdIcon({ flags, positional }) {
  const file = positional[0];
  if (!file) throw new Error("Which icon? Run `frugal icons bell` to find one.");

  const code = await getIconCode(file, { jsx: Boolean(flags.jsx) });

  if (flags.dir) {
    const extension = flags.jsx ? "jsx" : "svg";
    const path = join(resolve(process.cwd(), flags.dir), `${file.replace(/\.[^.]+$/, "")}.${extension}`);
    await write(path, code, { force: Boolean(flags.force) });
    out(`${green("✔")} ${path}`);
    return;
  }

  out(code);
}

function cmdHelp() {
  out(`${bold("frugal")} ${dim(VERSION)} - components, drawing templates and icons from the terminal

${bold("Components")}
  frugal list [--category tables] [--search pricing] [--json]
  frugal add <slug> [--dir ./src/ui] [--force]
  frugal show <slug>

${bold("Drawing templates")}
  frugal templates [--category Logos] [--search badge]
  frugal template <slug|id> [--dir ./art] [--force]

${bold("Icons")}
  frugal icons [search] [--limit 20]
  frugal icon <file> [--jsx] [--dir ./icons]

${bold("For coding agents")}
  frugal mcp            Run the MCP server over stdio

${bold("Environment")}
  FRUGAL_API            API base URL (default ${BASE_URL})
  NO_COLOR              Disable colour

${dim("Every component is one self-contained HTML file: no build step, no dependencies.")}
`);
}

/* --------------------------------------------------------------- main */

const COMMANDS = {
  list: cmdList,
  add: cmdAdd,
  show: cmdShow,
  templates: cmdTemplates,
  template: cmdTemplate,
  icons: cmdIcons,
  icon: cmdIcon,
  mcp: () => startMcpServer(),
  help: cmdHelp,
};

async function main() {
  const [, , command, ...rest] = process.argv;

  if (!command || command === "--help" || command === "-h" || command === "help") {
    cmdHelp();
    return;
  }

  if (command === "--version" || command === "-v") {
    out(VERSION);
    return;
  }

  const handler = COMMANDS[command];
  if (!handler) {
    fail(`Unknown command "${command}". Run \`frugal help\` for the list.`);
    return;
  }

  await handler(parseArgs(rest));
}

main().catch((error) => fail(error.message));
