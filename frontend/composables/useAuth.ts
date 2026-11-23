import type { WpMe } from './useWpApi'

export function useAuth() {
  const me = useState<WpMe | null>('snf-me', () => null)
  const loaded = useState<boolean>('snf-me-loaded', () => false)
  const { api, setToken } = useWpApi()

  async function fetchMe(force = false) {
    if (loaded.value && !force) {
      return me.value
    }
    try {
      const payload = await api<WpMe & { token?: string }>('snf/v1/me')
      me.value = payload && payload.id ? payload : null
      if (payload?.token) {
        setToken(payload.token)
      }
    } catch {
      me.value = null
    } finally {
      loaded.value = true
    }
    return me.value
  }

  function loginUrl(redirectPath?: string) {
    const redirect = redirectPath || '/app'
    return `/login?redirect=${encodeURIComponent(redirect)}`
  }

  async function login(username: string, password: string, remember = true) {
    const res = await api<{ ok?: boolean; token?: string; user?: Partial<WpMe> }>('snf/v1/auth/login', {
      method: 'POST',
      body: JSON.stringify({ username, password, remember }),
    })
    if (res?.token) {
      setToken(res.token)
    }
    loaded.value = false
    await fetchMe(true)
    return res
  }

  async function logout() {
    try {
      await api('snf/v1/auth/logout', { method: 'POST', body: '{}' })
    } catch {
      /* ignore */
    }
    clearSession()
  }

  function clearSession() {
    me.value = null
    loaded.value = false
    setToken(null)
  }

  return { me, loaded, fetchMe, loginUrl, login, logout, clearSession, setToken }
}
