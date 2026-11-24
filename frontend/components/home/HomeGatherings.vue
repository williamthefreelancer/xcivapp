<script setup lang="ts">
export type GatheringCard = {
  slug?: string
  title: string
  body?: string
  price?: string
  url?: string
  image?: string
  cta?: string
}

defineProps<{
  title: string
  lede?: string
  cards: GatheringCard[]
}>()

function toPath(url?: string) {
  if (!url) return '#'
  if (url.startsWith('/') || url.startsWith('#')) return url
  try {
    const path = new URL(url).pathname || '#'
    return path.replace(/\/product\//, '/events/').replace(/\/$/, '') || path
  } catch {
    return url
  }
}
</script>

<template>
  <section id="gatherings" class="snf-section snf-frame snf-home-reveal">
    <header class="snf-home-section__header snf-home-section__header--center">
      <h2 class="snf-home-section__title">{{ title }}</h2>
      <p v-if="lede" class="snf-home-section__lede">{{ lede }}</p>
    </header>
    <div class="snf-home-gatherings snf-home-gatherings--two">
      <article v-for="(card, i) in cards" :key="card.slug || i" class="snf-home-gathering">
        <div v-if="card.image" class="snf-home-gathering__media">
          <img
            :src="card.image"
            :alt="card.title"
            class="snf-home-gathering__img"
            loading="lazy"
            decoding="async"
          >
        </div>
        <div class="snf-home-gathering__copy">
          <h3 class="snf-home-gathering__title">{{ card.title }}</h3>
          <p v-if="card.price" class="snf-home-gathering__price">{{ card.price }}</p>
          <p v-if="card.body" class="snf-home-gathering__body">{{ card.body }}</p>
          <UiButton class="snf-home-gathering__cta" :to="toPath(card.url)" size="sm">
            {{ card.cta || 'View event' }}
          </UiButton>
        </div>
      </article>
    </div>
  </section>
</template>
