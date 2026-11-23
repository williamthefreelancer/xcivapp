export default defineNuxtRouteMiddleware(async (to) => {
  // Capture one-time bridge token from WP redirect on any route.
  const bridgeToken = typeof to.query.snf_auth === 'string' ? to.query.snf_auth : ''
  if (bridgeToken) {
    const { setToken } = useAuth()
    setToken(bridgeToken)
    const clean = { ...to.query }
    delete clean.snf_auth
    return navigateTo({ path: to.path, query: clean }, { replace: true })
  }

  const memberSurfaces =
    to.path.startsWith('/app') ||
    to.path.startsWith('/club') ||
    to.path.startsWith('/messages') ||
    to.path.startsWith('/community')

  if (!memberSurfaces) {
    return
  }

  const { fetchMe, loginUrl, me } = useAuth()
  await fetchMe(true)

  if (!me.value) {
    return navigateTo(loginUrl(to.fullPath))
  }

  if (to.path.startsWith('/club') && !me.value.canManageClub) {
    return navigateTo('/app')
  }
})
