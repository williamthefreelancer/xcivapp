<script setup lang="ts">
const { api } = useWpApi()
const { me, fetchMe } = useAuth()
await fetchMe()

const { data, pending, error } = await useAsyncData('events-catalog', () =>
  api<{ events: any[]; selectEventsUrl?: string; checkoutUrl?: string }>('snf/v1/events'),
)
</script>

<template>
  <div class="snf-page-pad mx-auto w-full max-w-[73.75rem]" style="margin-inline: auto; margin-block-end: var(--snf-section-space-y)">
    <div style="margin-inline: var(--snf-space-x)" class="flex flex-col gap-5">
      <header class="flex flex-col gap-2" style="padding-block-start: clamp(1rem, 2.5vw, 1.5rem)">
        <p class="m-0 text-[0.6875rem] font-medium uppercase tracking-[0.14em] text-white/75">
          Select Events
        </p>
        <h1 class="m-0 font-display text-[clamp(1.75rem,3.2vw,2.35rem)] uppercase tracking-[0.02em]">
          Immersions & tickets
        </h1>
        <p class="m-0 max-w-2xl text-sm text-white/80">
          Browse circuit events. Add tickets to cart and checkout in Nuxt (Woo Store API).
        </p>
      </header>

      <p v-if="pending" class="m-0 text-sm text-muted-foreground">Loading events…</p>
      <p v-else-if="error" class="m-0 text-sm text-red-300">Could not load events.</p>

      <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <UiCard v-for="event in data?.events || []" :key="event.id">
          <div
            v-if="event.image"
            class="h-40 w-full bg-cover bg-center"
            :style="{ backgroundImage: `url(${event.image})` }"
          />
          <UiCardHeader>
            <UiCardTitle>{{ event.title }}</UiCardTitle>
            <UiCardDescription>
              {{ [event.when, event.location].filter(Boolean).join(' · ') || 'Date TBA' }}
            </UiCardDescription>
          </UiCardHeader>
          <UiCardContent class="flex flex-wrap gap-2">
            <UiButton v-if="event.path || event.url" :to="event.path || `/events/${event.slug}`" size="sm">View</UiButton>
            <UiButton
              v-if="event.addToCartUrl"
              :href="event.addToCartUrl"
              size="sm"
              variant="secondary"
            >
              Add to cart
            </UiButton>
            <UiButton v-else-if="event.permalink" :to="`/events/${event.slug || event.id}`" size="sm" variant="outline">
              Details
            </UiButton>
          </UiCardContent>
        </UiCard>
      </div>

      <div class="flex flex-wrap gap-2">
        <UiButton v-if="me" to="/app" variant="outline" size="sm">Member dashboard</UiButton>
        <UiButton v-if="data?.selectEventsUrl" :href="data.selectEventsUrl" variant="ghost" size="sm">
          Classic Select Events
        </UiButton>
      </div>
    </div>
  </div>
</template>
