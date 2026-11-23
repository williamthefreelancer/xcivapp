<script setup lang="ts">
export type Plan = {
  name: string
  price: string
  cadence?: string
  term?: string
  benefits?: string[]
}

defineProps<{
  title: string
  lede?: string
  initiation?: { label: string; amount: string; meta?: string }
  plans: Plan[]
  ctaLabel?: string
  ctaTo?: string
}>()
</script>

<template>
  <section id="pricing" class="snf-section snf-frame snf-home-reveal">
    <header class="snf-home-section__header snf-home-section__header--center">
      <h2 class="snf-home-section__title">{{ title }}</h2>
      <p v-if="lede" class="snf-home-section__lede">{{ lede }}</p>
    </header>
    <p
      v-if="initiation"
      class="m-0 text-snf-cream"
      style="text-align: center; margin-block: 0 1.35rem"
    >
      <span class="uppercase tracking-[0.1em]">{{ initiation.label }}</span>
      —
      <strong>{{ initiation.amount }}</strong>
      <span v-if="initiation.meta" class="text-white/60">
        ({{ initiation.meta }})
      </span>
    </p>
    <div class="snf-home-plans">
      <article
        v-for="(plan, i) in plans"
        :key="i"
        class="snf-home-plan"
      >
        <h3 class="snf-home-plan__name">{{ plan.name }}</h3>
        <p class="snf-home-plan__price">
          <span class="text-[1.2rem] font-semibold">{{ plan.price }}</span>
          <span class="text-white/70"> {{ plan.cadence }} {{ plan.term }}</span>
        </p>
        <ul v-if="plan.benefits?.length" class="snf-home-plan__list">
          <li v-for="(b, j) in plan.benefits" :key="j">{{ b }}</li>
        </ul>
      </article>
    </div>
    <div style="margin-block-start: 1.5rem; text-align: center">
      <UiButton :to="ctaTo || '/apply'">{{ ctaLabel || 'Apply for consideration' }}</UiButton>
    </div>
  </section>
</template>
