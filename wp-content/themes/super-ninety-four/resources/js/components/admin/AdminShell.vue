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
    class="snf-admin-shell__hero relative mb-5 overflow-hidden rounded-lg px-6 py-7 text-[var(--snf-admin-cream,#f3ebe8)]"
  >
    <p class="mb-2 text-[11px] font-medium uppercase tracking-[0.14em] opacity-75">
      {{ brand }}
    </p>
    <h1 class="m-0 mb-2 text-[clamp(1.55rem,2.2vw,1.95rem)] font-semibold leading-tight tracking-tight">
      {{ title }}
    </h1>
    <p v-if="lede" class="m-0 max-w-2xl text-[0.98rem] opacity-80">
      {{ lede }}
    </p>
  </header>

  <nav
    v-if="nav.length"
    class="snf-admin-shell__nav mb-5 flex flex-wrap gap-1 rounded-lg border border-border bg-card p-1.5"
    :aria-label="'SNF Club sections'"
  >
    <a
      v-for="item in nav"
      :key="item.slug"
      :href="item.href"
      :class="
        cn(
          'inline-flex items-center rounded-md px-3.5 py-2 text-[13px] font-semibold no-underline transition-colors',
          active === item.slug
            ? 'bg-foreground text-background'
            : 'text-foreground hover:bg-muted'
        )
      "
    >
      {{ item.label }}
    </a>
  </nav>
</template>

<style scoped>
.snf-admin-shell__hero {
  background:
    radial-gradient(120% 140% at 100% 0%, rgba(243, 235, 232, 0.18), transparent 55%),
    linear-gradient(135deg, #815b5a 0%, #1b1313 78%);
  animation: snf-admin-fade-up 0.45s ease both;
}

.snf-admin-shell__nav {
  animation: snf-admin-fade-up 0.5s ease 0.05s both;
}

@keyframes snf-admin-fade-up {
  from {
    opacity: 0;
    transform: translateY(8px);
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
