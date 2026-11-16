(() => {
  const root = document.querySelector(".snf-account");
  if (!root) return;

  const toggle = root.querySelector(".snf-account__toggle");
  const menu = root.querySelector(".snf-account__menu");
  if (!toggle || !menu) return;

  const setOpen = (open) => {
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
    menu.hidden = !open;
    root.classList.toggle("is-open", open);
  };

  toggle.addEventListener("click", (event) => {
    event.stopPropagation();
    setOpen(menu.hidden);
  });

  document.addEventListener("click", (event) => {
    if (!root.contains(event.target)) {
      setOpen(false);
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      setOpen(false);
      toggle.focus();
    }
  });
})();