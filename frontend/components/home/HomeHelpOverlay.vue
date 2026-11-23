<script setup lang="ts">
export type HelpCard = {
  title: string
  meta?: string
  body?: string
  url?: string
  image?: string
  cta?: string
}

defineProps<{
  title: string
  lede?: string
  cards: HelpCard[]
}>()

function toPath(url?: string) {
  if (!url) return '#'
  if (url.startsWith('/') || url.startsWith('#')) return url
  try {
    return new URL(url).pathname || '#'
  } catch {
    return url
  }
}
</script>

<template>
  <section id="help" class="snf-section snf-frame snf-home-reveal">
    <header class="snf-home-section__header snf-home-section__header--center">
      <h2 class="snf-home-section__title">{{ title }}</h2>
      <p v-if="lede" class="snf-home-section__lede">{{ lede }}</p>
    </header>
    <div class="snf-home-overlay-grid">
      <NuxtLink
        v-for="(card, i) in cards"
        :key="i"
        :to="toPath(card.url)"
        class="snf-home-overlay-card"
      >
        <img
          v-if="card.image"
          :src="card.image"
          :alt="card.title"
          class="snf-home-overlay-card__img"
          loading="lazy"
          decoding="async"
        >
        <span class="snf-home-overlay-card__veil" aria-hidden="true" />
        <span class="snf-home-overlay-card__copy">
          <span class="snf-home-overlay-card__title">{{ card.title }}</span>
          <span v-if="card.body" class="snf-home-overlay-card__body">{{ card.body }}</span>
          <span v-else-if="card.meta" class="snf-home-overlay-card__body">{{ card.meta }}</span>
          <span class="snf-home-overlay-card__cta">{{ card.cta || 'Open the desk' }}</span>
        </span>
      </NuxtLink>
    </div>
  </section>
</template>
