/**
 * Expo / marketplace RFQ form (B2B inquiry without BuddyPress).
 */
export function initMarketplaceRfq() {
  const boot = window.snfExpo || window.snfMarketplace;
  if (!boot || !boot.rfqUrl) return;

  let form = document.getElementById("snf-marketplace-rfq");
  if (!form) {
    const host =
      document.querySelector(".snf-expo__intro") ||
      document.querySelector("#snf-marketplace-results") ||
      document.querySelector(".snf-expo");
    if (!host) return;

    const i18n = boot.rfqI18n || {};
    form = document.createElement("form");
    form.id = "snf-marketplace-rfq";
    form.className = "snf-rfq";
    form.setAttribute("novalidate", "novalidate");
    form.innerHTML = `
      <h2 class="snf-rfq__title">${escapeHtml(i18n.title || "Request a quote")}</h2>
      <div class="snf-rfq__grid">
        <label class="snf-rfq__field"><span>Name</span><input name="name" type="text" required autocomplete="name"></label>
        <label class="snf-rfq__field"><span>Email</span><input name="email" type="email" required autocomplete="email"></label>
        <label class="snf-rfq__field snf-rfq__field--full"><span>Company</span><input name="company" type="text" autocomplete="organization"></label>
        <label class="snf-rfq__field snf-rfq__field--full"><span>Message</span><textarea name="message" rows="4" required></textarea></label>
      </div>
      <p class="snf-rfq__status" id="snf-rfq-status" role="status" hidden></p>
      <button class="snf-rfq__submit" type="submit">${escapeHtml(i18n.submit || "Send RFQ")}</button>
    `;
    host.appendChild(form);
  }

  const status = form.querySelector("#snf-rfq-status") || form.querySelector(".snf-rfq__status");
  const i18n = boot.rfqI18n || {};

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const fd = new FormData(form);
    const payload = {
      name: String(fd.get("name") || "").trim(),
      email: String(fd.get("email") || "").trim(),
      company: String(fd.get("company") || "").trim(),
      message: String(fd.get("message") || "").trim(),
      audience: boot.audience || "public",
      productId: 0,
    };

    if (status) {
      status.hidden = false;
      status.textContent = "…";
    }

    try {
      const res = await fetch(boot.rfqUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": boot.rfqNonce || boot.nonce || "",
        },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        throw new Error((data && data.message) || i18n.error || "Error");
      }
      if (status) status.textContent = data.message || i18n.success || "Sent";
      form.reset();
    } catch (err) {
      if (status) status.textContent = err.message || i18n.error || "Error";
    }
  });
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}
