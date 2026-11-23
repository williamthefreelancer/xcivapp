<script setup lang="ts">
import { cn } from '~/utils/cn'

defineProps({
  class: { type: String, default: '' },
})
</script>

<template>
  <div :class="cn('m-5 mt-4', $props.class)">
    <slot />
  </div>
</template>
