<script setup lang="ts">
type Card = {
  eyebrow?: string
  title?: string
  meta?: string
  status?: string
  tone?: string
  url?: string
  action?: string
  image?: string
}

defineProps<{
  panels: Array<{
    id?: string
    title?: string
    lede?: string
    view_all?: string
    cards?: Card[]
  }>
}>()

function toneVariant(tone?: string) {
  if (tone === 'success' || tone === 'ok') return 'ok'
  if (tone === 'warn') return 'warn'
  if (tone === 'error' || tone === 'bad') return 'bad'
  if (tone === 'info') return 'info'
  return 'neutral'
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <section v-for="panel in panels" :key="panel.id || panel.title" class="flex flex-col gap-3">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="flex flex-col gap-1">
          <h2 class="m-0 text-lg font-semibold tracking-tight">{{ panel.title }}</h2>
          <p v-if="panel.lede" class="m-0 text-sm text-muted-foreground">{{ panel.lede }}</p>
        </div>
        <UiButton v-if="panel.view_all" :href="panel.view_all" variant="outline" size="sm">
          View all
        </UiButton>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <UiCard v-for="(card, i) in panel.cards || []" :key="i" class="overflow-hidden">
          <div
            v-if="card.image"
            class="h-36 w-full bg-cover bg-center"
            :style="{ backgroundImage: `url(${card.image})` }"
          />
          <UiCardHeader>
            <p class="m-0 text-[0.6875rem] font-semibold uppercase tracking-wider text-muted-foreground">
              {{ card.eyebrow }}
            </p>
            <UiCardTitle>{{ card.title }}</UiCardTitle>
            <UiCardDescription v-if="card.meta">{{ card.meta }}</UiCardDescription>
          </UiCardHeader>
          <UiCardContent class="flex flex-wrap items-center gap-2">
            <UiBadge v-if="card.status" :variant="toneVariant(card.tone)">
              {{ card.status }}
            </UiBadge>
            <UiButton v-if="card.url" :href="card.url" size="sm" variant="secondary">
              {{ card.action || 'Open' }}
            </UiButton>
          </UiCardContent>
        </UiCard>
      </div>
    </section>
  </div>
</template>
