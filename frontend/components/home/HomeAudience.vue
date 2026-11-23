<script setup lang="ts">
export type AudienceCard = {
  title: string
  body: string
}

defineProps<{
  forTitle: string
  forCards: AudienceCard[]
  notForTitle: string
  notForCards: AudienceCard[]
}>()
</script>

<template>
  <section id="fit" class="snf-section snf-frame snf-home-reveal">
    <div class="snf-home-audience">
      <div>
        <h2 class="snf-home-section__title" style="text-align: center; margin-bottom: 1.15rem">
          {{ forTitle }}
        </h2>
        <div class="snf-home-audience__cards">
          <UiCard
            v-for="(card, i) in forCards"
            :key="i"
            class="snf-home-audience__card border-white/12 bg-[rgba(26,26,26,0.55)] shadow-none"
          >
            <UiCardHeader class="p-4 pb-1">
              <UiCardTitle class="text-[0.9375rem] font-semibold tracking-wide">
                {{ card.title }}
              </UiCardTitle>
            </UiCardHeader>
            <UiCardContent class="p-4 pt-0">
              <p class="m-0 text-white/70" style="line-height: 1.45">{{ card.body }}</p>
            </UiCardContent>
          </UiCard>
        </div>
      </div>

      <div>
        <h2 class="snf-home-section__title" style="text-align: center; margin-bottom: 1.15rem">
          {{ notForTitle }}
        </h2>
        <div class="snf-home-accordion">
          <details
            v-for="(card, i) in notForCards"
            :key="i"
            class="snf-home-accordion__item"
          >
            <summary class="snf-home-accordion__summary">{{ card.title }}</summary>
            <p class="snf-home-accordion__body">{{ card.body }}</p>
          </details>
        </div>
      </div>
    </div>
  </section>
</template>
