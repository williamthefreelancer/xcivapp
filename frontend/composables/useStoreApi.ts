/**
 * WooCommerce Store API helpers (cart / products / checkout).
 * Proxied through Nitro `/api/wp/wc/store/v1/...`.
 */

export type StoreCartItem = {
  key: string
  id: number
  name: string
  quantity: number
  prices?: { price?: string; currency_symbol?: string; currency_minor_unit?: number }
  images?: Array<{ src?: string; thumbnail?: string }>
  permalink?: string
}

export type StoreCart = {
  items: StoreCartItem[]
  items_count: number
  totals?: {
    total_items?: string
    total_price?: string
    currency_symbol?: string
    currency_minor_unit?: number
  }
  needs_payment?: boolean
  payment_methods?: string[]
}

export type StoreProduct = {
  id: number
  name: string
  slug: string
  description?: string
  short_description?: string
  prices?: { price?: string; currency_symbol?: string; currency_minor_unit?: number }
  images?: Array<{ src?: string; thumbnail?: string; alt?: string }>
  categories?: Array<{ id?: number; name?: string; slug?: string }>
  permalink?: string
  is_purchasable?: boolean
  is_in_stock?: boolean
  add_to_cart?: { text?: string }
}

function formatMinor(amount?: string, minor = 2, symbol = '$') {
  if (amount === undefined || amount === null || amount === '') return ''
  const n = Number(amount) / Math.pow(10, minor)
  if (Number.isNaN(n)) return String(amount)
  return `${symbol}${n.toFixed(minor)}`
}

export function useStoreApi() {
  const { api, tokenCookie } = useWpApi()
  const cartNonce = useCookie<string | null>('snf_store_nonce', {
    sameSite: 'lax',
    maxAge: 60 * 60 * 24,
  })
  const cartToken = useCookie<string | null>('snf_store_cart', {
    sameSite: 'lax',
    maxAge: 60 * 60 * 24 * 7,
  })

  async function storeFetch<T = unknown>(path: string, options: RequestInit = {}): Promise<T> {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...(options.headers as Record<string, string> | undefined),
    }
    if (tokenCookie.value) {
      headers.Authorization = `Bearer ${tokenCookie.value}`
    }
    if (cartNonce.value) {
      headers.Nonce = cartNonce.value
    }
    if (cartToken.value) {
      headers['Cart-Token'] = cartToken.value
    }
    if (options.body && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json'
    }

    // Use raw $fetch to capture Store API nonce / cart token headers when available.
    const res = await $fetch.raw<T>(`/api/wp/wc/store/v1/${path.replace(/^\//, '')}`, {
      method: (options.method as any) || 'GET',
      headers,
      body: options.body as any,
      credentials: 'include',
    })

    const nonce = res.headers.get('nonce') || res.headers.get('Nonce')
    if (nonce) {
      cartNonce.value = nonce
    }
    const token = res.headers.get('cart-token') || res.headers.get('Cart-Token')
    if (token) {
      cartToken.value = token
    }

    return res._data as T
  }

  function money(prices?: { price?: string; currency_symbol?: string; currency_minor_unit?: number }) {
    return formatMinor(prices?.price, prices?.currency_minor_unit ?? 2, prices?.currency_symbol || '$')
  }

  function cartTotal(cart?: StoreCart | null) {
    const t = cart?.totals
    return formatMinor(t?.total_price, t?.currency_minor_unit ?? 2, t?.currency_symbol || '$')
  }

  async function getProducts(params: Record<string, string | number> = {}) {
    const qs = new URLSearchParams()
    Object.entries(params).forEach(([k, v]) => qs.set(k, String(v)))
    const suffix = qs.toString() ? `?${qs}` : ''
    return storeFetch<StoreProduct[]>(`products${suffix}`)
  }

  async function getProduct(idOrSlug: string | number) {
    return storeFetch<StoreProduct>(`products/${idOrSlug}`)
  }

  async function getCart() {
    return storeFetch<StoreCart>('cart')
  }

  async function addToCart(productId: number, quantity = 1) {
    return storeFetch<StoreCart>('cart/add-item', {
      method: 'POST',
      body: JSON.stringify({ id: productId, quantity }),
    })
  }

  async function updateItem(key: string, quantity: number) {
    return storeFetch<StoreCart>('cart/update-item', {
      method: 'POST',
      body: JSON.stringify({ key, quantity }),
    })
  }

  async function removeItem(key: string) {
    return storeFetch<StoreCart>('cart/remove-item', {
      method: 'POST',
      body: JSON.stringify({ key }),
    })
  }

  async function checkout(payload: Record<string, unknown>) {
    return storeFetch<any>('checkout', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
  }

  /** Fallback: Stripe checkout session via theme endpoint when Store checkout needs gateway handoff. */
  async function stripeCheckout(body: Record<string, unknown> = {}) {
    return api<{ url?: string; id?: string }>('snf/v1/stripe/checkout', {
      method: 'POST',
      body: JSON.stringify(body),
    })
  }

  return {
    storeFetch,
    getProducts,
    getProduct,
    getCart,
    addToCart,
    updateItem,
    removeItem,
    checkout,
    stripeCheckout,
    money,
    cartTotal,
    api,
  }
}
