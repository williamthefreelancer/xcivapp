<script setup lang="ts">
import { cn } from '~/utils/cn'

defineProps<{
  brand?: string
  title: string
  lede?: string
  nav?: Array<{ slug: string; label: string; href: string }>
  active?: string
}>()
</script>

<template>
  <header
    class="snf-admin-shell__hero relative overflow-hidden rounded-lg text-snf-admin-on-dark"
    style="margin-block: 0 1rem"
  >
    <div style="margin: 1.15rem 1.5rem">
      <p class="mb-2 text-[0.6875rem] font-medium uppercase tracking-[0.14em] text-white/75">
        {{ brand || 'Super Ninety Four' }}
      </p>
      <h1 class="m-0 mb-2 text-[clamp(1.35rem,2.2vw,1.95rem)] font-semibold leading-tight tracking-tight text-snf-admin-on-dark">
        {{ title }}
      </h1>
      <p v-if="lede" class="m-0 max-w-2xl text-[0.95rem] text-white/80">
        {{ lede }}
      </p>
    </div>
  </header>

  <nav
    v-if="nav?.length"
    class="snf-admin-shell__nav flex flex-wrap gap-1 rounded-lg border border-border bg-snf-surface"
    style="margin-block: 0 1.25rem"
    aria-label="SNF Club sections"
  >
    <NuxtLink
      v-for="item in nav"
      :key="item.slug"
      :to="item.href"
      :class="
        cn(
          'inline-flex min-h-11 items-center rounded-md text-[0.8125rem] font-semibold no-underline transition-colors sm:min-h-9',
          active === item.slug
            ? 'bg-white/15 text-white'
            : 'text-white/70 hover:bg-white/10 hover:text-white',
        )
      "
      style="margin: 0.375rem; padding-inline: 0.875rem"
    >
      {{ item.label }}
    </NuxtLink>
  </nav>
</template>

<style scoped>
.snf-admin-shell__hero {
  border: 0.0625rem solid rgb(255 255 255 / 0.18);
  background:
    radial-gradient(120% 140% at 100% 0%, color-mix(in srgb, var(--snf-admin-cream, #f3ebe8) 14%, transparent), transparent 55%),
    linear-gradient(135deg, #815b5a 0%, #1b1313 78%);
}
</style>
