import '../css/admin.css';

import { createApp, h } from 'vue';
import AdminOverview from '@/components/admin/AdminOverview.vue';
import AdminSettingsPulse from '@/components/admin/AdminSettingsPulse.vue';

function bootAdmin() {
  const payload = window.snfAdminDashboard || null;
  if (!payload || typeof payload !== 'object') {
    return;
  }

  const overviewEl = document.getElementById('snf-admin-overview');
  if (overviewEl && payload.page === 'overview') {
    createApp({
      render: () => h(AdminOverview, { data: payload }),
    }).mount(overviewEl);
  }

  const settingsEl = document.getElementById('snf-admin-settings-pulse');
  if (settingsEl && payload.page === 'settings') {
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
