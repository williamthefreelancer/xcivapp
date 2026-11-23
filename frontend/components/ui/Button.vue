<script setup lang="ts">
import { computed } from 'vue'
import { cva } from 'class-variance-authority'
import { cn } from '~/utils/cn'

const props = defineProps({
  variant: { type: String, default: 'default' },
  size: { type: String, default: 'default' },
  type: { type: String, default: 'button' },
  href: { type: String, default: '' },
  to: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  class: { type: String, default: '' },
})

const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 no-underline',
  {
    variants: {
      variant: {
        default: 'bg-primary text-snf-admin-on-dark hover:bg-white/15 hover:text-white',
        secondary: 'bg-secondary text-secondary-foreground hover:bg-white/15 hover:text-white',
        outline: 'border border-border bg-transparent text-foreground hover:bg-white/10 hover:text-white hover:border-white/40',
        ghost: 'text-foreground hover:bg-white/10 hover:text-white',
        link: 'text-snf-admin-cream underline-offset-4 hover:underline hover:text-white',
      },
      size: {
        default: 'h-11 px-4 py-2 sm:h-10',
        sm: 'h-11 rounded-md px-3 sm:h-9',
        lg: 'h-11 rounded-md px-8',
        icon: 'size-11 sm:size-10',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
)

const classes = computed(() =>
  cn(buttonVariants({ variant: props.variant as any, size: props.size as any }), props.class),
)
</script>

<template>
  <NuxtLink v-if="to" :to="to" :class="classes" :aria-disabled="disabled || undefined">
    <slot />
  </NuxtLink>
  <a v-else-if="href" :href="href" :class="classes" :aria-disabled="disabled || undefined">
    <slot />
  </a>
  <button v-else :type="type as any" :disabled="disabled" :class="classes">
    <slot />
  </button>
</template>
