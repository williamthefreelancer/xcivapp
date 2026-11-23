<script setup lang="ts">
const route = useRoute()
const hideChrome = computed(() => route.meta.hideChrome === true)
</script>

<template>
  <div class="snf-nuxt min-h-dvh">
    <SiteHeader v-if="!hideChrome" />
    <main class="snf-main">
      <slot />
    </main>
    <SiteFooter v-if="!hideChrome" />
  </div>
</template>
