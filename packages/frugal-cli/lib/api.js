/**
 * The public Frugal API, wrapped.
 *
 * No dependencies and no authentication: every endpoint used here is public,
 * which is the whole reason a CLI can exist for this library at all. The base
 * URL is overridable so the same package can run against a local Laravel
 * instance during development.
 */

export const BASE_URL = (process.env.FRUGAL_API || "https://api.frugaldomain.site/api").replace(/\/+$/, "");

const TIMEOUT_MS = Number(process.env.FRUGAL_TIMEOUT || 20000);

async function request(path, { method = "GET", params } = {}) {
  const url = new URL(BASE_URL + path);

  for (const [key, value] of Object.entries(params || {})) {
    if (value !== undefined && value !== null && value !== "") {
      url.searchParams.set(key, String(value));
    }
  }

  let response;
  try {
    response = await fetch(url, {
      method,
      headers: { accept: "application/json" },
      signal: AbortSignal.timeout(TIMEOUT_MS),
    });
  } catch (error) {
    // A network failure here is almost always offline, a proxy, or a typo in
    // FRUGAL_API - say which URL failed rather than just "fetch failed".
    throw new Error(`Could not reach ${url.origin} (${error.name === "TimeoutError" ? "timed out" : error.message})`);
  }

  if (response.status === 404) {
    throw new Error(`Not found: ${url.pathname.replace("/api", "")}`);
  }
  if (!response.ok) {
    throw new Error(`${url.pathname} returned ${response.status}`);
  }

  return response.json();
}

/* ---------------------------------------------------------- components */

export async function listComponents({ category, search, type } = {}) {
  const payload = await request("/components", { params: { category, search, type } });
  const components = payload?.components || [];
  const categories = payload?.categories || [];

  // The endpoint filters by category server-side only when it is given one,
  // and search is not a server filter at all - so both are applied here as
  // well, which also makes the CLI behave the same against an older API.
  const term = String(search || "").trim().toLowerCase();

  const filtered = components.filter((component) => {
    if (category) {
      const slugs = (component.categories || []).map((item) => item.slug);
      if (!slugs.includes(category)) return false;
    }
    if (!term) return true;

    return [component.name, component.name_ar, component.tagline, ...(component.tags || [])]
      .filter(Boolean)
      .some((field) => String(field).toLowerCase().includes(term));
  });

  return { components: filtered, categories };
}

export async function getComponent(slug) {
  const payload = await request(`/components/${encodeURIComponent(slug)}`);
  return payload?.component || null;
}

/**
 * Counts a download.
 *
 * Called after a file is written, never before: a failed write should not
 * inflate the counter, and the counter is what the gallery sorts "popular"
 * by. A failure here is swallowed - nobody's install should break because a
 * statistic did not record.
 */
export async function registerDownload(slug) {
  try {
    await request(`/components/${encodeURIComponent(slug)}/download`, { method: "POST" });
    return true;
  } catch {
    return false;
  }
}

/* ---------------------------------------------------- drawing templates */

export async function listTemplates({ category, search } = {}) {
  const payload = await request("/drawing-templates", { params: { category, search } });
  return { templates: payload?.data || [], categories: payload?.categories || [] };
}

/** The list carries ids and slugs; the document endpoint takes the id. */
export async function getTemplate(idOrSlug) {
  const numeric = /^\d+$/.test(String(idOrSlug));

  if (!numeric) {
    const { templates } = await listTemplates({});
    const match = templates.find((template) => template.slug === idOrSlug);
    if (!match) throw new Error(`No drawing template with the slug "${idOrSlug}"`);
    idOrSlug = match.id;
  }

  const payload = await request(`/drawing-templates/${idOrSlug}`);
  return payload?.data || null;
}

/* --------------------------------------------------------------- icons */

export async function listIcons({ search, category, style, limit = 40 } = {}) {
  const payload = await request("/icons", {
    params: { search, category, style, per_page: Math.min(Number(limit) || 40, 100) },
  });

  return { icons: payload?.data || [], meta: payload?.meta || {} };
}

export async function getIconCode(fileName, { jsx = false } = {}) {
  const endpoint = jsx ? "/get-icon-jsx/" : "/get-icon-svg/";
  const payload = await request(endpoint + encodeURIComponent(fileName));

  if (!payload?.success) throw new Error(payload?.message || "That icon could not be read.");
  return payload.code;
}

/** The icon list returns paths; the code endpoints want the file name. */
export function iconFileName(icon) {
  const path = icon?.file_svg || icon?.icon_text || "";
  return String(path).split("/").pop();
}
