(() => {
  const root = document.querySelector(".snf-hub");
  if (!root) return;

  const motionItems = root.querySelectorAll(
    ".snf-hub__hero, .snf-hub__panel, .snf-hub__metrics, .snf-hub__identity, .snf-hub__auth, .snf-apply"
  );

  motionItems.forEach((el, index) => {
    el.classList.add("snf-hub__motion");
    el.style.setProperty("--snf-delay", `${Math.min(index * 65, 320)}ms`);
  });

  requestAnimationFrame(() => {
    root.classList.add("is-ready");
  });

  root.querySelectorAll(".snf-hub__switch input").forEach((input) => {
    input.addEventListener("change", () => {
      const row = input.closest(".snf-hub__switch");
      if (row) {
        row.classList.toggle("is-on", input.checked);
      }
    });
  });

  // Non-member Apply: Basics → Profile → Intent
  const applyForm = root.querySelector("[data-snf-apply-steps]");
  if (applyForm) {
    const stepButtons = [...root.querySelectorAll(".snf-apply__step-btn")];
    const stepItems = [...root.querySelectorAll("[data-step-indicator]")];
    const panels = [...applyForm.querySelectorAll("[data-step-panel]")];
    const titleEl = root.querySelector("[data-snf-apply-title]");
    const ledeEl = root.querySelector("[data-snf-apply-lede]");
    const statusEl = applyForm.querySelector("[data-snf-apply-status]");
    const copy = {
      1: {
        title: "Basics",
        lede: "A few details to get started",
      },
      2: {
        title: "Profile",
        lede: "Tell us a little more about you",
      },
      3: {
        title: "Intent",
        lede: "Choose your plan and billing details",
      },
    };
    let currentStep = 1;
    const maxStep = 3;

    const showStatus = (message) => {
      if (!statusEl) return;
      statusEl.hidden = !message;
      statusEl.textContent = message || "";
    };

    const showStep = (step) => {
      currentStep = Math.min(maxStep, Math.max(1, Number(step) || 1));
      stepButtons.forEach((btn) => {
        const active = String(btn.dataset.step) === String(currentStep);
        btn.setAttribute("aria-current", active ? "step" : "false");
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
      const meta = copy[currentStep] || copy[1];
      if (titleEl) titleEl.textContent = meta.title;
      if (ledeEl) ledeEl.textContent = meta.lede;
      showStatus("");
    };

    const validateStep = (step) => {
      const panel = applyForm.querySelector(`[data-step-panel="${step}"]`);
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

    stepButtons.forEach((btn) => {
      btn.addEventListener("click", () => {
        const target = Number(btn.dataset.step) || 1;
        if (target > currentStep) {
          for (let s = currentStep; s < target; s += 1) {
            if (!validateStep(s)) return;
          }
        }
        showStep(target);
      });
    });

    applyForm.querySelectorAll("[data-snf-next-step]").forEach((btn) => {
      btn.addEventListener("click", () => {
        if (!validateStep(currentStep)) return;
        showStep(currentStep + 1);
      });
    });

    applyForm.querySelectorAll("[data-snf-prev-step]").forEach((btn) => {
      btn.addEventListener("click", () => showStep(currentStep - 1));
    });

    applyForm.addEventListener("submit", (event) => {
      event.preventDefault();
      for (let s = 1; s <= maxStep; s += 1) {
        if (!validateStep(s)) {
          showStep(s);
          return;
        }
      }
      showStatus("Admissions request received. Taking you to under consideration…");
      const submit = applyForm.querySelector('button[type="submit"]');
      if (submit) submit.disabled = true;
      const nextUrl =
        window.snfHub && snfHub.considerationUrl
          ? snfHub.considerationUrl
          : "/under-consideration/";
      window.setTimeout(() => {
        window.location.assign(nextUrl);
      }, 600);
    });

    showStep(1);
  }
})();
