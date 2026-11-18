<script setup>
import { computed } from 'vue';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardDescription from '@/components/ui/CardDescription.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Button from '@/components/ui/Button.vue';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const pulse = computed(() => props.data.pulse || []);
const pages = computed(() => props.data.pageHealth || []);
const missing = computed(() => pages.value.filter((p) => !p.ok).length);
</script>

<template>
  <div class="snf-admin-settings-pulse mb-5 grid gap-4 lg:grid-cols-3">
    <Card class="lg:col-span-2">
      <CardHeader>
        <CardTitle>Integration status</CardTitle>
        <CardDescription>
          Live health for the services Critical settings and Stripe control.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <ul class="m-0 grid list-none gap-0 p-0 sm:grid-cols-2">
          <li
            v-for="(row, i) in pulse"
            :key="row.id || i"
            class="flex items-center justify-between gap-3 border-b border-border px-0 py-3 sm:odd:pr-4 sm:even:pl-4"
          >
            <span class="text-sm font-medium">{{ row.label }}</span>
            <Badge :variant="row.status || 'outline'">{{ row.value }}</Badge>
          </li>
        </ul>
        <div v-if="data.stripeHref" class="mt-4">
          <Button :href="data.stripeHref" size="sm" variant="outline">Open SNF Stripe</Button>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>Page health</CardTitle>
        <CardDescription>Auto-created Club surfaces.</CardDescription>
      </CardHeader>
      <CardContent>
        <p class="m-0 mb-3 text-[clamp(1.5rem,2vw,2rem)] font-bold tracking-tight">
          {{ pages.length - missing }}
          <span class="text-sm font-medium text-muted-foreground">/ {{ pages.length }} present</span>
        </p>
        <Badge :variant="missing ? 'warn' : 'ok'">
          {{ missing ? `${missing} missing` : 'All present' }}
        </Badge>
        <ul class="mt-4 m-0 flex list-none flex-col gap-2 p-0">
          <li
            v-for="page in pages.slice(0, 4)"
            :key="page.slug"
            class="flex items-center justify-between gap-2 text-sm"
          >
            <span class="truncate">{{ page.label }}</span>
            <Badge :variant="page.ok ? 'ok' : 'bad'" class="shrink-0">
              {{ page.ok ? 'OK' : 'Missing' }}
            </Badge>
          </li>
        </ul>
      </CardContent>
    </Card>
  </div>
</template>
