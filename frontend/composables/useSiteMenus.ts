export type SiteMenuItem = {
  title: string
  path: string
}

export type SiteMenus = {
  primary: SiteMenuItem[]
  footer: SiteMenuItem[]
  account: SiteMenuItem[]
  brand: { name: string; path: string }
}

const fallbackMenus: SiteMenus = {
  brand: { name: 'SUPER NINETY FOUR', path: '/' },
  primary: [
    { title: 'Help', path: '/#help' },
    { title: 'Gatherings', path: '/#gatherings' },
    { title: 'Fit', path: '/#fit' },
    { title: 'Pricing', path: '/#pricing' },
    { title: 'Shop', path: '/shop' },
  ],
  footer: [
    { title: 'Privacy Policy', path: '/legal/privacy-policy' },
    { title: 'Terms of Service', path: '/legal/terms-of-service' },
    { title: 'Cookie Policy', path: '/legal/cookie-policy' },
    { title: 'Shop', path: '/shop' },
    { title: 'Apply', path: '/apply' },
  ],
  account: [
    { title: 'Dashboard', path: '/app' },
    { title: 'Community', path: '/community' },
    { title: 'Messages', path: '/messages' },
  ],
}

export function useSiteMenus() {
  const menus = useState<SiteMenus>('snf-menus', () => fallbackMenus)
  const loaded = useState<boolean>('snf-menus-loaded', () => false)
  const { api } = useWpApi()

  async function fetchMenus(force = false) {
    if (loaded.value && !force) {
      return menus.value
    }
    try {
      const payload = await api<SiteMenus>('snf/v1/content/menus')
      if (payload?.primary?.length) {
        menus.value = {
          ...fallbackMenus,
          ...payload,
          brand: payload.brand || fallbackMenus.brand,
        }
      }
    } catch {
      menus.value = fallbackMenus
    } finally {
      loaded.value = true
    }
    return menus.value
  }

  return { menus, loaded, fetchMenus }
}
