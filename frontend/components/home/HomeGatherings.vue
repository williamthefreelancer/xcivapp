<script setup lang="ts">
export type GatheringCard = {
  title: string
  body?: string
  price?: string
  url?: string
  image?: string
  cta?: string
  slug?: string
}

defineProps<{
  title: string
  lede?: string
  cards: GatheringCard[]
}>()

function toPath(url?: string) {
  if (!url) return '/apply'
  if (url.startsWith('/') || url.startsWith('#')) return url
  try {
    return new URL(url).pathname || '/apply'
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
    <div class="snf-home-gatherings">
      <article
        v-for="(card, i) in cards"
        :key="card.slug || i"
        class="snf-home-gathering"
      >
        <div class="snf-home-gathering__media">
          <img
            v-if="card.image"
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
          <UiButton :to="toPath(card.url)" variant="outline" size="sm" style="margin-block-start: 0.85rem">
            {{ card.cta || 'View event' }}
          </UiButton>
        </div>
      </article>
    </div>
  </section>
</template>
