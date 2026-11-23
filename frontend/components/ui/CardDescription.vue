<script setup lang="ts">
import { cn } from '~/utils/cn'

defineProps({
  class: { type: String, default: '' },
})
</script>

<template>
  <p :class="cn('m-0 text-sm text-muted-foreground', $props.class)">
    <slot />
  </p>
</template>
