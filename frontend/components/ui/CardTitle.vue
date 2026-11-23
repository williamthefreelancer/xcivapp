<script setup lang="ts">
import { cn } from '~/utils/cn'

defineProps({
  class: { type: String, default: '' },
})
</script>

<template>
  <h2 :class="cn('m-0 text-base font-semibold tracking-tight text-foreground', $props.class)">
    <slot />
  </h2>
</template>
