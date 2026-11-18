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
  <div :class="cn('m-5 mt-4', $props.class)">
    <slot />
  </div>
</template>
