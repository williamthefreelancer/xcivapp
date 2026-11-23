<script setup>
import { cn } from '@/lib/utils';

defineProps({
  class: {
    type: String,
    default: '',
  },
});
</script>

<template>
  <h2 :class="cn('text-base font-semibold tracking-tight text-foreground', $props.class)">
    <slot />
  </h2>
</template>
