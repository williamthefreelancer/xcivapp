<script setup lang="ts">
const { api } = useWpApi()
const { data } = await useAsyncData('inquiries', () =>
  api<{ title?: string; content?: string; excerpt?: string }>('snf/v1/content/page/inquiries').catch(() => null),
)

useSeoMeta({ title: 'Service inquiries — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero
      brand="SUPER NINETY FOUR"
      :title="data?.title || 'Service inquiries'"
      lede="Property managers and clients — open the desk that matches the service you need."
      primary-label="Reciprocal programs"
      primary-to="/reciprocal"
      secondary-label="Contact"
      secondary-to="/contact"
      compact
    />
    <section class="snf-section snf-frame" style="max-width: var(--snf-content-w)">
      <div v-if="data?.content" class="prose prose-invert max-w-none" v-html="data.content" />
      <div v-else class="grid gap-4 md:grid-cols-2">
        <article class="m-0 border-t border-white/15" style="padding-block-start: 0.85rem">
          <h2 class="m-0 text-[1.125rem] font-semibold uppercase">Litter pickup & grounds</h2>
          <p class="m-0 text-white/70" style="margin-block-start: 0.5rem">Commercial grounds and parking-lot cleaning on the circuit.</p>
          <UiButton
            href="https://www.litterpickupserviceatsuperninetyfour.com/"
            variant="outline"
            style="margin-block-start: 0.75rem"
          >
            Open litter desk
          </UiButton>
        </article>
        <article class="m-0 border-t border-white/15" style="padding-block-start: 0.85rem">
          <h2 class="m-0 text-[1.125rem] font-semibold uppercase">Line sitting & courier</h2>
          <p class="m-0 text-white/70" style="margin-block-start: 0.5rem">Professional line sitting and courier for properties and events.</p>
          <UiButton
            href="https://www.xcivcourier.com/"
            variant="outline"
            style="margin-block-start: 0.75rem"
          >
            Open courier desk
          </UiButton>
        </article>
        <article class="m-0 border-t border-white/15" style="padding-block-start: 0.85rem">
          <h2 class="m-0 text-[1.125rem] font-semibold uppercase">Reciprocal club inquiry</h2>
          <p class="m-0 text-white/70" style="margin-block-start: 0.5rem">Back Creek, Wyoma Square, City Clubs.</p>
          <UiButton to="/reciprocal" variant="outline" style="margin-block-start: 0.75rem">Open reciprocal hub</UiButton>
        </article>
      </div>
    </section>
  </div>
</template>
