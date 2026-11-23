<script setup lang="ts">
definePageMeta({ layout: 'club' })
const { api } = useWpApi()
const { data, pending, error } = await useAsyncData('club-settings', () =>
  api<any>('snf/v1/club/overview?page=settings'),
)

function pulseVariant(status?: string) {
  if (status === 'ok') return 'ok'
  if (status === 'warn') return 'warn'
  if (status === 'bad') return 'bad'
  return 'outline'
}
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="m-0 text-xl font-semibold">{{ data?.title || 'Critical settings' }}</h2>
    <p class="m-0 text-sm text-muted-foreground">{{ data?.lede }}</p>
    <p v-if="pending" class="m-0 text-sm text-muted-foreground">Loading…</p>
    <p v-else-if="error" class="m-0 text-sm text-red-300">Could not load settings pulse.</p>
    <UiCard v-else>
      <UiCardHeader>
        <UiCardTitle>Integrations pulse</UiCardTitle>
      </UiCardHeader>
      <UiCardContent>
        <ul class="m-0 flex list-none flex-col">
          <li
            v-for="(row, i) in data?.pulse || []"
            :key="row.id || i"
            class="flex items-start justify-between gap-4 border-b border-border"
            style="margin-block: 0.75rem"
          >
            <div class="flex flex-col gap-1">
              <span class="text-sm font-medium">{{ row.label }}</span>
              <span class="text-xs text-muted-foreground">{{ row.detail }}</span>
            </div>
            <UiBadge :variant="pulseVariant(row.status)">{{ row.statusLabel || row.status }}</UiBadge>
          </li>
        </ul>
        <UiButton
          v-if="data?.settingsHref || data?.stripeHref"
          :href="data?.settingsHref || data?.stripeHref"
          size="sm"
          class="mt-4"
        >
          Open in WordPress admin
        </UiButton>
      </UiCardContent>
    </UiCard>
  </div>
</template>
