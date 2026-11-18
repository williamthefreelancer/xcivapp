export function initCommunity() {
  const root = document.getElementById("snf-community");
  const boot = window.snfCommunity;
  if (!root || !boot || !boot.restUrl) return;

  const i18n = boot.i18n || {};

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
      const err = new Error((data && data.message) || i18n.error || "Request failed");
      err.status = res.status;
      throw err;
    }
    return data;
  };

  root.addEventListener("click", async (event) => {
    const btn = event.target.closest("[data-circle-join]");
    if (!btn) return;

    const card = btn.closest("[data-circle-id]");
    if (!card) return;

    const id = card.getAttribute("data-circle-id");
    const joined = btn.getAttribute("data-joined") === "1";
    const path = joined ? `/circles/${id}/leave` : `/circles/${id}/join`;

    btn.disabled = true;
    try {
      const data = await api(path, { method: "POST", body: "{}" });
      const circle = data.circle || {};
      const nowJoined = !!circle.joined;
      btn.setAttribute("data-joined", nowJoined ? "1" : "0");
      btn.classList.toggle("is-joined", nowJoined);
      btn.textContent = nowJoined
        ? i18n.leave || "Leave"
        : i18n.join || "Join";
      const countEl = card.querySelector("[data-member-count]");
      if (countEl && typeof circle.memberCount !== "undefined") {
        countEl.textContent = String(circle.memberCount);
      }
    } catch (err) {
      btn.title = err.message || i18n.error || "Error";
    } finally {
      btn.disabled = false;
    }
  });
}
