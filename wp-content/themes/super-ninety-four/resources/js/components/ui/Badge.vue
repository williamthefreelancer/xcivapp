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
  'inline-flex items-center rounded-sm border px-2 py-0.5 text-[0.6875rem] font-semibold tracking-wide uppercase transition-colors',
  {
    variants: {
      variant: {
        default: 'border-transparent bg-primary text-snf-admin-on-dark hover:bg-snf-admin-ink hover:text-snf-admin-on-dark',
        secondary: 'border-transparent bg-secondary text-secondary-foreground hover:bg-snf-admin-ink hover:text-snf-admin-on-dark',
        outline: 'border-border text-foreground hover:bg-snf-admin-ink hover:text-snf-admin-on-dark hover:border-snf-admin-ink',
        ok: 'border-emerald-400/40 bg-emerald-500/20 text-emerald-200 hover:bg-emerald-700 hover:text-snf-admin-on-dark hover:border-emerald-500',
        warn: 'border-amber-400/40 bg-amber-500/20 text-amber-200 hover:bg-amber-700 hover:text-snf-admin-on-dark hover:border-amber-500',
        bad: 'border-red-400/40 bg-red-500/20 text-red-200 hover:bg-red-700 hover:text-snf-admin-on-dark hover:border-red-500',
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
