(() => {
  const root = document.querySelector(".snf-service");
  if (!root) return;

  const motionItems = root.querySelectorAll(
    ".snf-service__hero, .snf-service__benefits, .snf-service__pricing"
  );

  motionItems.forEach((el, index) => {
    el.classList.add("snf-service__motion");
    el.style.setProperty("--snf-delay", `${Math.min(index * 80, 240)}ms`);
  });

  requestAnimationFrame(() => {
    root.classList.add("is-ready");
  });
})();
