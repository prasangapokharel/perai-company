(function () {
  "use strict";

  var script = document.currentScript;
  if (!script) return;

  var companyId = script.getAttribute("data-company-id");
  var apiKey = script.getAttribute("data-api-key");
  var apiBase = script.getAttribute("data-api-base") || "http://localhost:8000/api/v1";
  var title = script.getAttribute("data-title") || "Chat with us";
  var color = script.getAttribute("data-color") || "#2563eb";
  var logoUrl =
    script.getAttribute("data-logo-url") ||
    apiBase.replace(/\/$/, "") + "/files/companies/" + companyId + "/logo";

  if (!companyId || !apiKey) {
    console.error("[Perai] data-company-id and data-api-key are required");
    return;
  }

  var rootId = "perai-widget-root";
  if (document.getElementById(rootId)) return;

  var style = document.createElement("style");
  style.textContent =
    "#perai-widget-root{font-family:system-ui,-apple-system,sans-serif;z-index:2147483000;position:fixed;bottom:20px;right:20px}" +
    "#perai-widget-btn{width:56px;height:56px;border-radius:9999px;border:none;cursor:pointer;color:#fff;box-shadow:0 8px 24px rgba(0,0,0,.18);font-weight:600;font-size:13px}" +
    "#perai-widget-panel{display:none;position:absolute;bottom:68px;right:0;width:360px;max-width:calc(100vw - 32px);height:480px;background:#fff;border-radius:14px;box-shadow:0 16px 40px rgba(0,0,0,.2);overflow:hidden;flex-direction:column}" +
    "#perai-widget-panel.open{display:flex}" +
    "#perai-widget-header{padding:12px 14px;color:#fff;font-weight:600;font-size:14px}" +
    "#perai-widget-messages{flex:1;overflow:auto;padding:12px;background:#f8fafc;display:flex;flex-direction:column;gap:10px}" +
    "#perai-widget-form{display:flex;gap:8px;padding:10px;border-top:1px solid #e2e8f0;background:#fff}" +
    "#perai-widget-input{flex:1;border:1px solid #cbd5e1;border-radius:8px;padding:8px 10px;font-size:14px}" +
    "#perai-widget-send{border:none;border-radius:8px;color:#fff;padding:8px 12px;cursor:pointer;font-size:13px;font-weight:500}" +
    ".perai-msg-row{display:flex;align-items:flex-end;gap:8px;max-width:100%}" +
    ".perai-msg-row.user{justify-content:flex-end}" +
    ".perai-msg-row.bot{justify-content:flex-start}" +
    ".perai-msg-avatar{width:28px;height:28px;border-radius:9999px;object-fit:cover;flex-shrink:0;background:#e2e8f0;border:1px solid #dbeafe}" +
    ".perai-msg-avatar-fallback{width:28px;height:28px;border-radius:9999px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#e2e8f0;color:#475569;font-size:11px;font-weight:600;border:1px solid #dbeafe}" +
    ".perai-msg{font-size:13px;line-height:1.5;max-width:calc(100% - 36px)}" +
    ".perai-msg.user{background:#e2e8f0;padding:8px 10px;border-radius:10px 10px 2px 10px;color:#0f172a}" +
    ".perai-msg.bot{background:#fff;border:1px solid #e2e8f0;padding:8px 10px;border-radius:10px 10px 10px 2px;color:#0f172a}" +
    ".perai-msg.loading{opacity:.75;font-style:italic}" +
    ".perai-msg-body p{margin:0 0 8px}" +
    ".perai-msg-body p:last-child{margin-bottom:0}" +
    ".perai-msg-body ul,.perai-msg-body ol{margin:0 0 8px;padding-left:18px}" +
    ".perai-msg-body li{margin-bottom:4px}" +
    ".perai-msg-body strong{font-weight:600}" +
    ".perai-msg-body code{font-family:ui-monospace,monospace;font-size:12px;background:#f1f5f9;padding:1px 4px;border-radius:4px}";

  document.head.appendChild(style);

  var root = document.createElement("div");
  root.id = rootId;

  var panel = document.createElement("div");
  panel.id = "perai-widget-panel";

  var header = document.createElement("div");
  header.id = "perai-widget-header";
  header.textContent = title;
  header.style.background = color;

  var messages = document.createElement("div");
  messages.id = "perai-widget-messages";

  var form = document.createElement("form");
  form.id = "perai-widget-form";

  var input = document.createElement("input");
  input.id = "perai-widget-input";
  input.type = "text";
  input.placeholder = "Ask a question...";
  input.autocomplete = "off";

  var send = document.createElement("button");
  send.id = "perai-widget-send";
  send.type = "submit";
  send.textContent = "Send";
  send.style.background = color;

  form.appendChild(input);
  form.appendChild(send);
  panel.appendChild(header);
  panel.appendChild(messages);
  panel.appendChild(form);

  var button = document.createElement("button");
  button.id = "perai-widget-btn";
  button.type = "button";
  button.textContent = "AI";
  button.style.background = color;
  button.setAttribute("aria-label", "Open chat");

  root.appendChild(panel);
  root.appendChild(button);
  document.body.appendChild(root);

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function renderMarkdown(text) {
    var safe = escapeHtml(text);
    safe = safe.replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>");
    safe = safe.replace(/\*(.+?)\*/g, "<em>$1</em>");
    safe = safe.replace(/`([^`]+)`/g, "<code>$1</code>");

    var lines = safe.split("\n");
    var html = [];
    var inList = false;

    for (var i = 0; i < lines.length; i++) {
      var line = lines[i];
      var bullet = line.match(/^\s*[-*]\s+(.+)/);
      if (bullet) {
        if (!inList) {
          html.push("<ul>");
          inList = true;
        }
        html.push("<li>" + bullet[1] + "</li>");
        continue;
      }
      if (inList) {
        html.push("</ul>");
        inList = false;
      }
      if (line.trim()) {
        html.push("<p>" + line + "</p>");
      }
    }
    if (inList) html.push("</ul>");
    return html.join("");
  }

  function createAvatar() {
    var img = document.createElement("img");
    img.className = "perai-msg-avatar";
    img.src = logoUrl;
    img.alt = title;
    img.referrerPolicy = "no-referrer";

    var fallback = document.createElement("div");
    fallback.className = "perai-msg-avatar-fallback";
    fallback.textContent = (title || "AI").slice(0, 1).toUpperCase();
    fallback.style.display = "none";

    img.addEventListener("error", function () {
      img.style.display = "none";
      fallback.style.display = "flex";
    });

    return { img: img, fallback: fallback };
  }

  function addMessage(text, role, options) {
    options = options || {};
    var row = document.createElement("div");
    row.className = "perai-msg-row " + role;

    var bubble = document.createElement("div");
    bubble.className = "perai-msg " + role + (options.loading ? " loading" : "");

    if (role === "bot") {
      var avatarWrap = document.createElement("div");
      var avatar = createAvatar();
      avatarWrap.appendChild(avatar.img);
      avatarWrap.appendChild(avatar.fallback);
      row.appendChild(avatarWrap);

      var body = document.createElement("div");
      body.className = "perai-msg-body";
      if (options.markdown) {
        body.innerHTML = renderMarkdown(text);
      } else {
        body.textContent = text;
      }
      bubble.appendChild(body);
      row.appendChild(bubble);
    } else {
      bubble.textContent = text;
      row.appendChild(bubble);
    }

    messages.appendChild(row);
    messages.scrollTop = messages.scrollHeight;
    return { row: row, bubble: bubble, body: bubble.querySelector(".perai-msg-body") };
  }

  function setBotMessage(target, text, options) {
    options = options || {};
    target.bubble.className = "perai-msg bot" + (options.loading ? " loading" : "");
    if (target.body) {
      if (options.markdown !== false) {
        target.body.innerHTML = renderMarkdown(text);
      } else {
        target.body.textContent = text;
      }
    } else {
      target.bubble.textContent = text;
    }
    messages.scrollTop = messages.scrollHeight;
  }

  function parseErrorMessage(err, resBody) {
    if (resBody) {
      try {
        var parsed = JSON.parse(resBody);
        if (parsed.detail) return String(parsed.detail);
      } catch (e) {}
    }
    if (err && err.message) return err.message;
    return "Sorry, something went wrong.";
  }

  button.addEventListener("click", function () {
    panel.classList.toggle("open");
  });

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var prompt = input.value.trim();
    if (!prompt) return;

    input.value = "";
    addMessage(prompt, "user");
    var loading = addMessage("Thinking...", "bot", { loading: true, markdown: false });

    fetch(apiBase.replace(/\/$/, "") + "/company/" + companyId + "/chat/query", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-API-Key": apiKey,
      },
      body: JSON.stringify({ prompt: prompt }),
    })
      .then(function (res) {
        return res.text().then(function (body) {
          return { ok: res.ok, status: res.status, body: body };
        });
      })
      .then(function (result) {
        if (result.status === 402) {
          setBotMessage(loading, "Insufficient balance. Please top up your account.", { markdown: false });
          return;
        }
        if (!result.ok) {
          throw new Error(parseErrorMessage(null, result.body));
        }
        var data = JSON.parse(result.body);
        setBotMessage(loading, data.response || "No response", { markdown: true });
      })
      .catch(function (err) {
        setBotMessage(loading, parseErrorMessage(err), { markdown: false });
        console.error("[Perai]", err);
      });
  });
})();
