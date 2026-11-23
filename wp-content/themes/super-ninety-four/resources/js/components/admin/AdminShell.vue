<script setup>
import { cn } from '@/lib/utils';

defineProps({
  brand: {
    type: String,
    default: 'Super Ninety Four',
  },
  title: {
    type: String,
    required: true,
  },
  lede: {
    type: String,
    default: '',
  },
  nav: {
    type: Array,
    default: () => [],
  },
  active: {
    type: String,
    default: '',
  },
});
</script>

<template>
  <header
    class="snf-admin-shell__hero relative mb-4 overflow-hidden rounded-lg px-4 py-[1.15rem] text-snf-admin-on-dark sm:mb-5 sm:px-6 sm:py-6"
  >
    <p class="mb-2 text-[0.6875rem] font-medium uppercase tracking-[0.14em] text-white/75">
      {{ brand }}
    </p>
    <h1 class="m-0 mb-2 text-[clamp(1.35rem,2.2vw,1.95rem)] font-semibold leading-tight tracking-tight text-snf-admin-on-dark">
      {{ title }}
    </h1>
    <p v-if="lede" class="m-0 max-w-2xl text-[0.95rem] text-white/80 sm:text-[0.98rem]">
      {{ lede }}
    </p>
  </header>

  <nav
    v-if="nav.length"
    class="snf-admin-shell__nav mb-4 flex flex-wrap gap-1 rounded-lg border border-border bg-snf-surface p-1.5 sm:mb-5"
    :aria-label="'SNF Club sections'"
  >
    <a
      v-for="item in nav"
      :key="item.slug"
      :href="item.href"
      :class="
        cn(
          'inline-flex min-h-11 items-center rounded-md px-3.5 py-2 text-[0.8125rem] font-semibold no-underline transition-colors sm:min-h-9',
          active === item.slug
            ? 'bg-white/15 text-white'
            : 'text-white/70 hover:bg-white/10 hover:text-white'
        )
      "
    >
      {{ item.label }}
    </a>
  </nav>
</template>

<style scoped>
.snf-admin-shell__hero {
  border: 0.0625rem solid rgb(255 255 255 / 0.18);
  background:
    radial-gradient(120% 140% at 100% 0%, color-mix(in srgb, var(--snf-admin-cream, #f3ebe8) 14%, transparent), transparent 55%),
    linear-gradient(135deg, #815b5a 0%, #1b1313 78%);
  animation: snf-admin-fade-up 0.45s ease both;
}

.snf-admin-shell__nav {
  margin-block: 0;
  animation: snf-admin-fade-up 0.5s ease 0.05s both;
}

@keyframes snf-admin-fade-up {
  from {
    opacity: 0;
    transform: translateY(0.5rem);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .snf-admin-shell__hero,
  .snf-admin-shell__nav {
    animation: none;
  }
}
</style>
