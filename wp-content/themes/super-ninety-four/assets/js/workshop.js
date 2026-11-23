(() => {
  const root = document.querySelector(".snf-workshop");
  if (!root) return;

  root.classList.add("snf-workshop--ready");

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

  const initTicketCommerce = (scope = document) => {
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

  initTicketCommerce(root);

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

  const purchase = root.querySelector("[data-snf-workshop-steps]");
  if (!purchase) return;

  const stepButtons = [...root.querySelectorAll(".snf-workshop__step-btn")];
  const stepItems = [...root.querySelectorAll("[data-step-indicator]")];
  const panels = [...purchase.querySelectorAll("[data-step-panel]")];
  let currentStep = 1;
  const maxStep = 2;

  const showStep = (step) => {
    currentStep = Math.min(maxStep, Math.max(1, Number(step) || 1));
    stepButtons.forEach((btn) => {
      const active = String(btn.dataset.step) === String(currentStep);
      if (active) {
        btn.setAttribute("aria-current", "step");
      } else {
        btn.removeAttribute("aria-current");
      }
    });
    stepItems.forEach((item) => {
      const n = Number(item.dataset.stepIndicator) || 0;
      item.classList.toggle("is-active", n === currentStep);
      item.classList.toggle("is-complete", n < currentStep);
    });
    panels.forEach((panel) => {
      const active = String(panel.dataset.stepPanel) === String(currentStep);
      panel.classList.toggle("is-active", active);
      panel.hidden = !active;
    });
  };

  const validateStep = (step) => {
    const panel = purchase.querySelector(`[data-step-panel="${step}"]`);
    if (!panel) return true;
    const fields = [...panel.querySelectorAll("input, select, textarea")].filter(
      (el) => el.type !== "hidden" && !el.disabled
    );
    for (const field of fields) {
      if (typeof field.checkValidity === "function" && !field.checkValidity()) {
        field.reportValidity?.();
        field.focus();
        return false;
      }
    }
    return true;
  };

  const syncContactToCart = () => {
    const map = {
      snf_workshop_name: "snf_cart_name",
      snf_workshop_email: "snf_cart_email",
      snf_workshop_phone: "snf_cart_phone",
    };
    Object.entries(map).forEach(([from, to]) => {
      const source = purchase.querySelector(`[name="${from}"]`);
      const target = purchase.querySelector(`[name="${to}"]`);
      if (source && target) {
        target.value = (source.value || "").trim();
      }
    });
  };

  stepButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const target = Number(btn.dataset.step) || 1;
      if (target > currentStep) {
        for (let s = currentStep; s < target; s += 1) {
          if (!validateStep(s)) return;
        }
        syncContactToCart();
      }
      showStep(target);
    });
  });

  purchase.querySelectorAll("[data-snf-next-step]").forEach((btn) => {
    btn.addEventListener("click", () => {
      if (!validateStep(currentStep)) return;
      syncContactToCart();
      showStep(currentStep + 1);
    });
  });

  purchase.querySelectorAll("[data-snf-prev-step]").forEach((btn) => {
    btn.addEventListener("click", () => showStep(currentStep - 1));
  });

  purchase.addEventListener("submit", () => {
    syncContactToCart();
  });

  showStep(1);
})();
