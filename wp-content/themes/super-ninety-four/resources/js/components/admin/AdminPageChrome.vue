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
  <div class="snf-admin-chrome flex flex-col gap-4 sm:gap-5">
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
      class="rounded-md border border-border bg-card m-[0.75rem_1rem] text-sm"
      :class="{
        'border-l-[0.1875rem] border-l-emerald-400 bg-emerald-500/20 text-white': notice.type === 'success',
        'border-l-[0.1875rem] border-l-amber-400 bg-amber-500/20 text-white': notice.type === 'warn',
        'border-l-[0.1875rem] border-l-red-400 bg-red-500/20 text-white': notice.type === 'error',
      }"
    >
      {{ notice.message }}
    </div>
  </div>
</template>
