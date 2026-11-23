<script setup>
import { cn } from '@/lib/utils';

defineProps({
  orientation: {
    type: String,
    default: 'horizontal',
  },
  class: {
    type: String,
    default: '',
  },
});
</script>

<template>
  <div
    role="separator"
    :aria-orientation="orientation"
    :class="
      cn(
        'shrink-0 bg-border',
        orientation === 'vertical' ? 'h-full w-px' : 'h-px w-full',
        $props.class
      )
    "
  />
</template>
