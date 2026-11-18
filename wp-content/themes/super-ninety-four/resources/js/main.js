import '../css/app.css';

import { initHeader } from './islands/header.js';

/**
 * Mount optional Vue root islands marked with [data-snf-vue].
 * Vue + shadcn components load only when a mount point exists.
 */
async function mountVueRoots() {
  const roots = document.querySelectorAll('[data-snf-vue]');
  if (!roots.length) return;

  const [{ createApp, h }, { default: Button }, { default: Card }] = await Promise.all([
    import('vue'),
    import('@/components/ui/Button.vue'),
    import('@/components/ui/Card.vue'),
  ]);

  roots.forEach((el) => {
    if (el.__snfVueMounted) return;
    el.__snfVueMounted = true;
    const app = createApp({
      setup() {
        return () => h('div', { class: 'snf-vue-root contents' }, el.childNodes);
      },
    });
    app.component('SnfButton', Button);
    app.component('SnfCard', Card);
    app.mount(el);
  });
}

/**
 * Lazily boot page islands so the homepage payload stays small.
 */
async function boot() {
  initHeader();

  const loaders = [];

  if (window.snfMarketplace && document.querySelector('#snf-search-events')) {
    loaders.push(
      import('./islands/marketplace.js').then((m) => m.initMarketplace())
    );
  }

  if (
    document.querySelector('[data-snf-media-hub]') ||
    document.querySelector('.snf-live') ||
    document.querySelector('.snf-audio-player') ||
    window.snfMedia
  ) {
    loaders.push(
      import('./islands/media-library.js').then((m) => m.initMediaLibrary())
    );
  }

  if (window.snfExpo && document.getElementById('snf-expo-map')) {
    loaders.push(
      import('./islands/expo-marketplace.js').then((m) => m.initExpoMarketplace())
    );
  }

  if (document.querySelector('.snf-dashboard')) {
    loaders.push(import('./islands/dashboard.js').then((m) => m.initDashboard()));
  }

  if (document.querySelector('.snf-hub') || window.snfHub) {
    loaders.push(import('./islands/hub.js').then((m) => m.initHub()));
  }

  if (document.getElementById('snf-msg') && window.snfMessages) {
    loaders.push(import('./islands/messages.js').then((m) => m.initMessages()));
  }

  if (document.querySelector('.snf-quote')) {
    loaders.push(
      import('./islands/service-quote.js').then((m) => m.initServiceQuote())
    );
  }

  if (document.querySelector('.snf-workshop')) {
    loaders.push(import('./islands/workshop.js').then((m) => m.initWorkshop()));
  }

  if (document.querySelector('.snf-event')) {
    loaders.push(
      import('./islands/event-single.js').then((m) => m.initEventSingle())
    );
  }

  loaders.push(mountVueRoots());

  await Promise.allSettled(loaders);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    void boot();
  });
} else {
  void boot();
}
