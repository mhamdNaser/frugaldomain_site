/* Fruga demo runtime — injected only into the published preview copies.
 *
 * These applications normally talk to a Laravel API backed by PostgreSQL. The
 * preview has no backend, so without this shim the login screen would reject
 * every attempt and the visitor would see nothing.
 *
 * It intercepts XMLHttpRequest and fetch before the bundle boots, answers the
 * API calls from a seed file plus whatever the visitor changes, and keeps that
 * state in localStorage so edits survive a reload. Nothing leaves the browser.
 *
 * The original projects are not modified in any way; this file exists only in
 * the uploaded copy.
 */
(function () {
  "use strict";

  var CFG = window.__FRUGA_DEMO__ || {};
  var SEED_URL = CFG.seedUrl || "./demo-data.json";
  var STORE_KEY = "fruga.demo." + (CFG.slug || "app");

  var seed = null;
  var store = null;
  var ready = false;

  /* ---------- persistence ---------- */

  function load() {
    try {
      var raw = localStorage.getItem(STORE_KEY);
      if (raw) return JSON.parse(raw);
    } catch (e) {
      /* private mode or blocked storage — fall back to memory only */
    }
    return null;
  }

  function save() {
    try {
      localStorage.setItem(STORE_KEY, JSON.stringify(store));
    } catch (e) {
      /* quota or blocked — the session still works, it just will not persist */
    }
  }

  function resetStore() {
    store = JSON.parse(JSON.stringify(seed.state || {}));
    save();
  }

  /* ---------- seed loading (synchronous, before the app boots) ---------- */

  function loadSeedSync() {
    var xhr = new window.__demoXHR();
    xhr.open("GET", SEED_URL, false); // deliberately synchronous: the bundle
    xhr.send(null); //                  may issue its first call immediately
    seed = JSON.parse(xhr.responseText);
    store = load() || JSON.parse(JSON.stringify(seed.state || {}));
    ready = true;
  }

  /* ---------- helpers ---------- */

  function pathOf(url) {
    var s = String(url);
    try {
      return new URL(s, window.location.href).pathname.replace(/\/+$/, "") || "/";
    } catch (e) {
      return s.split("?")[0].replace(/\/+$/, "") || "/";
    }
  }

  // "/api/donors/12" -> ["donors", "12"]
  function segments(path) {
    var p = path.replace(/^.*?\/api\/?/, "");
    return p ? p.split("/").filter(Boolean) : [];
  }

  function paginate(rows, query) {
    var perPage = parseInt(query.per_page || query.perPage || "15", 10) || 15;
    var page = parseInt(query.page || "1", 10) || 1;
    var q = (query.q || query.search || "").toString().toLowerCase();

    var filtered = rows;
    if (q) {
      filtered = rows.filter(function (row) {
        return JSON.stringify(row).toLowerCase().indexOf(q) !== -1;
      });
    }

    var start = (page - 1) * perPage;
    return {
      data: filtered.slice(start, start + perPage),
      meta: {
        current_page: page,
        per_page: perPage,
        total: filtered.length,
        last_page: Math.max(1, Math.ceil(filtered.length / perPage)),
      },
      last_page: Math.max(1, Math.ceil(filtered.length / perPage)),
      total: filtered.length,
    };
  }

  function parseQuery(url) {
    var out = {};
    var i = String(url).indexOf("?");
    if (i === -1) return out;
    String(url)
      .slice(i + 1)
      .split("&")
      .forEach(function (pair) {
        if (!pair) return;
        var kv = pair.split("=");
        out[decodeURIComponent(kv[0])] = decodeURIComponent((kv[1] || "").replace(/\+/g, " "));
      });
    return out;
  }

  function nextId(collection) {
    var max = 0;
    (store[collection] || []).forEach(function (row) {
      var id = parseInt(row.id, 10);
      if (id > max) max = id;
    });
    return max + 1;
  }

  /* ---------- the router ---------- */

  function route(method, url, body) {
    var path = pathOf(url);
    var seg = segments(path);
    var query = parseQuery(url);
    var verb = String(method || "GET").toUpperCase();

    if (!seg.length) return null; // not an API call — let it through

    var head = seg[0];

    /* auth ------------------------------------------------------------- */
    if (head === "auth" || head === "login" || head === "logout" || head === "me") {
      var action = head === "auth" ? seg[1] : head;

      if (action === "login") {
        var payload = body || {};
        var identifier = String(
          payload.username || payload.email || payload.user || ""
        ).trim().toLowerCase();
        var secret = String(payload.password || "");

        var match = (seed.users || []).filter(function (u) {
          var names = [u.username, u.email].filter(Boolean).map(function (v) {
            return String(v).toLowerCase();
          });
          return names.indexOf(identifier) !== -1 && String(u.password) === secret;
        })[0];

        if (!match) {
          return {
            status: 422,
            body: {
              message: seed.strings && seed.strings.badCredentials
                ? seed.strings.badCredentials
                : "بيانات الدخول غير صحيحة",
              errors: { username: ["بيانات الدخول غير صحيحة"] },
            },
          };
        }

        store.__session = match.username || match.email;
        save();

        var user = shapeUser(match);
        return {
          status: 200,
          body: {
            token: "demo-token-" + encodeURIComponent(store.__session),
            access_token: "demo-token",
            token_type: "Bearer",
            user: user,
            data: user,
            modules: match.modules || seed.modules || [],
            permissions: match.permissions || [],
          },
        };
      }

      if (action === "logout") {
        delete store.__session;
        save();
        return { status: 200, body: { message: "ok" } };
      }

      if (action === "me" || action === "user" || action === "profile") {
        var current = currentUser();
        if (!current) return { status: 401, body: { message: "Unauthenticated." } };
        var shaped = shapeUser(current);
        return {
          status: 200,
          body: {
            user: shaped,
            data: shaped,
            modules: current.modules || seed.modules || [],
            permissions: current.permissions || [],
          },
        };
      }
    }

    /* explicit fixtures ------------------------------------------------- */
    var fixtures = seed.endpoints || {};
    var exactKey = verb + " " + path.replace(/^.*?(\/api)/, "$1");
    if (fixtures[exactKey]) return { status: 200, body: fixtures[exactKey] };
    if (fixtures[path]) return { status: 200, body: fixtures[path] };

    var loose = "/api/" + seg.join("/");
    if (fixtures[verb + " " + loose]) return { status: 200, body: fixtures[verb + " " + loose] };
    if (fixtures[loose]) return { status: 200, body: fixtures[loose] };

    /* generic collection CRUD ------------------------------------------- */
    // These apps namespace their routes (/inventory/items, /accounting/journals,
    // /trade/documents). Resolving the last non-numeric segment to a seeded
    // collection covers hundreds of endpoints without listing each one.
    var name = seg[0];
    var id = seg[1];

    if (!Object.prototype.hasOwnProperty.call(store, name)) {
      for (var s = seg.length - 1; s >= 0; s--) {
        if (/^\d+$/.test(seg[s])) continue;
        if (Object.prototype.hasOwnProperty.call(store, seg[s]) && Array.isArray(store[seg[s]])) {
          name = seg[s];
          id = /^\d+$/.test(seg[s + 1] || "") ? seg[s + 1] : undefined;
          break;
        }
      }
    }

    if (Object.prototype.hasOwnProperty.call(store, name) && Array.isArray(store[name])) {
      var rows = store[name];

      if (verb === "GET" && !id) return { status: 200, body: paginate(rows, query) };

      if (verb === "GET" && id) {
        var found = rows.filter(function (r) {
          return String(r.id) === String(id);
        })[0];
        return found
          ? { status: 200, body: { data: found } }
          : { status: 404, body: { message: "غير موجود" } };
      }

      if (verb === "POST") {
        var created = Object.assign({}, body || {}, { id: nextId(name) });
        rows.unshift(created);
        save();
        return { status: 201, body: { data: created, message: "تم الحفظ" } };
      }

      if (verb === "PUT" || verb === "PATCH") {
        for (var i = 0; i < rows.length; i++) {
          if (String(rows[i].id) === String(id)) {
            rows[i] = Object.assign({}, rows[i], body || {});
            save();
            return { status: 200, body: { data: rows[i], message: "تم التحديث" } };
          }
        }
        return { status: 404, body: { message: "غير موجود" } };
      }

      if (verb === "DELETE") {
        store[name] = rows.filter(function (r) {
          return String(r.id) !== String(id);
        });
        save();
        return { status: 200, body: { message: "تم الحذف" } };
      }
    }

    /* anything else: an empty, well-shaped answer beats a network error */
    if (verb === "GET") {
      return {
        status: 200,
        body: {
          data: [],
          meta: { current_page: 1, per_page: 15, total: 0, last_page: 1 },
          last_page: 1,
          total: 0,
        },
      };
    }

    return { status: 200, body: { message: "هذه معاينة للواجهة فقط", data: body || {} } };
  }

  function currentUser() {
    var key = store.__session;
    if (!key) return null;
    return (seed.users || []).filter(function (u) {
      return u.username === key || u.email === key;
    })[0] || null;
  }

  function shapeUser(u) {
    return {
      id: u.id || 1,
      name: u.name || u.username,
      username: u.username,
      first_name: u.first_name || u.name || u.username,
      last_name: u.last_name || "",
      email: u.email || (u.username + "@demo.local"),
      roles: u.roles || ["admin"],
      permissions: u.permissions || [],
      modules: u.modules || seed.modules || [],
      is_active: true,
      status: 1,
    };
  }

  /* ---------- XMLHttpRequest interception ---------- */

  var RealXHR = window.XMLHttpRequest;
  window.__demoXHR = RealXHR;

  function DemoXHR() {
    this._real = new RealXHR();
    this._headers = {};
    this.readyState = 0;
    this.status = 0;
    this.response = "";
    this.responseText = "";
    this.onreadystatechange = null;
    this.onload = null;
    this.onerror = null;
    this.ontimeout = null;
    this.onabort = null;
    this.upload = this._real.upload;
    this.withCredentials = false;
    this.responseType = "";
  }

  DemoXHR.prototype.open = function (method, url) {
    this._method = method;
    this._url = url;
    this._handled = segments(pathOf(url)).length > 0 && /\/api(\/|$|\?)/.test(String(url));
    if (!this._handled) {
      return this._real.open.apply(this._real, arguments);
    }
    this.readyState = 1;
  };

  DemoXHR.prototype.setRequestHeader = function (k, v) {
    if (!this._handled) return this._real.setRequestHeader(k, v);
    this._headers[k] = v;
  };

  DemoXHR.prototype.getAllResponseHeaders = function () {
    if (!this._handled) return this._real.getAllResponseHeaders();
    return "content-type: application/json\r\n";
  };

  DemoXHR.prototype.getResponseHeader = function (name) {
    if (!this._handled) return this._real.getResponseHeader(name);
    return /content-type/i.test(name) ? "application/json" : null;
  };

  DemoXHR.prototype.abort = function () {
    if (!this._handled) return this._real.abort();
  };

  DemoXHR.prototype.send = function (payload) {
    var self = this;

    if (!this._handled) {
      ["onreadystatechange", "onload", "onerror", "ontimeout", "onabort"].forEach(function (k) {
        if (self[k]) self._real[k] = self[k].bind(self._real);
      });
      this._real.responseType = this.responseType;
      this._real.withCredentials = this.withCredentials;
      this._real.addEventListener("loadend", function () {
        self.readyState = self._real.readyState;
        self.status = self._real.status;
        self.response = self._real.response;
        try {
          self.responseText = self._real.responseText;
        } catch (e) {
          self.responseText = "";
        }
      });
      return this._real.send(payload);
    }

    var body = null;
    if (payload) {
      try {
        body = typeof payload === "string" ? JSON.parse(payload) : payload;
      } catch (e) {
        body = payload;
      }
    }

    var result;
    try {
      result = route(this._method, this._url, body) || { status: 200, body: {} };
    } catch (e) {
      result = { status: 500, body: { message: "Demo runtime error: " + e.message } };
    }

    // A small delay keeps loading states visible instead of flashing.
    setTimeout(function () {
      self.readyState = 4;
      self.status = result.status;
      var text = JSON.stringify(result.body);
      self.responseText = text;
      self.response = self.responseType === "json" ? result.body : text;

      if (self.onreadystatechange) self.onreadystatechange();
      if (self.onload) self.onload();
      if (self.onloadend) self.onloadend();
    }, CFG.latency == null ? 140 : CFG.latency);
  };

  DemoXHR.prototype.addEventListener = function (type, fn) {
    if (!this._handled) return this._real.addEventListener(type, fn);
    if (type === "load") this.onload = fn;
    if (type === "loadend") this.onloadend = fn;
    if (type === "error") this.onerror = fn;
  };

  DemoXHR.prototype.removeEventListener = function () {};

  DemoXHR.UNSENT = 0;
  DemoXHR.OPENED = 1;
  DemoXHR.HEADERS_RECEIVED = 2;
  DemoXHR.LOADING = 3;
  DemoXHR.DONE = 4;

  /* ---------- fetch interception ---------- */

  var realFetch = window.fetch ? window.fetch.bind(window) : null;

  window.fetch = function (input, init) {
    var url = typeof input === "string" ? input : (input && input.url) || "";
    var method = (init && init.method) || (input && input.method) || "GET";

    if (!/\/api(\/|$|\?)/.test(String(url))) {
      return realFetch ? realFetch(input, init) : Promise.reject(new Error("no fetch"));
    }

    var body = null;
    if (init && init.body) {
      try {
        body = typeof init.body === "string" ? JSON.parse(init.body) : init.body;
      } catch (e) {
        body = init.body;
      }
    }

    var result;
    try {
      result = route(method, url, body) || { status: 200, body: {} };
    } catch (e) {
      result = { status: 500, body: { message: e.message } };
    }

    var text = JSON.stringify(result.body);
    return Promise.resolve(
      new Response(text, {
        status: result.status,
        headers: { "Content-Type": "application/json" },
      })
    );
  };

  /* ---------- boot ---------- */

  try {
    loadSeedSync();
    window.XMLHttpRequest = DemoXHR;
    window.__frugaDemoReset = function () {
      resetStore();
      window.location.reload();
    };
    window.__frugaDemoSeed = function () {
      return seed;
    };
  } catch (e) {
    // Without the seed the shim would answer nothing; leave the real
    // transports in place and let the app show its own connection error.
    if (window.console) {
      console.error("[fruga-demo] seed failed to load:", e);
    }
  }
})();
