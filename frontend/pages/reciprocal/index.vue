<script setup lang="ts">
const { api } = useWpApi()
const { data } = await useAsyncData('reciprocal-hub', () =>
  api<{ title?: string; lede?: string; clubs?: any[] }>('snf/v1/reciprocal'),
)

useSeoMeta({ title: 'Reciprocal programs — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero
      brand="SUPER NINETY FOUR"
      :title="data?.title || 'Reciprocal programs'"
      :lede="data?.lede"
      compact
    />
    <section class="snf-section snf-frame grid gap-6 sm:grid-cols-2">
      <article v-for="club in data?.clubs || []" :key="club.slug" class="m-0">
        <img
          v-if="club.card_image"
          :src="club.card_image"
          :alt="club.title"
          class="m-0 w-full object-cover"
          style="aspect-ratio: 16 / 10"
        >
        <h2 class="m-0 font-display text-[1.5rem] uppercase" style="margin-block-start: 0.75rem">
          {{ club.title }}
        </h2>
        <p
          v-if="club.scope"
          class="m-0 text-[0.7rem] uppercase tracking-[0.1em] text-white/65"
          style="margin-block-start: 0.4rem"
        >
          {{ club.scope }}
        </p>
        <p class="m-0 text-white/70" style="margin-block-start: 0.5rem">{{ club.lede }}</p>
        <UiButton :to="club.path || `/reciprocal/${club.slug}`" style="margin-block-start: 0.85rem">
          View club
        </UiButton>
      </article>
    </section>
  </div>
</template>
