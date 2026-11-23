<script setup lang="ts">
const props = defineProps<{
  brand?: string
  title: string
  lede?: string
  primaryLabel?: string
  primaryTo?: string
  secondaryLabel?: string
  secondaryTo?: string
  compact?: boolean
}>()

const brandLabel = computed(() => (props.brand || '').trim())
const titleText = computed(() => (props.title || '').trim())
const titleIsBrand = computed(() => {
  if (!brandLabel.value || !titleText.value) return false
  return brandLabel.value.toLowerCase() === titleText.value.toLowerCase()
})
</script>

<template>
  <section
    class="snf-hero"
    :class="compact ? 'snf-hero--compact' : 'snf-hero--home'"
  >
    <div class="snf-hero__veil" aria-hidden="true" />
    <div class="snf-frame snf-hero__inner">
      <template v-if="!compact">
        <p v-if="brandLabel" class="snf-hero__brand snf-display">
          {{ brandLabel }}
        </p>
        <h1
          v-if="!titleIsBrand"
          class="snf-hero__title"
        >
          {{ titleText }}
        </h1>
        <h1 v-else class="sr-only">{{ titleText }}</h1>
        <p v-if="lede" class="snf-hero__lede">{{ lede }}</p>
      </template>

      <template v-else>
        <p v-if="brandLabel && !titleIsBrand" class="snf-hero__kicker">
          {{ brandLabel }}
        </p>
        <h1 class="snf-hero__title snf-hero__title--page">{{ titleText }}</h1>
        <p v-if="lede" class="snf-hero__lede">{{ lede }}</p>
      </template>

      <div
        v-if="primaryTo || secondaryTo"
        class="snf-hero__actions"
      >
        <UiButton v-if="primaryTo" :to="primaryTo" class="snf-hero__cta-primary">
          {{ primaryLabel || 'Continue' }}
        </UiButton>
        <UiButton v-if="secondaryTo" :to="secondaryTo" variant="outline">
          {{ secondaryLabel || 'Learn more' }}
        </UiButton>
      </div>
    </div>
  </section>
</template>
