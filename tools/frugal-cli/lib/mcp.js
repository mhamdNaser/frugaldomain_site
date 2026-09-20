/**
 * An MCP server over stdio, so a coding agent can pull components, drawing
 * templates and icons into a project without the developer leaving the editor.
 *
 * The protocol is JSON-RPC 2.0 with one message per line. That is small
 * enough to implement directly, which keeps this package at zero
 * dependencies - the same rule the component library follows.
 *
 * One rule matters above all here: **stdout carries protocol messages and
 * nothing else.** A stray console.log corrupts the stream and the client
 * disconnects with an unhelpful parse error, so every diagnostic goes to
 * stderr.
 */

import {
  getComponent,
  getIconCode,
  getTemplate,
  iconFileName,
  listComponents,
  listIcons,
  listTemplates,
} from "./api.js";

const SERVER = { name: "frugal", version: "1.0.0" };
const DEFAULT_PROTOCOL = "2025-06-18";

const TOOLS = [
  {
    name: "list_components",
    description:
      "List ready-made interface components (tables, forms, dashboards, UI elements). " +
      "Each is a single self-contained HTML file with no dependencies. " +
      "Use this to find a component before fetching its source with get_component.",
    inputSchema: {
      type: "object",
      properties: {
        category: {
          type: "string",
          description: "Filter by category slug: tables, forms, dashboards or ui-elements.",
        },
        search: {
          type: "string",
          description: "Match against the name, tagline and tags, e.g. \"pricing\" or \"dark\".",
        },
        limit: { type: "number", description: "Maximum rows to return. Defaults to 40." },
      },
      additionalProperties: false,
    },
  },
  {
    name: "get_component",
    description:
      "Fetch one component by slug, including its full HTML source, so it can be written " +
      "into the project. The file is self-contained: styles and behaviour are inside it.",
    inputSchema: {
      type: "object",
      properties: { slug: { type: "string", description: "The component slug, e.g. users-directory-table." } },
      required: ["slug"],
      additionalProperties: false,
    },
  },
  {
    name: "list_drawing_templates",
    description:
      "List starter templates for the vector editor - logos, social posts, badges, frames, " +
      "diagrams and basics. Each is editable geometry, not a flattened image.",
    inputSchema: {
      type: "object",
      properties: {
        category: { type: "string", description: "Logos, Social, Badges, Frames, Diagrams or Basics." },
        search: { type: "string", description: "Match against the template title." },
        limit: { type: "number", description: "Maximum rows to return. Defaults to 40." },
      },
      additionalProperties: false,
    },
  },
  {
    name: "get_drawing_template",
    description:
      "Fetch one drawing template's document by slug or id. The document is the editor's own " +
      "format: paths, text items, width, height and background - ready to write as a .fruga.json file.",
    inputSchema: {
      type: "object",
      properties: { slug: { type: "string", description: "The template slug or numeric id." } },
      required: ["slug"],
      additionalProperties: false,
    },
  },
  {
    name: "search_icons",
    description: "Search the icon library. Returns names and the file name needed by get_icon.",
    inputSchema: {
      type: "object",
      properties: {
        search: { type: "string", description: "What the icon depicts, e.g. \"bell\" or \"cart\"." },
        limit: { type: "number", description: "Maximum rows to return. Defaults to 20." },
      },
      additionalProperties: false,
    },
  },
  {
    name: "get_icon",
    description: "Fetch one icon as SVG markup, or as a React component when jsx is true.",
    inputSchema: {
      type: "object",
      properties: {
        file: { type: "string", description: "The icon file name from search_icons." },
        jsx: { type: "boolean", description: "Return a React component instead of raw SVG." },
      },
      required: ["file"],
      additionalProperties: false,
    },
  },
];

/* ------------------------------------------------------------ handlers */

