/* Fruga demo banner — shows the preview notice and the demo logins.
 *
 * The credentials card is rendered only while a password field is on screen,
 * so it sits on the login page and disappears once the visitor is inside. One
 * click fills the form and submits, because typing an Arabic username on a
 * phone keyboard is enough friction to lose people at the first screen.
 */
(function () {
  "use strict";

  var CFG = window.__FRUGA_DEMO__ || {};
  var seed = null;

  function t(ar, en) {
    return (document.documentElement.lang || "ar").indexOf("en") === 0 ? en : ar;
  }

  var css = [
    ".fruga-demo-bar{position:fixed;inset-inline:0;top:0;z-index:2147483000;",
    "display:flex;align-items:center;justify-content:center;gap:.6rem;flex-wrap:wrap;",
    "padding:.45rem .8rem;background:#0b1220;color:#e2e8f0;",
    "font:500 12px/1.4 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;",
    "box-shadow:0 1px 0 rgba(255,255,255,.08)}",
    ".fruga-demo-bar a{color:#38bdf8;text-decoration:none;font-weight:600}",
    ".fruga-demo-bar a:hover{text-decoration:underline}",
    ".fruga-demo-bar button{background:transparent;border:1px solid rgba(255,255,255,.25);",
    "color:#e2e8f0;border-radius:6px;padding:.15rem .5rem;font-size:11px;cursor:pointer}",
    ".fruga-demo-bar button:hover{background:rgba(255,255,255,.1)}",
    "body{--fruga-demo-offset:34px}",
    ".fruga-demo-push{padding-top:34px!important}",
    ".fruga-demo-card{position:fixed;z-index:2147483000;inset-inline-end:14px;bottom:14px;",
    "width:min(310px,calc(100vw - 28px));background:#0f172a;color:#e2e8f0;border:1px solid rgba(255,255,255,.14);",
    "border-radius:14px;box-shadow:0 18px 40px rgba(0,0,0,.45);overflow:hidden;",
    "font:400 12px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif}",
    ".fruga-demo-card h4{margin:0;padding:.6rem .8rem;font-size:12px;font-weight:700;",
    "background:rgba(56,189,248,.14);color:#7dd3fc;display:flex;justify-content:space-between;align-items:center}",
    ".fruga-demo-card h4 span{cursor:pointer;opacity:.75;font-weight:400;padding:0 .2rem}",
    ".fruga-demo-card ul{list-style:none;margin:0;padding:.35rem}",
    ".fruga-demo-card li{display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;border-radius:9px}",
    ".fruga-demo-card li+li{margin-top:.15rem}",
    ".fruga-demo-card li:hover{background:rgba(255,255,255,.06)}",
    ".fruga-demo-card .r{flex:1;min-width:0}",
    ".fruga-demo-card .n{font-weight:600;color:#f1f5f9}",
    ".fruga-demo-card .c{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;",
    "font-size:11px;color:#94a3b8;direction:ltr;unicode-bidi:plaintext}",
    ".fruga-demo-card .u{flex:none;background:#0ea5e9;color:#fff;border:0;border-radius:7px;",
    "padding:.3rem .6rem;font-size:11px;font-weight:700;cursor:pointer}",
    ".fruga-demo-card .u:hover{opacity:.9}",
    ".fruga-demo-note{padding:.45rem .8rem;border-top:1px solid rgba(255,255,255,.1);",
    "color:#94a3b8;font-size:11px}",
    ".fruga-demo-reopen{position:fixed;z-index:2147483000;inset-inline-end:14px;bottom:14px;",
    "background:#0ea5e9;color:#fff;border:0;border-radius:999px;padding:.5rem .9rem;",
    "font:700 12px system-ui;cursor:pointer;box-shadow:0 10px 24px rgba(0,0,0,.4)}",
  ].join("");

  function injectCss() {
    var style = document.createElement("style");
    style.textContent = css;
    document.head.appendChild(style);
  }

  function bar() {
    var el = document.createElement("div");
    el.className = "fruga-demo-bar";
    el.innerHTML =
      "<strong>" + t("معاينة واجهة", "Interface preview") + "</strong>" +
      "<span>" +
      t(
        "البيانات تجريبية وتُحفظ في متصفحك فقط — لا يوجد خادم.",
        "Demo data, stored in your browser only — no backend."
      ) +
      "</span>";

    var reset = document.createElement("button");
    reset.type = "button";
    reset.textContent = t("تصفير البيانات", "Reset data");
    reset.onclick = function () {
      if (window.__frugaDemoReset) window.__frugaDemoReset();
    };

    var back = document.createElement("a");
    back.href = CFG.backUrl || "/apps";
    back.textContent = t("عودة إلى المشاريع", "Back to projects");

    el.appendChild(reset);
    el.appendChild(back);
    document.body.appendChild(el);
    document.body.classList.add("fruga-demo-push");
  }

  /* ---------- credential card ---------- */

  function findPasswordField() {
    return document.querySelector('input[type="password"]');
  }

  function setValue(input, value) {
    // React tracks the value on the DOM node, so assigning .value directly is
    // ignored. Write through the native setter and dispatch input instead.
    var proto = Object.getPrototypeOf(input);
    var desc = Object.getOwnPropertyDescriptor(proto, "value");
    if (desc && desc.set) desc.set.call(input, value);
    else input.value = value;
    input.dispatchEvent(new Event("input", { bubbles: true }));
    input.dispatchEvent(new Event("change", { bubbles: true }));
  }

  function fill(user) {
    var pass = findPasswordField();
    if (!pass) return;

    // Not every login screen wraps its inputs in a <form> (Manex does not),
    // so fall back to the nearest common container before the document.
    var scope =
      pass.closest("form") ||
      pass.closest("[class*='login' i],[class*='auth' i],section,main,div") ||
      document;

    var candidates = scope.querySelectorAll(
      'input[type="text"],input[type="email"],input[type="tel"],input:not([type])'
    );

    // Prefer a field that names itself as the identifier; the first text input
    // can belong to an unrelated widget such as a server-address box.
    var target = null;
    for (var i = 0; i < candidates.length; i++) {
      var el = candidates[i];
      var hint = (
        (el.name || "") + " " + (el.id || "") + " " +
        (el.getAttribute("placeholder") || "") + " " +
        (el.getAttribute("autocomplete") || "")
      ).toLowerCase();
      if (/user|email|login|mail|اسم|بريد/.test(hint)) {
        target = el;
        break;
      }
    }
    if (!target) target = candidates[0];

    // Manex authenticates by email; the others accept the username.
    var identifier = user.username || user.email || "";
    if (target) {
      var isEmail =
        target.type === "email" ||
        /email|mail|بريد/.test(
          ((target.name || "") + (target.id || "") + (target.getAttribute("placeholder") || "")).toLowerCase()
        );
      setValue(target, isEmail ? user.email || identifier : identifier);
    }

    setValue(pass, user.password || "");

    setTimeout(function () {
      var submit =
        scope.querySelector('button[type="submit"]') ||
        scope.querySelector('input[type="submit"]');

      if (!submit) {
        // Last resort: a button whose label reads like a sign-in action.
        var buttons = scope.querySelectorAll("button");
        for (var b = 0; b < buttons.length; b++) {
          if (/دخول|تسجيل|login|sign in/i.test(buttons[b].textContent || "")) {
            submit = buttons[b];
            break;
          }
        }
      }

      if (submit) {
        submit.click();
      } else {
        // Some forms only submit on Enter.
        pass.dispatchEvent(
          new KeyboardEvent("keydown", { key: "Enter", code: "Enter", bubbles: true })
        );
      }
    }, 150);
  }

  var card = null;
  var dismissed = false;

  function buildCard() {
    if (card || dismissed) return;
    var users = (seed && seed.users) || [];
    if (!users.length) return;

    card = document.createElement("div");
    card.className = "fruga-demo-card";

    var head = document.createElement("h4");
    head.innerHTML = "<em style='font-style:normal'>" +
      t("حسابات التجربة", "Demo accounts") + "</em>";
    var close = document.createElement("span");
    close.textContent = "✕";
    close.title = t("إغلاق", "Close");
    close.onclick = function () {
      dismissed = true;
      card.remove();
      card = null;
      showReopen();
    };
    head.appendChild(close);
    card.appendChild(head);

    var list = document.createElement("ul");
    users.slice(0, 5).forEach(function (u) {
      var li = document.createElement("li");

      var right = document.createElement("div");
      right.className = "r";
      right.innerHTML =
        '<div class="n">' + (u.label || u.name || u.username) + "</div>" +
        '<div class="c">' + (u.username || u.email) + " · " + u.password + "</div>";

      var use = document.createElement("button");
      use.type = "button";
      use.className = "u";
      use.textContent = t("دخول", "Use");
      use.onclick = function () {
        fill(u);
      };

      li.appendChild(right);
      li.appendChild(use);
      list.appendChild(li);
    });
    card.appendChild(list);

    var note = document.createElement("div");
    note.className = "fruga-demo-note";
    note.textContent = t(
      "اضغط «دخول» ليُعبّأ النموذج تلقائياً.",
      "Press Use to fill the form automatically."
    );
    card.appendChild(note);

    document.body.appendChild(card);
  }

  var reopenBtn = null;
  function showReopen() {
    if (reopenBtn) return;
    reopenBtn = document.createElement("button");
    reopenBtn.type = "button";
    reopenBtn.className = "fruga-demo-reopen";
    reopenBtn.textContent = t("حسابات التجربة", "Demo accounts");
    reopenBtn.onclick = function () {
      dismissed = false;
      reopenBtn.remove();
      reopenBtn = null;
      buildCard();
    };
    document.body.appendChild(reopenBtn);
  }

  function sync() {
    var onLogin = !!findPasswordField();
    if (onLogin) {
      if (!card && !dismissed) buildCard();
      if (dismissed) showReopen();
    } else {
      if (card) {
        card.remove();
        card = null;
      }
      if (reopenBtn) {
        reopenBtn.remove();
        reopenBtn = null;
      }
      dismissed = false;
    }
  }

  function start() {
    injectCss();
    bar();
    try {
      seed = window.__frugaDemoSeed && window.__frugaDemoSeed();
    } catch (e) {
      seed = null;
    }
    sync();
    // The bundle renders asynchronously, and the login form can appear or
    // disappear on any route change, so the check repeats rather than running
    // once on load.
    new MutationObserver(sync).observe(document.body, {
      childList: true,
      subtree: true,
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start);
  } else {
    start();
  }
})();
