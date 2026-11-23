/**
 * Header / primary nav island (ported from assets/js/header.js).
 */
export function initHeader() {
  const accountRoot = document.querySelector('.snf-account');
  if (accountRoot) {
    const toggle = accountRoot.querySelector('.snf-account__toggle');
    const menu = accountRoot.querySelector('.snf-account__menu');

    if (toggle && menu) {
      const setOpen = (open) => {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menu.hidden = !open;
        accountRoot.classList.toggle('is-open', open);
      };

      toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        setOpen(menu.hidden);
      });

      document.addEventListener('click', (event) => {
        if (!accountRoot.contains(event.target)) {
          setOpen(false);
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          setOpen(false);
          toggle.focus();
        }
      });
    }
  }

  const nav = document.querySelector('.snf-primary-nav');
  if (!nav) return;

  const menuToggle = nav.querySelector('.snf-primary-nav__toggle');
  const panel = nav.querySelector('.snf-primary-nav__panel');
  const dropdownItems = Array.from(
    nav.querySelectorAll('.snf-primary-nav__item--dropdown')
  );
  const desktopQuery = window.matchMedia('(min-width: 80.0625rem)');

  const setNavOpen = (open) => {
    if (!menuToggle || !panel) return;
    menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    nav.classList.toggle('is-open', open);
    document.body.classList.toggle('snf-nav-open', open);
  };

  const closeDropdowns = (except = null) => {
    dropdownItems.forEach((item) => {
      if (item === except) return;
      const trigger = item.querySelector('.snf-primary-nav__trigger');
      const dropdown = item.querySelector('.snf-primary-nav__dropdown');
      if (!trigger || !dropdown) return;
      trigger.setAttribute('aria-expanded', 'false');
      dropdown.hidden = true;
      item.classList.remove('is-open');
    });
  };

  const resetMobileNav = () => {
    closeDropdowns();
    setNavOpen(false);
  };

  if (menuToggle && panel) {
    menuToggle.addEventListener('click', (event) => {
      event.stopPropagation();
      setNavOpen(!nav.classList.contains('is-open'));
    });
  }

  dropdownItems.forEach((item) => {
    const trigger = item.querySelector('.snf-primary-nav__trigger');
    const dropdown = item.querySelector('.snf-primary-nav__dropdown');
    if (!trigger || !dropdown) return;

    trigger.addEventListener('click', (event) => {
      event.stopPropagation();
      const willOpen = dropdown.hidden;
      closeDropdowns(item);
      trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      dropdown.hidden = !willOpen;
      item.classList.toggle('is-open', willOpen);
    });
  });

  document.addEventListener('click', (event) => {
    if (!nav.contains(event.target)) {
      closeDropdowns();
      setNavOpen(false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    closeDropdowns();
    setNavOpen(false);
    if (menuToggle) menuToggle.focus();
  });

  const onViewportChange = () => {
    if (desktopQuery.matches) {
      resetMobileNav();
    }
  };

  if (typeof desktopQuery.addEventListener === 'function') {
    desktopQuery.addEventListener('change', onViewportChange);
  } else if (typeof desktopQuery.addListener === 'function') {
    desktopQuery.addListener(onViewportChange);
  }

  window.addEventListener('orientationchange', resetMobileNav);
}