async function callTool(name, args = {}) {
  switch (name) {
    case "list_components": {
      const { components } = await listComponents({
        category: args.category,
        search: args.search,
      });
      const rows = components.slice(0, Number(args.limit) || 40).map((component) => ({
        slug: component.slug,
        name: component.name,
        name_ar: component.name_ar,
        tagline: component.tagline,
        categories: (component.categories || []).map((item) => item.slug),
        tags: component.tags,
        downloads: component.downloads,
      }));

      return {
        found: components.length,
        showing: rows.length,
        components: rows,
        next: "Call get_component with a slug to fetch the file.",
      };
    }

    case "get_component": {
      const component = await getComponent(args.slug);
      if (!component) throw new Error(`No component with the slug "${args.slug}".`);

      return {
        slug: component.slug,
        name: component.name,
        name_ar: component.name_ar,
        tagline: component.tagline,
        summary: component.summary,
        type: component.type,
        tags: component.tags,
        features: (component.features || []).map((feature) => feature.label),
        suggested_filename: `${component.slug}.html`,
        notes:
          "Self-contained: no build step, no dependencies. Open it with ?dir=rtl to see the " +
          "right-to-left layout, or ?theme=dark for the dark theme.",
        source: component.source,
      };
    }

    case "list_drawing_templates": {
      const { templates } = await listTemplates({
        category: args.category,
        search: args.search,
      });
      const rows = templates.slice(0, Number(args.limit) || 40).map((template) => ({
        id: template.id,
        slug: template.slug,
        title: template.title,
        category: template.category,
        size: `${template.width}x${template.height}`,
        tags: template.tags,
      }));

      return { found: templates.length, showing: rows.length, templates: rows };
    }

    case "get_drawing_template": {
      const template = await getTemplate(args.slug);
      if (!template) throw new Error(`No drawing template matching "${args.slug}".`);

      return {
        slug: template.slug,
        title: template.title,
        category: template.category,
        suggested_filename: `${template.slug}.fruga.json`,
        document: {
          format: "fruga-drawboard",
          version: 1,
          width: template.document?.width,
          height: template.document?.height,
          background: template.document?.background,
          paths: template.document?.paths,
          textItems: template.document?.textItems,
        },
      };
    }

    case "search_icons": {
      const { icons } = await listIcons({ search: args.search, limit: args.limit || 20 });
      return {
        icons: icons.map((icon) => ({
          title: icon.title,
          style: icon.style,
          category: icon.category_name,
          file: iconFileName(icon),
        })),
      };
    }

    case "get_icon": {
      const code = await getIconCode(args.file, { jsx: Boolean(args.jsx) });
      return { file: args.file, format: args.jsx ? "jsx" : "svg", code };
    }

    default:
      throw new Error(`Unknown tool: ${name}`);
  }
}

/* ----------------------------------------------------------- transport */

function send(message) {
  process.stdout.write(JSON.stringify(message) + "\n");
}

function reply(id, result) {
  send({ jsonrpc: "2.0", id, result });
}

function replyError(id, code, message) {
  send({ jsonrpc: "2.0", id, error: { code, message } });
}

async function handle(message) {
  const { id, method, params } = message;

  // A notification has no id and must never be answered.
  const isNotification = id === undefined || id === null;

  switch (method) {
    case "initialize":
      reply(id, {
        // Echo the client's protocol version when it names one, so a newer or
        // older client is not refused over a version string.
        protocolVersion: params?.protocolVersion || DEFAULT_PROTOCOL,
        capabilities: { tools: {} },
        serverInfo: SERVER,
      });
      return;

    case "notifications/initialized":
    case "notifications/cancelled":
      return;

    case "ping":
      if (!isNotification) reply(id, {});
      return;

    case "tools/list":
      reply(id, { tools: TOOLS });
      return;

    case "tools/call": {
      const name = params?.name;
      try {
        const result = await callTool(name, params?.arguments || {});
        reply(id, {
          content: [{ type: "text", text: JSON.stringify(result, null, 2) }],
        });
      } catch (error) {
        // A tool failure is reported inside the result, not as a protocol
        // error: the model should see it and can retry with different input.
        reply(id, {
          content: [{ type: "text", text: `Error: ${error.message}` }],
          isError: true,
        });
      }
      return;
    }

    default:
      if (!isNotification) replyError(id, -32601, `Method not found: ${method}`);
  }
}

export function startMcpServer() {
  process.stderr.write(`frugal mcp server ready (${SERVER.version})\n`);

  let buffer = "";

  process.stdin.setEncoding("utf8");
  process.stdin.on("data", (chunk) => {
    buffer += chunk;

    let newline;
    while ((newline = buffer.indexOf("\n")) !== -1) {
      const line = buffer.slice(0, newline).trim();
      buffer = buffer.slice(newline + 1);
      if (!line) continue;

      let message;
      try {
        message = JSON.parse(line);
      } catch {
        replyError(null, -32700, "Parse error");
        continue;
      }

      handle(message).catch((error) => {
        replyError(message?.id ?? null, -32603, error.message);
      });
    }
  });

  process.stdin.on("end", () => process.exit(0));
}

export { TOOLS };
