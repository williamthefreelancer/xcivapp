<script setup lang="ts">
definePageMeta({ layout: 'club' })

const { api } = useWpApi()
const { data, error, pending } = await useAsyncData('club-overview', () =>
  api<any>('snf/v1/club/overview'),
)

function pulseVariant(status?: string) {
  if (status === 'ok') return 'ok'
  if (status === 'warn') return 'warn'
  if (status === 'bad') return 'bad'
  return 'outline'
}
</script>

<template>
  <div class="flex flex-col gap-5">
    <p v-if="pending" class="m-0 text-sm text-muted-foreground">Loading club overview…</p>
    <p v-else-if="error" class="m-0 text-sm text-red-300">Could not load club overview.</p>
    <template v-else>
      <div
        v-for="(notice, i) in data?.notices || []"
        :key="i"
        class="rounded-md border border-border bg-card text-sm"
        style="margin: 0; padding-block: 0.75rem; padding-inline: 1rem"
      >
        {{ notice.message }}
      </div>

      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <UiCard
          v-for="(stat, i) in data?.stats || []"
          :key="i"
          class="border-t-[0.1875rem] border-t-primary"
        >
          <UiCardContent>
            <span class="block text-[clamp(1.2rem,2vw,1.75rem)] font-bold leading-none tracking-tight">
              {{ stat.value }}
            </span>
            <span class="mt-2 block text-[0.6875rem] font-semibold uppercase tracking-wider text-muted-foreground">
              {{ stat.label }}
            </span>
          </UiCardContent>
        </UiCard>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <UiCard>
          <UiCardHeader>
            <UiCardTitle>{{ data?.desksTitle || 'Content desks' }}</UiCardTitle>
            <UiCardDescription>{{ data?.desksLede }}</UiCardDescription>
          </UiCardHeader>
          <UiCardContent>
            <div class="flex flex-col gap-4">
              <div v-for="desk in data?.desks || []" :key="desk.id" class="flex flex-col gap-2">
                <p class="m-0 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                  {{ desk.label }}
                </p>
                <div class="flex flex-wrap gap-2">
                  <UiButton
                    v-if="desk.nuxtPath"
                    :to="desk.nuxtPath"
                    size="sm"
                  >
                    {{ desk.dashboardLabel || 'Dashboard' }}
                  </UiButton>
                  <UiButton
                    v-else-if="desk.dashboardHref"
                    :href="desk.dashboardHref"
                    size="sm"
                  >
                    {{ desk.dashboardLabel || 'Dashboard' }}
                  </UiButton>
                  <UiButton
                    v-if="desk.listHref"
                    :href="desk.listHref"
                    variant="outline"
                    size="sm"
                  >
                    {{ desk.listLabel || 'All' }}
                  </UiButton>
                </div>
                <UiSeparator />
              </div>
            </div>
          </UiCardContent>
        </UiCard>

        <UiCard>
          <UiCardHeader>
            <UiCardTitle>{{ data?.pulseTitle || 'Integrations pulse' }}</UiCardTitle>
            <UiCardDescription>{{ data?.pulseLede }}</UiCardDescription>
          </UiCardHeader>
          <UiCardContent>
            <ul class="m-0 flex list-none flex-col gap-0">
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
            <div class="flex flex-wrap gap-2" style="margin-block: 1rem 0">
              <UiButton v-if="data?.settingsHref" :href="data.settingsHref" size="sm" variant="outline">
                WP critical settings
              </UiButton>
              <UiButton to="/club/settings" size="sm">Club settings</UiButton>
              <UiButton v-if="data?.stripeHref" :href="data.stripeHref" size="sm" variant="secondary">
                Stripe
              </UiButton>
            </div>
          </UiCardContent>
        </UiCard>
      </div>
    </template>
  </div>
</template>
