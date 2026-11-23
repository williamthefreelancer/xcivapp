<script setup lang="ts">
const { getCart, checkout, stripeCheckout, cartTotal } = useStoreApi()
const { me, fetchMe } = useAuth()
await fetchMe()

const { data: cart, refresh } = await useAsyncData('checkout-cart', () => getCart())

const form = reactive({
  billing_address: {
    first_name: '',
    last_name: '',
    company: '',
    address_1: '',
    address_2: '',
    city: '',
    state: '',
    postcode: '',
    country: 'US',
    email: me.value?.email || '',
    phone: '',
  },
  payment_method: 'stripe',
  customer_note: '',
})

const status = ref<'idle' | 'paying' | 'error'>('idle')
const errorMsg = ref('')

async function placeOrder() {
  status.value = 'paying'
  errorMsg.value = ''
  try {
    await refresh()
    if (!(cart.value?.items || []).length) {
      throw new Error('Cart is empty')
    }

    // Prefer Store API checkout; fall back to theme Stripe session when gateway needs it.
    try {
      const result = await checkout({
        billing_address: form.billing_address,
        shipping_address: form.billing_address,
        payment_method: form.payment_method,
        customer_note: form.customer_note,
      })
      const orderId = result?.order_id || result?.orderId
      if (result?.payment_result?.redirect_url) {
        window.location.href = result.payment_result.redirect_url
        return
      }
      if (orderId) {
        await navigateTo(`/order-received/${orderId}`)
        return
      }
    } catch {
      /* try stripe handoff */
    }

    const stripe = await stripeCheckout({
      billing: form.billing_address,
      note: form.customer_note,
    })
    if (stripe?.url) {
      window.location.href = stripe.url
      return
    }
    throw new Error('Checkout could not start')
  } catch (e: any) {
    status.value = 'error'
    errorMsg.value = e?.message || e?.data?.message || 'Checkout failed.'
  }
}

useSeoMeta({ title: 'Checkout — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero brand="SUPER NINETY FOUR" title="Checkout" compact />
    <section class="snf-section snf-frame grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
      <form class="flex flex-col gap-3" @submit.prevent="placeOrder">
        <div class="grid gap-3 md:grid-cols-2">
          <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
            First name
            <input v-model="form.billing_address.first_name" required class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
          </label>
          <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
            Last name
            <input v-model="form.billing_address.last_name" required class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
          </label>
        </div>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Email
          <input v-model="form.billing_address.email" type="email" required class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Address
          <input v-model="form.billing_address.address_1" required class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
        </label>
        <div class="grid gap-3 md:grid-cols-3">
          <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
            City
            <input v-model="form.billing_address.city" required class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
          </label>
          <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
            State
            <input v-model="form.billing_address.state" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
          </label>
          <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
            Postcode
            <input v-model="form.billing_address.postcode" required class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
          </label>
        </div>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Country
          <input v-model="form.billing_address.country" required class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Order note
          <textarea v-model="form.customer_note" rows="3" class="border border-white/25 bg-transparent" style="margin: 0" />
        </label>
        <p v-if="errorMsg" class="m-0 text-red-300">{{ errorMsg }}</p>
        <UiButton type="submit" :disabled="status === 'paying'">
          {{ status === 'paying' ? 'Processing…' : 'Place order' }}
        </UiButton>
      </form>

      <aside class="border border-white/15" style="margin: 0">
        <div style="margin: 1rem">
          <h2 class="m-0 font-display text-[1.5rem] uppercase">Order summary</h2>
          <ul class="m-0 list-none p-0" style="margin-block-start: 1rem">
            <li
              v-for="item in cart?.items || []"
              :key="item.key"
              class="flex justify-between gap-3 border-t border-white/10"
              style="padding-block: 0.65rem"
            >
              <span>{{ item.name }} × {{ item.quantity }}</span>
            </li>
          </ul>
          <p class="m-0 text-[1.125rem]" style="margin-block-start: 1rem">
            Total: <strong>{{ cartTotal(cart) }}</strong>
          </p>
          <p class="m-0 text-sm text-white/60" style="margin-block-start: 0.75rem">
            Payment via Stripe. Webhooks remain on WordPress.
          </p>
        </div>
      </aside>
    </section>
  </div>
</template>
