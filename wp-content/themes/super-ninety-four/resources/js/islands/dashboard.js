export function initDashboard() {
const root = document.querySelector(".snf-dashboard");
  if (!root) return;

  const motionItems = root.querySelectorAll(
    ".snf-dashboard__intro, .snf-account-surface__intro, .snf-profile-identity, .snf-panel, .snf-dashboard__row"
  );

  motionItems.forEach((el, index) => {
    el.classList.add("snf-dashboard__motion");
    el.style.setProperty("--snf-delay", `${Math.min(index * 70, 280)}ms`);
  });

  requestAnimationFrame(() => {
    root.classList.add("is-ready");
  });

  root.querySelectorAll(".snf-dashboard__row--slider").forEach((row) => {
    const track = row.querySelector("[data-slider-track]");
    const prev = row.querySelector("[data-slider-prev]");
    const next = row.querySelector("[data-slider-next]");
    if (!track || !prev || !next) return;

    const scrollByCard = (direction) => {
      const card = track.querySelector(".snf-dashboard__card-item");
      const amount = card ? card.getBoundingClientRect().width + 14 : 280;
      track.scrollBy({ left: direction * amount, behavior: "smooth" });
    };

    prev.addEventListener("click", () => scrollByCard(-1));
    next.addEventListener("click", () => scrollByCard(1));
  });
}
