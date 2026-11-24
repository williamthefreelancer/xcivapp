export type LeadRiderRoute = {
  id: string
  title: string
  center: [number, number]
  zoom: number
  coordinates: [number, number][]
  geometrySource?: string
}

export type ExpoBootstrap = {
  audience: string
  mapboxToken: string
  leadRiderName: string
  canBroadcast: boolean
  canLive: boolean
  applyUrl: string
  routes: LeadRiderRoute[]
  defaultRouteId: string
  ice: Array<{ urls: string | string[]; username?: string; credential?: string }>
  rest: {
    bootstrap?: string
    position: string
    signal: string
    peers: string
    nonce: string
  }
  pollMs: number
  i18n: Record<string, string>
}

export function useLeadRider() {
  const { api } = useWpApi()
  const runtimeConfig = useRuntimeConfig()

  async function fetchBootstrap(audience?: string) {
    const q = audience ? `?audience=${encodeURIComponent(audience)}` : ''
    const boot = await api<ExpoBootstrap>(`snf/v1/expo/bootstrap${q}`)
    const envToken = String(runtimeConfig.public.mapboxToken || '').trim()
    if (!boot.mapboxToken && envToken) {
      boot.mapboxToken = envToken
    }
    return boot
  }

  function restPath(absoluteUrl: string) {
    try {
      const u = new URL(absoluteUrl)
      const marker = '/wp-json/'
      const idx = u.pathname.indexOf(marker)
      if (idx >= 0) {
        return u.pathname.slice(idx + marker.length) + u.search
      }
    } catch {
      /* fall through */
    }
    return absoluteUrl.replace(/^https?:\/\/[^/]+\/wp-json\//, '')
  }

  return { fetchBootstrap, restPath }
}
