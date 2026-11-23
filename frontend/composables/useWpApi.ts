export type WpMe = {
  id: number
  name: string
  email: string
  roles: string[]
  membership: string
  isAdmin: boolean
  canManageClub: boolean
  loginUrl: string
  logoutUrl: string
  checkoutUrl: string
  cartUrl: string
  wpAdminUrl: string
}

export function useWpApi() {
  const tokenCookie = useCookie<string | null>('snf_wp_token', {
    sameSite: 'lax',
    maxAge: 60 * 60 * 12,
  })

  async function api<T = unknown>(path: string, options: RequestInit = {}): Promise<T> {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...(options.headers as Record<string, string> | undefined),
    }
    if (tokenCookie.value) {
      headers.Authorization = `Bearer ${tokenCookie.value}`
    }
    if (options.body && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json'
    }

    const res = await $fetch<T>(`/api/wp/${path.replace(/^\//, '')}`, {
      method: (options.method as any) || 'GET',
      headers,
      body: options.body as any,
      credentials: 'include',
    })
    return res
  }

  function setToken(token: string | null) {
    tokenCookie.value = token
  }

  return { api, setToken, tokenCookie }
}
