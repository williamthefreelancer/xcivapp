(() => {
  const root = document.querySelector(".snf-event");
  if (!root) return;

  const formatTicketMoney = (amount) => {
    const value = Math.max(0, Number(amount) || 0);
    try {
      return new Intl.NumberFormat(undefined, {
        style: "currency",
        currency: "USD",
        maximumFractionDigits: 0,
      }).format(value);
    } catch {
      return `$${Math.round(value)}`;
    }
  };

  const initTicketCommerce = (scope) => {
    const blocks = [...scope.querySelectorAll("[data-snf-ticket-commerce]")];
    if (!blocks.length) return;

    blocks.forEach((block) => {
      const totalWrap = block.querySelector("[data-snf-ticket-total]");
      const totalValue = block.querySelector("[data-snf-ticket-total-value]");

      const selectedTier = () =>
        [...block.querySelectorAll('input[name="snf_ticket_tier"]:checked')].map(
          (el) => el.value
        );

      const syncRequires = () => {
        const tiers = selectedTier();
        block.querySelectorAll("[data-requires-tier]").forEach((row) => {
          const raw = row.getAttribute("data-requires-tier") || "";
          const needs = raw
            .split(",")
            .map((s) => s.trim())
            .filter(Boolean);
          const ok = !needs.length || needs.some((id) => tiers.includes(id));
          row.hidden = !ok;
          row.classList.toggle("is-locked", !ok);
          if (!ok) {
            const qty = row.querySelector(".snf-ticket-commerce__qty-input");
            if (qty && !qty.required) qty.value = "0";
          }
        });
      };

      const updateTotal = () => {
        let sum = 0;
        block
          .querySelectorAll('input[name="snf_ticket_tier"]:checked')
          .forEach((el) => {
            sum += Number(el.dataset.ticketPrice || 0);
          });
        block.querySelectorAll(".snf-ticket-commerce__qty-input").forEach((el) => {
          if (el.closest("[data-requires-tier]")?.hidden) return;
          const qty = Math.max(0, Number(el.value) || 0);
          sum += qty * Number(el.dataset.ticketPrice || 0);
        });
        if (totalWrap && totalValue) {
          const alwaysShow =
            block.matches("[data-snf-booking-form]") ||
            Boolean(block.closest("[data-snf-booking-form]"));
          if (!alwaysShow) {
            totalWrap.hidden = sum <= 0;
          } else {
            totalWrap.hidden = false;
          }
          totalValue.textContent = formatTicketMoney(sum);
        }
      };

      const refresh = () => {
        syncRequires();
        updateTotal();
      };

      block.addEventListener("change", refresh);
      block.addEventListener("input", refresh);
      refresh();
    });
  };

  // Countdown
  const countdownEl = root.querySelector("[data-snf-countdown]");
  if (countdownEl) {
    const targetRaw = countdownEl.getAttribute("data-snf-countdown");
    const target = targetRaw ? Date.parse(targetRaw) : NaN;
    const units = {
      days: countdownEl.querySelector('[data-unit="days"]'),
      hours: countdownEl.querySelector('[data-unit="hours"]'),
      mins: countdownEl.querySelector('[data-unit="mins"]'),
      secs: countdownEl.querySelector('[data-unit="secs"]'),
    };

    const pad = (n) => String(Math.max(0, n)).padStart(2, "0");

    const tick = () => {
      if (!Number.isFinite(target)) return;
      const diff = Math.max(0, target - Date.now());
      const days = Math.floor(diff / 86400000);
      const hours = Math.floor((diff % 86400000) / 3600000);
      const mins = Math.floor((diff % 3600000) / 60000);
      const secs = Math.floor((diff % 60000) / 1000);
      if (units.days) units.days.textContent = pad(Math.min(99, days));
      if (units.hours) units.hours.textContent = pad(hours);
      if (units.mins) units.mins.textContent = pad(mins);
      if (units.secs) units.secs.textContent = pad(secs);
    };

    tick();
    if (Number.isFinite(target)) {
      setInterval(tick, 1000);
    }
  }

  initTicketCommerce(root);

  // Multi-step admissions
  const form = root.querySelector("[data-snf-event-steps]");
  if (!form) return;

  const canPurchase = form.getAttribute("data-ticket-purchase") === "1";
  const tabs = [...form.querySelectorAll(".snf-event__step-tab")];
  const panels = [...form.querySelectorAll("[data-step-panel]")];
  const statusEl = form.querySelector("[data-snf-form-status]");
  const maxStep = panels.reduce((max, panel) => {
    const step = Number(panel.dataset.stepPanel) || 1;
    return Math.max(max, step);
  }, 1);

  let currentStep = 1;

  const showStatus = (message, isError = false) => {
    if (!statusEl) return;
    statusEl.hidden = !message;
    statusEl.textContent = message || "";
    statusEl.classList.toggle("is-error", Boolean(isError && message));
  };

  const showStep = (step) => {
    const next = Math.min(maxStep, Math.max(1, Number(step) || 1));
    currentStep = next;

    tabs.forEach((tab) => {
      const active = String(tab.dataset.step) === String(next);
      tab.classList.toggle("is-active", active);
      tab.setAttribute("aria-selected", active ? "true" : "false");
    });

    panels.forEach((panel) => {
      const active = String(panel.dataset.stepPanel) === String(next);
      panel.classList.toggle("is-active", active);
      panel.hidden = !active;
    });

    showStatus("");
  };

  const panelFields = (step) => {
    const panel = form.querySelector(`[data-step-panel="${step}"]`);
    if (!panel) return [];
    return [...panel.querySelectorAll("input, select, textarea")].filter(
      (el) =>
        el.type !== "hidden" &&
        !el.disabled &&
        !el.closest("[hidden]") &&
        !el.closest(".is-locked")
    );
  };

  const validateStep = (step) => {
    const fields = panelFields(step);
    for (const field of fields) {
      if (typeof field.checkValidity === "function" && !field.checkValidity()) {
        field.reportValidity?.();
        field.focus();
        return false;
      }
      if (field.hasAttribute("required") && !String(field.value || "").trim()) {
        field.focus();
        return false;
      }
    }
    return true;
  };

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      const target = Number(tab.dataset.step) || 1;
      if (target > currentStep) {
        for (let step = currentStep; step < target; step += 1) {
          if (!validateStep(step)) return;
        }
      }
      showStep(target);
    });
  });

  form.querySelectorAll("[data-snf-next-step]").forEach((btn) => {
    btn.addEventListener("click", () => {
      if (!validateStep(currentStep)) return;
      showStep(currentStep + 1);
    });
  });

  form.querySelectorAll("[data-snf-prev-step]").forEach((btn) => {
    btn.addEventListener("click", () => showStep(currentStep - 1));
  });

  form.addEventListener("submit", (event) => {
    for (let step = 1; step <= maxStep; step += 1) {
      if (!validateStep(step)) {
        event.preventDefault();
        showStep(step);
        return;
      }
    }

    if (canPurchase) {
      showStatus("Adding tickets to your cart…");
      return;
    }

    event.preventDefault();
    const applyLink = form.querySelector("[data-snf-apply-redirect]");
    if (applyLink?.href) {
      window.location.href = applyLink.href;
      return;
    }

    showStatus("Admissions request received. The desk will follow up shortly.");
    form.querySelector("#snf-event-cta")?.setAttribute("disabled", "disabled");
  });

  showStep(1);
})();
