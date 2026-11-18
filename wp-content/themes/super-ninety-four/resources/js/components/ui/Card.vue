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
  <div
    :class="
      cn(
        'rounded-lg border border-border bg-card text-card-foreground shadow-[0_0.625rem_1.75rem_rgba(0,0,0,0.18)]',
        $props.class
      )
    "
  >
    <slot />
  </div>
</template>
