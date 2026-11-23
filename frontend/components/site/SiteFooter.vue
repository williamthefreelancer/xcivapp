<script setup lang="ts">
const { menus, fetchMenus } = useSiteMenus()
await fetchMenus()
</script>

<template>
  <footer
    class="snf-site-footer"
    style="margin-block-start: 0; border-top: 0.0625rem solid rgba(255,255,255,0.12)"
  >
    <div class="snf-frame" style="margin-block: clamp(1.35rem, 3vw, 2rem)">
      <NuxtLink
        :to="menus.brand.path"
        class="inline-flex items-center no-underline"
        :aria-label="menus.brand.name"
      >
        <img
          src="/icons/the-super-ninety-four-app-white.svg"
          alt=""
          width="209"
          height="20"
          class="block h-auto w-[min(13.0625rem,48vw)]"
          decoding="async"
        >
      </NuxtLink>
      <p class="snf-lede m-0" style="margin-block-start: 0.75rem">
        Not a marketplace. A private hospitality circuit — if you already clear the bar.
      </p>
      <nav
        class="flex flex-wrap gap-x-4 gap-y-2"
        style="margin-block-start: 1.25rem"
        aria-label="Footer"
      >
        <NuxtLink
          v-for="item in menus.footer"
          :key="item.path"
          :to="item.path"
          class="text-[0.75rem] uppercase tracking-[0.12em] text-white/70 no-underline hover:text-white"
        >
          {{ item.title }}
        </NuxtLink>
      </nav>
      <p class="m-0 text-[0.75rem] text-white/45" style="margin-block-start: 1.5rem">
        © {{ new Date().getFullYear() }} SUPER NINETY FOUR
      </p>
    </div>
  </footer>
</template>
