<script setup lang="ts">
import { cn } from '~/utils/cn'

defineProps({
  class: { type: String, default: '' },
})
</script>

<template>
  <div :class="cn('flex flex-col gap-1.5 m-5 mb-0', $props.class)">
    <slot />
  </div>
</template>
