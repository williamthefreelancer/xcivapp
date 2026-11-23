<script setup lang="ts">
const route = useRoute()
const slug = computed(() => String(route.params.slug || ''))
const { api } = useWpApi()

const { data, error } = await useAsyncData(
  () => `legal-${slug.value}`,
  () => api<{ title?: string; content?: string }>(`snf/v1/content/legal/${slug.value}`),
  { watch: [slug] },
)

useSeoMeta({ title: () => `${data.value?.title || 'Legal'} — SUPER NINETY FOUR` })
</script>

<template>
  <div>
    <SiteHero brand="SUPER NINETY FOUR" :title="data?.title || 'Legal'" compact />
    <section class="snf-section snf-frame" style="max-width: var(--snf-content-w)">
      <p v-if="error" class="m-0 text-white/70">This policy page is not available yet.</p>
      <div v-else-if="data?.content" class="max-w-none text-white/85 [&_a]:underline [&_h2]:font-display [&_h2]:uppercase" v-html="data.content" />
    </section>
  </div>
</template>
