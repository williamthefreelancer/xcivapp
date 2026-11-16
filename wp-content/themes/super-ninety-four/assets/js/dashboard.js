(() => {
  const root = document.querySelector(".snf-dashboard");
  if (!root) return;

  const motionItems = root.querySelectorAll(
    ".snf-dashboard__intro, .snf-dashboard__row"
  );

  motionItems.forEach((el, index) => {
    el.classList.add("snf-dashboard__motion");
    el.style.setProperty("--snf-delay", `${Math.min(index * 70, 280)}ms`);
  });

  requestAnimationFrame(() => {
    root.classList.add("is-ready");
  });
})();
