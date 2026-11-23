<script setup lang="ts">
const route = useRoute()
const slug = computed(() => String(route.params.slug || ''))
const { api } = useWpApi()

const { data, error } = await useAsyncData(
  () => `reciprocal-${slug.value}`,
  () => api<any>(`snf/v1/reciprocal/${slug.value}`),
  { watch: [slug] },
)

useSeoMeta({ title: () => `${data.value?.title || 'Reciprocal'} — SUPER NINETY FOUR` })
</script>

<template>
  <div>
    <SiteHero
      :brand="data?.mark_label || 'SUPER NINETY FOUR'"
      :title="data?.hero_heading || data?.title || 'Reciprocal club'"
      :lede="data?.hero_lede || data?.lede"
      :primary-label="data?.cta_label || 'Apply'"
      :primary-to="data?.cta_path || '/apply'"
    />
    <section v-if="error" class="snf-section snf-frame">
      <p class="m-0 text-white/70">Club not found.</p>
    </section>
    <template v-else-if="data">
      <section v-if="data.scope" class="snf-section snf-frame" style="padding-block: 0 0">
        <p class="m-0 text-[0.8rem] uppercase tracking-[0.12em] text-white/65">{{ data.scope }}</p>
      </section>
      <section class="snf-section snf-frame" style="max-width: var(--snf-content-w)">
        <h2 class="snf-display m-0">{{ data.about_title }}</h2>
        <p class="snf-lede m-0" style="margin-block-start: 1rem; max-width: none">{{ data.about_body }}</p>
      </section>
      <section v-if="data.why_items?.length" class="snf-section snf-frame">
        <h2 class="m-0 font-display text-[clamp(1.5rem,3vw,2.25rem)] uppercase">Why consider</h2>
        <p v-if="data.why_lede" class="snf-lede m-0" style="margin-block-start: 0.75rem">{{ data.why_lede }}</p>
        <div class="grid gap-4 md:grid-cols-2" style="margin-block-start: 1.25rem">
          <article
            v-for="(item, i) in data.why_items"
            :key="i"
            class="m-0 border-t border-white/15"
            style="padding-block-start: 0.85rem"
          >
            <h3 class="m-0 text-[1rem] font-semibold">{{ item.title }}</h3>
            <p class="m-0 text-white/70" style="margin-block-start: 0.35rem">{{ item.body }}</p>
          </article>
        </div>
      </section>
      <section class="snf-section snf-frame">
        <UiButton :to="data.cta_path || '/apply'">{{ data.cta_label || 'Apply' }}</UiButton>
      </section>
    </template>
  </div>
</template>
