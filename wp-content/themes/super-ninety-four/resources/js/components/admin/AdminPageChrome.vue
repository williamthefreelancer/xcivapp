<script setup>
import { computed } from 'vue';
import AdminShell from '@/components/admin/AdminShell.vue';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const notices = computed(() => props.data.notices || []);
</script>

<template>
  <div class="snf-admin-chrome flex flex-col gap-5">
    <AdminShell
      :brand="data.brand || 'Super Ninety Four'"
      :title="data.title"
      :lede="data.lede || ''"
      :nav="data.nav || []"
      :active="data.active || ''"
    />

    <div
      v-for="(notice, i) in notices"
      :key="i"
      class="rounded-md border border-border bg-card px-4 py-3 text-sm"
      :class="{
        'border-l-[3px] border-l-emerald-700 bg-emerald-600/5': notice.type === 'success',
        'border-l-[3px] border-l-amber-700 bg-amber-600/5': notice.type === 'warn',
        'border-l-[3px] border-l-red-700 bg-red-600/5': notice.type === 'error',
      }"
    >
      {{ notice.message }}
    </div>
  </div>
</template>
