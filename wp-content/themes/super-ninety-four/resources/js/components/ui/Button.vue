<script setup>
import { computed } from 'vue';
import { cva } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
  },
  size: {
    type: String,
    default: 'default',
  },
  type: {
    type: String,
    default: 'button',
  },
  href: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  class: {
    type: String,
    default: '',
  },
});

const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 no-underline',
  {
    variants: {
      variant: {
        default: 'bg-primary text-snf-admin-on-dark hover:bg-snf-admin-ink hover:text-snf-admin-on-dark',
        secondary: 'bg-secondary text-secondary-foreground hover:bg-snf-admin-ink hover:text-snf-admin-on-dark',
        outline: 'border border-border bg-transparent text-foreground hover:bg-snf-admin-ink hover:text-snf-admin-on-dark hover:border-snf-admin-ink',
        ghost: 'text-foreground hover:bg-snf-admin-ink hover:text-snf-admin-on-dark',
        link: 'text-primary underline-offset-4 hover:underline',
      },
      size: {
        default: 'h-10 px-4 py-2',
        sm: 'h-9 rounded-md px-3',
        lg: 'h-11 rounded-md px-8',
        icon: 'size-10',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  }
);

const classes = computed(() =>
  cn(buttonVariants({ variant: props.variant, size: props.size }), props.class)
);
</script>

<template>
  <a v-if="href" :href="href" :class="classes" :aria-disabled="disabled || undefined">
    <slot />
  </a>
  <button v-else :type="type" :disabled="disabled" :class="classes">
    <slot />
  </button>
</template>
