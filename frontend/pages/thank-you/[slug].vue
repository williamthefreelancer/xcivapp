<script setup lang="ts">
const route = useRoute()
const slug = computed(() => String(route.params.slug || ''))
const { api } = useWpApi()
const config = useRuntimeConfig()

const openAdmission = new Set([
  'the-city-club-private-party',
  'the-lifestyle-concierge-club-workshop',
])

if (!openAdmission.has(slug.value)) {
  await navigateTo('/order-received/' + (route.query.order || ''), { redirectCode: 302 })
}

const orderId = computed(() => String(route.query.order || ''))
const orderKey = computed(() => String(route.query.key || ''))

const { data } = await useAsyncData(
  () => `gathering-thanks-${slug.value}-${orderId.value}`,
  () =>
    api<any>('snf/v1/gathering-thankyou', {
      query: {
        slug: slug.value,
        order: orderId.value,
        key: orderKey.value,
      },
    }).catch(() => null),
  { watch: [slug, orderId, orderKey] },
)

const form = reactive({
  name: '',
  email: '',
  phone: '',
  window: '',
  q1_problem: '',
  q2_fear: '',
  q3_tried: '',
})

watch(
  () => data.value?.order,
  (order) => {
    if (!order) return
    form.name = order.name || form.name
    form.email = order.email || form.email
    form.phone = order.phone || form.phone
  },
  { immediate: true },
)

const status = ref<'idle' | 'sending' | 'sent' | 'error'>('idle')
const wpOrigin = computed(() => {
  const apiBase = String(config.public.wpApiBase || config.public.apiBase || '')
  try {
    return new URL(apiBase).origin
  } catch {
    return ''
  }
})

async function submit() {
  status.value = 'sending'
  try {
    const body = new URLSearchParams()
    body.set('action', 'snf_gathering_book')
    body.set('snf_gathering_slug', slug.value)
    body.set('snf_book_name', form.name)
    body.set('snf_book_email', form.email)
    body.set('snf_book_phone', form.phone)
    body.set('snf_book_window', form.window)
    body.set('q1_problem', form.q1_problem)
    body.set('q2_fear', form.q2_fear)
    body.set('q3_tried', form.q3_tried)
    if (orderId.value) body.set('snf_order_id', orderId.value)
    if (orderKey.value) body.set('snf_order_key', orderKey.value)
    body.set('snf_redirect', window.location.href.split('?')[0] + window.location.search)

    const res = await fetch(`${wpOrigin.value}/wp-admin/admin-post.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
      credentials: 'include',
      redirect: 'follow',
    })
    if (!res.ok && res.status >= 400) throw new Error('submit failed')
    status.value = 'sent'
  } catch {
    status.value = 'error'
  }
}

useSeoMeta({
  title: () => `${data.value?.title || 'Thank you'} — SUPER NINETY FOUR`,
})
</script>

<template>
  <div>
    <SiteHero
      brand="SUPER NINETY FOUR"
      :title="data?.title || 'You’re confirmed'"
      :lede="data?.lede || 'Your e-ticket is secured.'"
      compact
    />
    <section class="snf-section snf-frame" style="max-width: var(--snf-content-narrow-w)">
      <p v-if="data?.order" class="m-0 text-white/70">
        Order #{{ data.order.number }} · <span v-html="data.order.total" />
      </p>
      <p class="m-0 text-white/70" style="margin-block-start: 0.75rem">
        {{ data?.location || 'Harris Houston · Charlotte / Harrisburg area · undisclosed location' }}
      </p>

      <p v-if="status === 'sent' || data?.submitted" class="m-0 text-snf-cream" style="margin-block-start: 1rem" role="status">
        Received. I’ll review your answers and confirm the in-person window.
      </p>
      <p v-else-if="status === 'error' || data?.error" class="m-0 text-red-300" style="margin-block-start: 1rem" role="alert">
        Something was missing. Answer all three questions bluntly and pick a window.
      </p>

      <form
        v-if="status !== 'sent' && !data?.submitted"
        class="grid gap-3"
        style="margin-block-start: 1.25rem"
        @submit.prevent="submit"
      >
        <p class="m-0 text-white/75">
          Book the in-person conversation. Answer these three questions bluntly so I know the real problem — based on what you’ve lived, I’ll come with solutions from what I know.
        </p>
        <label class="grid gap-1">
          <span class="text-sm text-snf-cream">Full name</span>
          <input v-model="form.name" required autocomplete="name" class="snf-input">
        </label>
        <label class="grid gap-1">
          <span class="text-sm text-snf-cream">Email</span>
          <input v-model="form.email" type="email" required autocomplete="email" class="snf-input">
        </label>
        <label class="grid gap-1">
          <span class="text-sm text-snf-cream">Phone</span>
          <input v-model="form.phone" type="tel" required autocomplete="tel" class="snf-input">
        </label>
        <label class="grid gap-1">
          <span class="text-sm text-snf-cream">Preferred in-person window</span>
          <select v-model="form.window" required class="snf-input">
            <option value="">Select a window</option>
            <option v-for="w in data?.windows || []" :key="w.value" :value="w.value">{{ w.label }}</option>
          </select>
        </label>
        <label v-for="q in data?.questions || []" :key="q.key" class="grid gap-1">
          <span class="text-sm text-snf-cream">{{ q.label }}</span>
          <textarea
            v-model="(form as any)[q.key]"
            required
            rows="4"
            class="snf-input"
            :placeholder="q.hint"
          />
        </label>
        <UiButton type="submit" :disabled="status === 'sending'">
          {{ status === 'sending' ? 'Submitting…' : 'Submit booking request' }}
        </UiButton>
      </form>
    </section>
  </div>
</template>
