/* Manex-specific adapter, layered on top of the shared demo-runtime.
 *
 * demo-runtime.js is copied verbatim from the other previews, so it is kept
 * untouched here. Two things about Manex's auth contract differ from what the
 * shared runtime emits, and both are patched in this thin wrapper instead:
 *
 *  1. The runtime's shapeUser() returns a fixed set of fields. Manex reads
 *     `user.role`, `user.job_title`, `user.branch`, `user.department` and
 *     `user.two_factor_enabled` (sidebar scope line, top bar, circular
 *     audiences, the 2FA nudge). Those are dropped by shapeUser, so they are
 *     merged back in here from the seeded user record.
 *
 *  2. App.jsx does `api.me().then(setUser)` — it treats the WHOLE response as
 *     the user object, while the runtime answers /auth/me with an envelope
 *     ({user, data, modules, permissions}). Unpatched, a restored session
 *     yields a `user` with no `.name`, and TopBar's `user.name.charAt(0)`
 *     throws. The response is flattened so both the envelope keys and the
 *     user's own fields are present on one object.
 *
 * Only /auth/login and /auth/me responses are touched; everything else passes
 * through untouched. Still entirely client-side — no request leaves the page.
 */
(function () {
  "use strict";

  var seed = typeof window.__frugaDemoSeed === "function" ? window.__frugaDemoSeed() : null;
  if (!seed) return;

  function seededUser(email) {
    var key = String(email || "").toLowerCase();
    return (seed.users || []).filter(function (u) {
      return String(u.username).toLowerCase() === key || String(u.email).toLowerCase() === key;
    })[0] || null;
  }

  /** Merges the seeded profile fields onto whatever the runtime shaped. */
  function enrich(user) {
    if (!user || typeof user !== "object") return user;
    var full = seededUser(user.email || user.username);
    if (!full) return user;
    var out = {};
    Object.keys(user).forEach(function (k) { out[k] = user[k]; });
    [
      "role", "role_label", "job_title", "branch", "branch_id", "department",
      "department_id", "phone", "avatar_url", "two_factor_enabled", "hired_at",
      "employment_type", "name", "permissions", "roles",
    ].forEach(function (k) {
      if (full[k] !== undefined) out[k] = full[k];
    });
    return out;
  }

  function isAuthPath(url) {
    return /\/api\/auth\/(login|me)(\?|$)/.test(String(url));
  }

  function patchBody(text) {
    var body;
    try {
      body = JSON.parse(text);
    } catch (e) {
      return text;
    }
    if (!body || typeof body !== "object") return text;

    var user = enrich(body.user || body.data);
    if (!user) return text;

    // Flatten: keep the envelope keys AND lift the user's own fields up, so
    // both `data.user` (login) and `data.name` (me) resolve correctly.
    var out = {};
    Object.keys(user).forEach(function (k) { out[k] = user[k]; });
    Object.keys(body).forEach(function (k) {
      if (k !== "user" && k !== "data") out[k] = body[k];
    });
    out.user = user;
    out.data = user;
    if (body.permissions === undefined) out.permissions = user.permissions || [];
    if (body.modules === undefined) out.modules = user.modules || seed.modules || [];
    return JSON.stringify(out);
  }

  /* ---- fetch ---- */
  var innerFetch = window.fetch;
  window.fetch = function (input, init) {
    var url = typeof input === "string" ? input : (input && input.url) || "";
    var promise = innerFetch.call(window, input, init);
    if (!isAuthPath(url)) return promise;
    return promise.then(function (response) {
      return response.text().then(function (text) {
        return new Response(patchBody(text), {
          status: response.status,
          headers: { "Content-Type": "application/json" },
        });
      });
    });
  };

  /* ---- XMLHttpRequest ---- */
  var InnerXHR = window.XMLHttpRequest;
  function AdaptedXHR() {
    var xhr = new InnerXHR();
    var open = xhr.open;
    xhr.open = function (method, url) {
      this.__authPath = isAuthPath(url);
      return open.apply(this, arguments);
    };
    var send = xhr.send;
    xhr.send = function () {
      if (this.__authPath) {
        var self = this;
        var fire = function () {
          if (self.readyState === 4 && !self.__patched) {
            self.__patched = true;
            try {
              var patched = patchBody(self.responseText);
              Object.defineProperty(self, "responseText", { value: patched, configurable: true });
              Object.defineProperty(self, "response", {
                value: self.responseType === "json" ? JSON.parse(patched) : patched,
                configurable: true,
              });
            } catch (e) { /* leave the original response in place */ }
          }
        };
        var prevStateChange = this.onreadystatechange;
        this.onreadystatechange = function () {
          fire();
          if (prevStateChange) prevStateChange.apply(this, arguments);
        };
        var prevLoad = this.onload;
        this.onload = function () {
          fire();
          if (prevLoad) prevLoad.apply(this, arguments);
        };
      }
      return send.apply(this, arguments);
    };
    return xhr;
  }
  AdaptedXHR.UNSENT = 0;
  AdaptedXHR.OPENED = 1;
  AdaptedXHR.HEADERS_RECEIVED = 2;
  AdaptedXHR.LOADING = 3;
  AdaptedXHR.DONE = 4;
  window.XMLHttpRequest = AdaptedXHR;
})();
