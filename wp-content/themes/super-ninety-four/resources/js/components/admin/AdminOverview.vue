<script setup>
import { computed } from 'vue';
import AdminShell from '@/components/admin/AdminShell.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import CardDescription from '@/components/ui/CardDescription.vue';
import CardContent from '@/components/ui/CardContent.vue';
import Separator from '@/components/ui/Separator.vue';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const stats = computed(() => props.data.stats || []);
const desks = computed(() => props.data.desks || []);
const pulse = computed(() => props.data.pulse || []);
const notices = computed(() => props.data.notices || []);
</script>

<template>
  <div class="snf-admin-vue flex flex-col gap-5">
    <AdminShell
      :brand="data.brand"
      :title="data.title"
      :lede="data.lede"
      :nav="data.nav"
      :active="data.active"
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

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
      <Card
        v-for="(stat, i) in stats"
        :key="i"
        class="snf-admin-stat border-t-[3px] border-t-primary"
        :style="{ animationDelay: `${80 + i * 40}ms` }"
      >
        <CardContent class="p-4">
          <span class="block text-[clamp(1.25rem,2vw,1.75rem)] font-bold leading-none tracking-tight">
            {{ stat.value }}
          </span>
          <span class="mt-2 block text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
            {{ stat.label }}
          </span>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <Card class="snf-admin-panel">
        <CardHeader>
          <CardTitle>{{ data.desksTitle || 'Content desks' }}</CardTitle>
          <CardDescription>
            {{ data.desksLede || 'Jump into CPT lists or dedicated dashboards.' }}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex flex-col gap-4">
            <div
              v-for="desk in desks"
              :key="desk.id"
              class="flex flex-col gap-2"
            >
              <p class="m-0 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                {{ desk.label }}
              </p>
              <div class="flex flex-wrap gap-2">
                <Button
                  v-if="desk.dashboardHref"
                  :href="desk.dashboardHref"
                  size="sm"
                >
                  {{ desk.dashboardLabel || 'Dashboard' }}
                </Button>
                <Button
                  v-if="desk.listHref"
                  :href="desk.listHref"
                  variant="outline"
                  size="sm"
                >
                  {{ desk.listLabel || 'All' }}
                </Button>
              </div>
              <Separator v-if="desk !== desks[desks.length - 1]" class="mt-1" />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="snf-admin-panel">
        <CardHeader>
          <CardTitle>{{ data.pulseTitle || 'Integrations pulse' }}</CardTitle>
          <CardDescription>
            {{ data.pulseLede || 'Payments, maps, and media defaults that unlock front-end features.' }}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <ul class="m-0 flex list-none flex-col gap-0 p-0">
            <li
              v-for="(row, i) in pulse"
              :key="row.id || i"
              class="flex items-start justify-between gap-4 border-b border-border py-3 last:border-b-0"
            >
              <span class="text-sm font-medium">{{ row.label }}</span>
              <Badge :variant="row.status || 'outline'">{{ row.value }}</Badge>
            </li>
          </ul>
          <div class="mt-4 flex flex-wrap gap-2">
            <Button
              v-if="data.settingsHref"
              :href="data.settingsHref"
              size="sm"
            >
              Critical settings
            </Button>
            <Button
              v-if="data.stripeHref"
              :href="data.stripeHref"
              variant="outline"
              size="sm"
            >
              SNF Stripe
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<style scoped>
.snf-admin-stat,
.snf-admin-panel {
  animation: snf-admin-fade-up 0.55s ease both;
}

@keyframes snf-admin-fade-up {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .snf-admin-stat,
  .snf-admin-panel {
    animation: none;
  }
}
</style>
