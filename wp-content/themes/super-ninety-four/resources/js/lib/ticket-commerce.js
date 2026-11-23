/**
 * Shared TicketSpice-style tier / addon / upsell UI helpers.
 */

export function formatTicketMoney(amount) {
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
}

export function initTicketCommerce(root = document) {
  const blocks = [...root.querySelectorAll("[data-snf-ticket-commerce]")];
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
}
