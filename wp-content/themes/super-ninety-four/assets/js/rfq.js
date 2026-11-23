(() => {
  const boot = window.snfExpo || window.snfMarketplace;
  if (!boot || !boot.rfqUrl) return;

  const cfg = boot.rfq || {};
  if (cfg.enabled === false) return;

  const i18n = boot.rfqI18n || {};
  const requireCompany = !!cfg.requireCompany;
  const maxLen = Math.max(200, Number(cfg.maxMessageLength) || 4000);

  const escapeHtml = (value) =>
    String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");

  let form = document.getElementById("snf-marketplace-rfq");
  if (!form) {
    const host =
      document.querySelector(".snf-expo__rfq-host") ||
      document.querySelector(".snf-expo__hero") ||
      document.querySelector(".snf-expo__intro") ||
      document.querySelector("#snf-marketplace-results") ||
      document.querySelector(".snf-expo");
    if (!host) return;

    form = document.createElement("form");
    form.id = "snf-marketplace-rfq";
    form.className = "snf-rfq";
    form.setAttribute("novalidate", "novalidate");

    const intro = cfg.intro
      ? `<p class="snf-rfq__intro">${escapeHtml(cfg.intro)}</p>`
      : "";

    form.innerHTML = `
      <h2 class="snf-rfq__title">${escapeHtml(i18n.title || "Request a quote")}</h2>
      ${intro}
      <div class="snf-rfq__grid">
        <label class="snf-rfq__field"><span>${escapeHtml(i18n.name || "Name")}</span><input name="name" type="text" required autocomplete="name"></label>
        <label class="snf-rfq__field"><span>${escapeHtml(i18n.email || "Email")}</span><input name="email" type="email" required autocomplete="email"></label>
        <label class="snf-rfq__field snf-rfq__field--full"><span>${escapeHtml(i18n.company || "Company")}${requireCompany ? " *" : ""}</span><input name="company" type="text" autocomplete="organization"${requireCompany ? " required" : ""}></label>
        <label class="snf-rfq__field snf-rfq__field--full"><span>${escapeHtml(i18n.message || "Message")}</span><textarea name="message" rows="4" required maxlength="${maxLen}"></textarea></label>
      </div>
      <p class="snf-rfq__status" id="snf-rfq-status" role="status" hidden></p>
      <button class="snf-rfq__submit" type="submit">${escapeHtml(i18n.submit || "Send RFQ")}</button>
    `;
    host.appendChild(form);
  } else {
    const companyInput = form.querySelector('input[name="company"]');
    if (companyInput) {
      if (requireCompany) companyInput.setAttribute("required", "required");
      else companyInput.removeAttribute("required");
    }
    const message = form.querySelector('textarea[name="message"]');
    if (message) message.setAttribute("maxlength", String(maxLen));
  }

  const status =
    form.querySelector("#snf-rfq-status") ||
    form.querySelector(".snf-rfq__status");

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const fd = new FormData(form);
    const payload = {
      name: String(fd.get("name") || "").trim(),
      email: String(fd.get("email") || "").trim(),
      company: String(fd.get("company") || "").trim(),
      message: String(fd.get("message") || "").trim(),
      audience: boot.audience || cfg.defaultAudience || "public",
      productId: Number(fd.get("productId") || boot.productId || 0) || 0,
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
})();
