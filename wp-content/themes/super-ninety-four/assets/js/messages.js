(() => {
  const root = document.getElementById("snf-msg");
  const boot = window.snfMessages;
  if (!root || !boot) return;

  const threadsEl = document.getElementById("snf-msg-threads");
  const streamEl = document.getElementById("snf-msg-stream");
  const titleEl = document.getElementById("snf-msg-title");
  const statusEl = document.getElementById("snf-msg-status");
  const headEl = document.getElementById("snf-msg-head");
  const composer = document.getElementById("snf-msg-composer");
  const input = document.getElementById("snf-msg-input");
  const sendBtn = document.getElementById("snf-msg-send");
  const search = document.getElementById("snf-msg-search");

  const i18n = boot.i18n || {};
  const state = {
    threadId: null,
    lastId: 0,
    sending: false,
    polling: null,
    messages: new Map(),
  };

  const escapeHtml = (value) =>
    String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");

  const api = async (path, options = {}) => {
    const res = await fetch(`${boot.restUrl}${path}`, {
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": boot.nonce,
        ...(options.headers || {}),
      },
      ...options,
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const err = new Error(
        (data && data.message) || i18n.error || "Request failed"
      );
      err.status = res.status;
      err.data = data;
      throw err;
    }
    return data;
  };

  const resizeInput = () => {
    if (!input) return;
    input.style.height = "auto";
    input.style.height = `${Math.min(input.scrollHeight, 120)}px`;
  };

  const scrollStream = (force = false) => {
    if (!streamEl) return;
    const nearBottom =
      streamEl.scrollHeight - streamEl.scrollTop - streamEl.clientHeight < 120;
    if (force || nearBottom) {
      streamEl.scrollTop = streamEl.scrollHeight;
    }
  };

  const bubbleHtml = (msg) => {
    const mine = msg.mine ? " is-mine" : "";
    return `
      <article class="snf-msg__bubble${mine}" data-message-id="${msg.id}">
        <p class="snf-msg__bubble-text">${escapeHtml(msg.body)}</p>
        <time class="snf-msg__bubble-time" datetime="${escapeHtml(
          msg.created_at || ""
        )}">${escapeHtml(msg.time_label || "")}</time>
      </article>
    `;
  };

  const renderMessages = (messages, { replace = false } = {}) => {
    if (!streamEl) return;

    if (replace) {
      state.messages.clear();
      streamEl.innerHTML = "";
    }

    if (!messages.length && state.messages.size === 0) {
      streamEl.innerHTML = `<div class="snf-msg__placeholder"><p>${escapeHtml(
        i18n.emptyThread || "No messages yet."
      )}</p></div>`;
      return;
    }

    const placeholder = streamEl.querySelector(".snf-msg__placeholder");
    if (placeholder) placeholder.remove();

    const frag = document.createDocumentFragment();
    let added = 0;

    messages.forEach((msg) => {
      if (state.messages.has(msg.id)) return;
      state.messages.set(msg.id, msg);
      state.lastId = Math.max(state.lastId, msg.id);

      const wrap = document.createElement("div");
      wrap.innerHTML = bubbleHtml(msg).trim();
      frag.appendChild(wrap.firstElementChild);
      added += 1;
    });

    if (added) {
      streamEl.appendChild(frag);
      scrollStream(replace);
    }
  };

  const setActiveThreadButton = (threadId) => {
    if (!threadsEl) return;
    threadsEl.querySelectorAll(".snf-msg__thread").forEach((btn) => {
      const active = String(btn.dataset.threadId) === String(threadId);
      btn.classList.toggle("is-active", active);
      btn.setAttribute("aria-selected", active ? "true" : "false");
      if (active) {
        btn.classList.remove("is-unread");
        const unread = btn.querySelector(".snf-msg__unread");
        if (unread) unread.remove();
      }
    });
  };

  const updateHead = (btn) => {
    if (!btn || !headEl) return;
    const glyph = btn.dataset.glyph || "SN";
    const title = btn.dataset.title || "";
    const kind =
      (btn.querySelector(".snf-msg__avatar") || {}).dataset?.kind || "desk";

    let avatar = headEl.querySelector(".snf-msg__avatar");
    if (!avatar) {
      avatar = document.createElement("span");
      avatar.className = "snf-msg__avatar snf-msg__avatar--lg";
      avatar.setAttribute("aria-hidden", "true");
      headEl.prepend(avatar);
    }
    avatar.dataset.kind = kind;
    avatar.textContent = glyph;

    if (titleEl) titleEl.textContent = title;
    if (statusEl) statusEl.textContent = i18n.online || "Active now";
  };

  const updateThreadPreview = (threadId, preview, timeLabel) => {
    const btn = threadsEl?.querySelector(
      `.snf-msg__thread[data-thread-id="${threadId}"]`
    );
    if (!btn) return;
    const previewEl = btn.querySelector(".snf-msg__thread-preview");
    const timeEl = btn.querySelector(".snf-msg__thread-time");
    if (previewEl) previewEl.textContent = preview;
    if (timeEl && timeLabel) timeEl.textContent = timeLabel;

    const listItem = btn.closest("li");
    if (listItem && threadsEl.firstElementChild !== listItem) {
      threadsEl.prepend(listItem);
    }
  };

  const loadThread = async (threadId, { soft = false } = {}) => {
    if (!boot.loggedIn || !threadId) return;

    if (!soft) {
      state.threadId = Number(threadId);
      state.lastId = 0;
      state.messages.clear();
      setActiveThreadButton(threadId);
      const btn = threadsEl?.querySelector(
        `.snf-msg__thread[data-thread-id="${threadId}"]`
      );
      updateHead(btn);
      if (streamEl) {
        streamEl.innerHTML =
          '<div class="snf-msg__placeholder snf-msg__placeholder--loading"><p>…</p></div>';
      }
    }

    try {
      const after = soft ? state.lastId : 0;
      const data = await api(`/threads/${threadId}?after=${after}`);
      renderMessages(data.messages || [], { replace: !soft });
      if (!soft) {
        api(`/threads/${threadId}/read`, { method: "POST", body: "{}" }).catch(
          () => {}
        );
      }
    } catch (err) {
      if (!soft && streamEl) {
        streamEl.innerHTML = `<div class="snf-msg__placeholder"><p>${escapeHtml(
          err.message || i18n.error
        )}</p></div>`;
      }
    }
  };

  const refreshThreadList = async () => {
    if (!boot.loggedIn || !threadsEl) return;
    try {
      const data = await api("/threads");
      const threads = data.threads || [];
      threads.forEach((thread) => {
        const btn = threadsEl.querySelector(
          `.snf-msg__thread[data-thread-id="${thread.id}"]`
        );
        if (!btn) return;
        const previewEl = btn.querySelector(".snf-msg__thread-preview");
        const timeEl = btn.querySelector(".snf-msg__thread-time");
        if (previewEl) previewEl.textContent = thread.preview;
        if (timeEl) timeEl.textContent = thread.time_label;

        const isActive = Number(thread.id) === Number(state.threadId);
        btn.classList.toggle("is-unread", !!thread.unread && !isActive);
        let unread = btn.querySelector(".snf-msg__unread");
        if (thread.unread && !isActive) {
          if (!unread) {
            unread = document.createElement("span");
            unread.className = "snf-msg__unread";
            unread.setAttribute("aria-label", "Unread");
            btn.appendChild(unread);
          }
        } else if (unread) {
          unread.remove();
        }
      });
    } catch (_) {
      /* keep UI stable while offline briefly */
    }
  };

  const startPolling = () => {
    if (state.polling) clearInterval(state.polling);
    const ms = Number(boot.pollMs) || 2500;
    state.polling = setInterval(() => {
      if (document.hidden || !state.threadId) return;
      loadThread(state.threadId, { soft: true });
      refreshThreadList();
    }, ms);
  };

  const sendMessage = async () => {
    if (!boot.loggedIn || !state.threadId || state.sending || !input) return;
    const body = input.value.trim();
    if (!body) return;

    state.sending = true;
    sendBtn?.classList.add("is-busy");
    if (sendBtn) sendBtn.disabled = true;

    try {
      const data = await api(`/threads/${state.threadId}/send`, {
        method: "POST",
        body: JSON.stringify({ body }),
      });

      input.value = "";
      resizeInput();

      const batch = [];
      if (data.message) batch.push(data.message);
      if (Array.isArray(data.replies)) batch.push(...data.replies);
      renderMessages(batch);

      if (data.message) {
        updateThreadPreview(
          state.threadId,
          data.message.body,
          data.message.time_label
        );
      }
    } catch (err) {
      if (statusEl) {
        statusEl.textContent = err.message || i18n.error;
        statusEl.classList.add("is-error");
        setTimeout(() => {
          statusEl.classList.remove("is-error");
          statusEl.textContent = i18n.online || "Active now";
        }, 2400);
      }
    } finally {
      state.sending = false;
      sendBtn?.classList.remove("is-busy");
      if (sendBtn) sendBtn.disabled = false;
      input?.focus();
    }
  };

  threadsEl?.addEventListener("click", (event) => {
    const btn = event.target.closest(".snf-msg__thread");
    if (!btn) return;
    loadThread(btn.dataset.threadId);
  });

  composer?.addEventListener("submit", (event) => {
    event.preventDefault();
    sendMessage();
  });

  input?.addEventListener("input", resizeInput);
  input?.addEventListener("keydown", (event) => {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      sendMessage();
    }
  });

  search?.addEventListener("input", () => {
    const q = search.value.trim().toLowerCase();
    threadsEl?.querySelectorAll(".snf-msg__thread").forEach((btn) => {
      const hay = `${btn.dataset.title || ""} ${
        btn.querySelector(".snf-msg__thread-preview")?.textContent || ""
      }`.toLowerCase();
      btn.closest("li")?.classList.toggle("is-hidden", q && !hay.includes(q));
    });
  });

  document.addEventListener("visibilitychange", () => {
    if (!document.hidden && state.threadId) {
      loadThread(state.threadId, { soft: true });
    }
  });

  if (boot.loggedIn) {
    const first = threadsEl?.querySelector(".snf-msg__thread");
    if (first) {
      loadThread(first.dataset.threadId);
    }
    startPolling();
  }

  root.classList.add("is-ready");
})();
