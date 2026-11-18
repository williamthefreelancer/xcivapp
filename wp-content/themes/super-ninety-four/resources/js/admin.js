import '../css/admin.css';

import { createApp, h } from 'vue';
import AdminOverview from '@/components/admin/AdminOverview.vue';
import AdminPageChrome from '@/components/admin/AdminPageChrome.vue';
import AdminSettingsPulse from '@/components/admin/AdminSettingsPulse.vue';

function readChromePayload(base) {
  const el = document.getElementById('snf-admin-chrome-data');
  if (!el || !el.textContent) {
    return base;
  }
  try {
    const parsed = JSON.parse(el.textContent);
    return {
      ...(base || {}),
      ...parsed,
      brand: parsed.brand || (base && base.brand) || 'Super Ninety Four',
      nav: parsed.nav || (base && base.nav) || [],
      notices: parsed.notices || (base && base.notices) || [],
    };
  } catch (e) {
    return base;
  }
}

function bootAdmin() {
  const payload = window.snfAdminDashboard || null;

  const overviewEl = document.getElementById('snf-admin-overview');
  if (overviewEl && payload && payload.page === 'overview') {
    createApp({
      render: () => h(AdminOverview, { data: payload }),
    }).mount(overviewEl);
  }

  const chromeEl = document.getElementById('snf-admin-chrome');
  if (chromeEl) {
    const chromeData = readChromePayload(payload);
    if (chromeData && chromeData.title) {
      createApp({
        render: () => h(AdminPageChrome, { data: chromeData }),
      }).mount(chromeEl);
    }
  }

  const settingsEl = document.getElementById('snf-admin-settings-pulse');
  if (settingsEl && payload && (payload.page === 'settings' || payload.page === 'chrome')) {
    createApp({
      render: () => h(AdminSettingsPulse, { data: payload }),
    }).mount(settingsEl);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootAdmin);
} else {
  bootAdmin();
}
