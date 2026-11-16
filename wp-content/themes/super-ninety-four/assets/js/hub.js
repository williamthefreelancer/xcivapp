(() => {
  const root = document.querySelector(".snf-hub");
  if (!root) return;

  const motionItems = root.querySelectorAll(
    ".snf-hub__hero, .snf-hub__panel, .snf-hub__metrics, .snf-hub__identity, .snf-hub__auth"
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

  // Non-member Apply: contact details → billing details
  const applyForm = root.querySelector("[data-snf-apply-steps]");
  if (applyForm) {
    const tabs = [...applyForm.querySelectorAll(".snf-apply-steps__tab")];
    const panels = [...applyForm.querySelectorAll("[data-step-panel]")];
    const note = root.querySelector("[data-snf-apply-step-note]");
    const statusEl = applyForm.querySelector("[data-snf-apply-status]");
    const notes = {
      1: "Step 1 of 2 — Contact details",
      2: "Step 2 of 2 — Billing details",
    };
    let currentStep = 1;

    const showStatus = (message) => {
      if (!statusEl) return;
      statusEl.hidden = !message;
      statusEl.textContent = message || "";
    };

    const showStep = (step) => {
      currentStep = Math.min(2, Math.max(1, Number(step) || 1));
      tabs.forEach((tab) => {
        const active = String(tab.dataset.step) === String(currentStep);
        tab.classList.toggle("is-active", active);
        tab.setAttribute("aria-selected", active ? "true" : "false");
      });
      panels.forEach((panel) => {
        const active = String(panel.dataset.stepPanel) === String(currentStep);
        panel.classList.toggle("is-active", active);
        panel.hidden = !active;
      });
      if (note) note.textContent = notes[currentStep] || notes[1];
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

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        const target = Number(tab.dataset.step) || 1;
        if (target > currentStep && !validateStep(currentStep)) return;
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
      if (!validateStep(1)) {
        showStep(1);
        return;
      }
      if (!validateStep(2)) {
        showStep(2);
        return;
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
