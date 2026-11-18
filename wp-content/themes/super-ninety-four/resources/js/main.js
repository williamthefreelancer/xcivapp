import '../css/app.css';

import { createApp, h } from 'vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';

import { initHeader } from './islands/header.js';
import { initMarketplace } from './islands/marketplace.js';
import { initMediaLibrary } from './islands/media-library.js';
import { initExpoMarketplace } from './islands/expo-marketplace.js';
import { initDashboard } from './islands/dashboard.js';
import { initHub } from './islands/hub.js';
import { initMessages } from './islands/messages.js';
import { initServiceQuote } from './islands/service-quote.js';
import { initWorkshop } from './islands/workshop.js';
import { initEventSingle } from './islands/event-single.js';

/**
 * Mount optional Vue root islands marked with [data-snf-vue].
 * Provides shadcn-vue Button/Card globally for progressive enhancement.
 */
function mountVueRoots() {
  document.querySelectorAll('[data-snf-vue]').forEach((el) => {
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

function boot() {
  initHeader();
  initMarketplace();
  initMediaLibrary();
  initExpoMarketplace();
  initDashboard();
  initHub();
  initMessages();
  initServiceQuote();
  initWorkshop();
  initEventSingle();
  mountVueRoots();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}

export { Button, Card };
