<script setup lang="ts">
import { computed } from 'vue'
import { cva } from 'class-variance-authority'
import { cn } from '~/utils/cn'

const props = defineProps({
  variant: { type: String, default: 'default' },
  class: { type: String, default: '' },
})

const badgeVariants = cva(
  'inline-flex items-center rounded-sm border text-[0.6875rem] font-semibold tracking-wide uppercase transition-colors',
  {
    variants: {
      variant: {
        default: 'border-transparent bg-primary text-snf-admin-on-dark',
        secondary: 'border-transparent bg-secondary text-secondary-foreground',
        outline: 'border-border text-foreground',
        ok: 'border-emerald-400/40 bg-emerald-500/20 text-emerald-200',
        warn: 'border-amber-400/40 bg-amber-500/20 text-amber-200',
        bad: 'border-red-400/40 bg-red-500/20 text-red-200',
        info: 'border-sky-400/40 bg-sky-500/20 text-sky-200',
        success: 'border-emerald-400/40 bg-emerald-500/20 text-emerald-200',
        neutral: 'border-border text-muted-foreground',
      },
    },
    defaultVariants: { variant: 'default' },
  },
)

const classes = computed(() =>
  cn(badgeVariants({ variant: props.variant as any }), 'my-0 mx-0', props.class),
)
</script>

<template>
  <span :class="classes" style="margin-inline: 0.5em; margin-block: 0.15em">
    <slot />
  </span>
</template>
