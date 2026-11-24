<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    brand?: string
    showBrand?: boolean
    title: string
    titleLead?: string
    titleFocus?: string
    lede?: string
    primaryLabel?: string
    primaryTo?: string
    secondaryLabel?: string
    secondaryTo?: string
    compact?: boolean
  }>(),
  {
    showBrand: true,
  },
)

const brandLabel = computed(() => (props.brand || '').trim())
const titleText = computed(() => (props.title || '').trim())
const titleLead = computed(() => (props.titleLead || '').trim())
const titleFocus = computed(() => (props.titleFocus || '').trim())
const titleIsBrand = computed(() => {
  if (!brandLabel.value || !titleText.value) return false
  return brandLabel.value.toLowerCase() === titleText.value.toLowerCase()
})
const showBrandLabel = computed(() => props.showBrand && !!brandLabel.value)
const titleIsPrimary = computed(() => !showBrandLabel.value || !titleIsBrand.value)
const hasStatementParts = computed(() => !!(titleLead.value && titleFocus.value))
</script>

<template>
  <section
    class="snf-hero"
    :class="compact ? 'snf-hero--compact' : 'snf-hero--home'"
  >
    <div class="snf-hero__veil" aria-hidden="true" />
    <div class="snf-frame snf-hero__inner">
      <template v-if="!compact">
        <p v-if="showBrandLabel" class="snf-hero__brand snf-display">
          {{ brandLabel }}
        </p>
        <h1
          v-if="titleIsPrimary && hasStatementParts"
          class="snf-hero__title snf-hero__title--statement"
        >
          <span class="snf-hero__title-lead">{{ titleLead }}</span>
          <span class="snf-hero__title-focus">{{ titleFocus }}</span>
        </h1>
        <h1
          v-else-if="titleIsPrimary"
          class="snf-hero__title"
          :class="{ 'snf-hero__title--statement': !showBrandLabel }"
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
