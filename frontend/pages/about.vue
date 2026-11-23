<script setup lang="ts">
const { api } = useWpApi()
const { data } = await useAsyncData('about', () =>
  api<{ content?: { who?: { title: string; body: string }; founder?: { title: string; name: string; body: string } } }>(
    'snf/v1/content/about',
  ),
)
const content = computed(() => data.value?.content)
useSeoMeta({ title: 'About — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero brand="SUPER NINETY FOUR" title="About" compact />
    <section
      v-if="content?.who || content?.founder"
      class="snf-section snf-frame"
      style="max-width: var(--snf-content-w)"
    >
      <article v-if="content?.who" class="m-0">
        <h2 class="snf-home-section__title">{{ content.who.title }}</h2>
        <p class="snf-lede m-0" style="margin-block-start: 1rem; max-width: none">{{ content.who.body }}</p>
      </article>
      <article v-if="content?.founder" class="m-0" style="margin-block-start: var(--snf-section-space-y)">
        <h2 class="snf-home-section__title">{{ content.founder.title }}</h2>
        <p class="m-0 text-snf-cream" style="margin-block-start: 0.75rem">{{ content.founder.name }}</p>
        <p class="snf-lede m-0" style="margin-block-start: 1rem; max-width: none">{{ content.founder.body }}</p>
      </article>
    </section>
  </div>
</template>
