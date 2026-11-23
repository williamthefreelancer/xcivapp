<script setup lang="ts">
const { me, fetchMe } = useAuth()
const { api } = useWpApi()
const nav = ref<Array<{ slug: string; label: string; url: string; to?: string; group?: string }>>([])

onMounted(async () => {
  await fetchMe()
  try {
    const data = await api<{ items: typeof nav.value }>('snf/v1/account/nav')
    nav.value = (data.items || []).map((item) => ({
      ...item,
      to: mapAccountPath(item.slug),
    }))
  } catch {
    nav.value = []
  }
})

function mapAccountPath(slug: string) {
  const map: Record<string, string> = {
    dashboard: '/app',
    orders: '/app/orders',
    tickets: '/app/tickets',
    membership: '/app/membership',
    billing: '/app/billing',
    'edit-account': '/app/account',
    'edit-address': '/app/billing',
  }
  return map[slug] || ''
}

const route = useRoute()
</script>

<template>
  <div class="snf-account mx-auto flex min-h-dvh w-full max-w-[73.75rem] flex-col gap-6" style="margin-inline: auto; margin-block: 0; max-width: 73.75rem">
    <header class="snf-account__hero relative overflow-hidden rounded-lg text-snf-admin-on-dark" style="margin: var(--snf-space-y) var(--snf-space-x) 0">
      <div class="snf-account__hero-inner flex flex-col gap-2" style="margin: 1.15rem 1.5rem">
        <p class="m-0 text-[0.6875rem] font-medium uppercase tracking-[0.14em] text-white/75">
          Super Ninety Four
        </p>
        <h1 class="m-0 text-[clamp(1.35rem,2.2vw,1.95rem)] font-semibold tracking-tight">
          Member dashboard
        </h1>
        <p v-if="me" class="m-0 text-sm text-white/80">
          {{ me.name }} · {{ me.membership }}
        </p>
      </div>
    </header>

    <div class="snf-account__body grid gap-6 lg:grid-cols-[14rem_minmax(0,1fr)]" style="margin: 0 var(--snf-space-x) var(--snf-space-y)">
      <aside class="snf-account__nav flex flex-col gap-1 rounded-lg border border-border bg-snf-surface" style="margin: 0">
        <NuxtLink
          v-for="item in nav.filter((n) => n.to)"
          :key="item.slug"
          :to="item.to"
          class="no-underline text-[0.8125rem] font-semibold text-white/70 transition-colors hover:bg-white/10 hover:text-white"
          :class="route.path === item.to ? 'bg-white/15 text-white' : ''"
          style="margin: 0.25rem; min-height: 2.75rem; display: inline-flex; align-items: center"
        >
          <span style="margin-inline: 0.875rem">{{ item.label }}</span>
        </NuxtLink>
        <a
          v-for="item in nav.filter((n) => !n.to && n.url)"
          :key="`ext-${item.slug}`"
          :href="item.url"
          class="no-underline text-[0.8125rem] font-semibold text-white/70 transition-colors hover:bg-white/10 hover:text-white"
          style="margin: 0.25rem; min-height: 2.75rem; display: inline-flex; align-items: center"
        >
          <span style="margin-inline: 0.875rem">{{ item.label }}</span>
        </a>
        <a
          v-if="me?.cartUrl"
          :href="me.cartUrl"
          class="no-underline text-[0.8125rem] font-semibold text-white/70 hover:text-white"
          style="margin: 0.25rem; min-height: 2.75rem; display: inline-flex; align-items: center"
        >
          <span style="margin-inline: 0.875rem">Cart</span>
        </a>
      </aside>
      <main class="snf-account__main min-w-0">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.snf-account__hero {
  border: 0.0625rem solid rgb(255 255 255 / 0.18);
  background:
    radial-gradient(120% 140% at 100% 0%, color-mix(in srgb, var(--snf-cream) 14%, transparent), transparent 55%),
    linear-gradient(135deg, #815b5a 0%, #1b1313 78%);
}
</style>
