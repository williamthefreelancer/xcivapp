import { Agent } from 'undici'

const insecureAgent = new Agent({
  connect: {
    rejectUnauthorized: false,
  },
})

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const wpBase = String(config.wpUrl || config.public.wpUrl || '').replace(/\/$/, '')
  const path = (event.context.params?.path as string) || ''
  const query = getQuery(event)
  const qs = new URLSearchParams()
  for (const [key, value] of Object.entries(query)) {
    if (Array.isArray(value)) {
      value.forEach((v) => qs.append(key, String(v)))
    } else if (value !== undefined && value !== null) {
      qs.set(key, String(value))
    }
  }
  const url = `${wpBase}/wp-json/${path}${qs.toString() ? `?${qs}` : ''}`

  const method = event.method || 'GET'
  const headers: Record<string, string> = {
    Accept: 'application/json',
  }

  const auth = getHeader(event, 'authorization')
  if (auth) {
    headers.Authorization = auth
  }

  const cookie = getHeader(event, 'cookie')
  if (cookie) {
    headers.Cookie = cookie
  }

  const nonce = getHeader(event, 'x-wp-nonce') || getHeader(event, 'nonce')
  if (nonce) {
    headers['X-WP-Nonce'] = nonce
    headers.Nonce = nonce
  }

  const cartToken = getHeader(event, 'cart-token')
  if (cartToken) {
    headers['Cart-Token'] = cartToken
  }

  let body: string | undefined
  if (method !== 'GET' && method !== 'HEAD') {
    body = await readRawBody(event)
    const contentType = getHeader(event, 'content-type')
    if (contentType) {
      headers['Content-Type'] = contentType
    }
  }

  const upstream = await fetch(url, {
    method,
    headers,
    body,
    // Local XAMPP often uses a self-signed cert.
    dispatcher: insecureAgent,
  } as RequestInit).catch(async (err) => {
    throw createError({
      statusCode: 502,
      statusMessage: `WordPress proxy failed: ${err?.message || err}`,
    })
  })

  const text = await upstream.text()
  setResponseStatus(event, upstream.status)

  const contentType = upstream.headers.get('content-type') || 'application/json'
  setHeader(event, 'content-type', contentType)

  // Expose Store API session headers to the Nuxt client.
  const outNonce = upstream.headers.get('nonce') || upstream.headers.get('Nonce')
  if (outNonce) {
    setHeader(event, 'nonce', outNonce)
  }
  const outCart = upstream.headers.get('cart-token') || upstream.headers.get('Cart-Token')
  if (outCart) {
    setHeader(event, 'cart-token', outCart)
  }
  setHeader(event, 'access-control-expose-headers', 'nonce, cart-token, Nonce, Cart-Token')

  if (!text) {
    return null
  }

  try {
    return JSON.parse(text)
  } catch {
    return text
  }
})
