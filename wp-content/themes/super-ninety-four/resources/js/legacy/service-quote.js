(() => {
  const root = document.querySelector(".snf-quote");
  if (!root) return;

  const motionItems = root.querySelectorAll(
    ".snf-quote__hero, .snf-quote__section, .snf-quote__pricing"
  );

  motionItems.forEach((el, index) => {
    el.classList.add("snf-quote__motion");
    el.style.setProperty("--snf-delay", `${Math.min(index * 80, 240)}ms`);
  });

  requestAnimationFrame(() => {
    root.classList.add("is-ready");
  });
})();
