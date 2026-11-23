<script setup lang="ts">
const { me, fetchMe } = useAuth()
const { api } = useWpApi()
const nav = ref<Array<{ slug: string; label: string; href: string; to?: string }>>([])
const route = useRoute()

const activeSlug = computed(() => {
  if (route.path.startsWith('/club/settings')) return 'snf-club-settings'
  if (route.path.startsWith('/club/events')) return 'snf-club-events'
  if (route.path.startsWith('/club/circles')) return 'snf-club-circles'
  if (route.path.startsWith('/club/media')) return 'snf-club-media'
  if (route.path.startsWith('/club/newsletter')) return 'snf-club-newsletter'
  if (route.path.startsWith('/club/rfq')) return 'snf-club-rfq'
  if (route.path.startsWith('/club/addons')) return 'snf-club-addons'
  return 'snf-club'
})

onMounted(async () => {
  await fetchMe()
  try {
    const data = await api<{ nav: typeof nav.value }>('snf/v1/club/nav')
    nav.value = (data.nav || []).map((item) => ({
      ...item,
      to: mapClubPath(item.slug),
    }))
  } catch {
    nav.value = [
      { slug: 'snf-club', label: 'Overview', href: '/club', to: '/club' },
      { slug: 'snf-club-events', label: 'Events', href: '/club/events', to: '/club/events' },
      { slug: 'snf-club-circles', label: 'Circles', href: '/club/circles', to: '/club/circles' },
      { slug: 'snf-club-media', label: 'Media', href: '/club/media', to: '/club/media' },
      { slug: 'snf-club-newsletter', label: 'Newsletter', href: '/club/newsletter', to: '/club/newsletter' },
      { slug: 'snf-club-rfq', label: 'RFQ', href: '/club/rfq', to: '/club/rfq' },
      { slug: 'snf-club-addons', label: 'Add-ons', href: '/club/addons', to: '/club/addons' },
      { slug: 'snf-club-settings', label: 'Settings', href: '/club/settings', to: '/club/settings' },
    ]
  }
})

function mapClubPath(slug: string) {
  const map: Record<string, string> = {
    'snf-club': '/club',
    'snf-club-events': '/club/events',
    'snf-club-circles': '/club/circles',
    'snf-club-media': '/club/media',
    'snf-club-newsletter': '/club/newsletter',
    'snf-club-rfq': '/club/rfq',
    'snf-club-addons': '/club/addons',
    'snf-club-settings': '/club/settings',
  }
  return map[slug] || ''
}
</script>

<template>
  <div class="snf-club mx-auto w-full max-w-[73.75rem]" style="margin-inline: auto; margin-block: var(--snf-space-y)">
    <div style="margin-inline: var(--snf-space-x)">
      <AdminShell
        brand="Super Ninety Four"
        title="SNF Club"
        lede="Operations dashboard for events, circles, media, and critical settings."
        :nav="nav.filter((n) => n.to).map((n) => ({ slug: n.slug, label: n.label, href: n.to || n.href }))"
        :active="activeSlug"
      />
      <p v-if="me" class="m-0 text-sm text-white/70" style="margin-block: 0 1rem">
        Signed in as {{ me.name }}
      </p>
      <slot />
    </div>
  </div>
</template>
