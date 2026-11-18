<script setup>
import { computed } from 'vue';
import { cva } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
  },
  class: {
    type: String,
    default: '',
  },
});

const badgeVariants = cva(
  'inline-flex items-center rounded-sm border px-2 py-0.5 text-[11px] font-semibold tracking-wide uppercase transition-colors',
  {
    variants: {
      variant: {
        default: 'border-transparent bg-primary text-primary-foreground',
        secondary: 'border-transparent bg-secondary text-secondary-foreground',
        outline: 'border-border text-foreground',
        ok: 'border-emerald-700/30 bg-emerald-600/10 text-emerald-800',
        warn: 'border-amber-700/35 bg-amber-600/10 text-amber-900',
        bad: 'border-red-700/30 bg-red-600/10 text-red-800',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  }
);

const classes = computed(() => cn(badgeVariants({ variant: props.variant }), props.class));
</script>

<template>
  <span :class="classes">
    <slot />
  </span>
</template>
