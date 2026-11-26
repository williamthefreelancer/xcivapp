<script setup lang="ts">
const { menus, fetchMenus } = useSiteMenus()
const { me, fetchMe } = useAuth()
const open = ref(false)
const route = useRoute()

await Promise.all([fetchMenus(), fetchMe()])

watch(
  () => route.path,
  () => {
    open.value = false
  },
)

function isCurrent(path: string) {
  if (path.includes('#')) {
    const [base, hash] = path.split('#')
    const basePath = base || '/'
    if (route.path !== basePath) return false
    return route.hash === `#${hash}`
  }
  if (path === '/') return route.path === '/'
  return route.path === path || route.path.startsWith(`${path}/`)
}
</script>

<template>
  <header class="snf-site-header" :class="{ 'is-open': open }">
    <div class="snf-site-header__bar snf-frame">
      <div class="snf-site-header__brand">
        <NuxtLink
          :to="menus.brand.path"
          class="snf-site-header__logo"
          :aria-label="menus.brand.name"
        >
          <img
            src="/icons/the-super-ninety-four-app-white.svg"
            alt=""
            width="209"
            height="20"
            decoding="async"
            fetchpriority="high"
          >
        </NuxtLink>
        <SiteProductSwitcher />
      </div>

      <nav class="snf-site-header__nav" aria-label="Primary">
        <NuxtLink
          v-for="item in menus.primary"
          :key="item.path"
          :to="item.path"
          class="snf-site-header__link"
          :class="{ 'is-current': isCurrent(item.path) }"
        >
          {{ item.title }}
        </NuxtLink>
      </nav>

      <div class="snf-site-header__actions">
        <UiButton to="/cart" variant="ghost" size="sm" class="snf-site-header__util">Cart</UiButton>
        <UiButton v-if="me" to="/app" size="sm" class="snf-site-header__util">Account</UiButton>
        <UiButton v-else to="/login" size="sm" class="snf-site-header__util">Login</UiButton>
        <UiButton to="/apply" variant="outline" size="sm" class="snf-site-header__apply">Apply</UiButton>
        <button
          type="button"
          class="snf-site-header__menu"
          :aria-expanded="open"
          aria-controls="snf-mobile-nav"
          @click="open = !open"
        >
          <span class="sr-only">Menu</span>
          <span class="snf-site-header__menu-bars" aria-hidden="true" />
        </button>
      </div>
    </div>

    <nav
      v-show="open"
      id="snf-mobile-nav"
      class="snf-site-header__drawer snf-frame"
      aria-label="Mobile"
    >
      <NuxtLink
        v-for="item in menus.primary"
        :key="item.path"
        :to="item.path"
        class="snf-site-header__drawer-link"
        :class="{ 'is-current': isCurrent(item.path) }"
      >
        {{ item.title }}
      </NuxtLink>
    </nav>
  </header>
</template>
