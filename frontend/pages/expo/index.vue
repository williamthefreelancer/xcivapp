<script setup lang="ts">
const { api } = useWpApi()
const q = ref('')

const { data, refresh } = await useAsyncData('marketplace', () =>
  api<any>(`snf/v1/marketplace${q.value ? `?q=${encodeURIComponent(q.value)}` : ''}`),
)

const items = computed(() => {
  const d = data.value
  if (!d) return []
  return d.items || d.products || d.listings || d.results || []
})

async function search() {
  await refresh()
}

const rfq = reactive({ name: '', email: '', org: '', message: '' })
const rfqStatus = ref<'idle' | 'sending' | 'sent' | 'error'>('idle')

async function submitRfq() {
  rfqStatus.value = 'sending'
  try {
    await api('snf/v1/marketplace/rfq', { method: 'POST', body: JSON.stringify(rfq) })
    rfqStatus.value = 'sent'
  } catch {
    rfqStatus.value = 'error'
  }
}

useSeoMeta({ title: 'Expo — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero brand="SUPER NINETY FOUR" title="Expo marketplace" lede="Private vendor and B2B circuit." compact />
    <section class="snf-section snf-frame">
      <form class="flex flex-wrap gap-2" @submit.prevent="search">
        <input
          v-model="q"
          placeholder="Search expo"
          class="border border-white/25 bg-transparent text-white"
          style="min-height: 2.75rem; min-width: 16rem; margin: 0"
        >
        <UiButton type="submit" size="sm">Search</UiButton>
      </form>

      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" style="margin-block-start: 1.5rem">
        <article v-for="(item, i) in items" :key="item.id || item.slug || i" class="m-0">
          <img
            v-if="item.image || item.thumbnail"
            :src="item.image || item.thumbnail"
            :alt="item.title || ''"
            class="m-0 w-full object-cover"
            style="aspect-ratio: 4 / 3"
          >
          <h2 class="m-0 text-[1.0625rem] font-semibold" style="margin-block-start: 0.65rem">
            {{ item.title || item.name }}
          </h2>
          <p v-if="item.price_html || item.price" class="m-0 text-snf-cream" style="margin-block-start: 0.35rem" v-html="item.price_html || item.price" />
          <UiButton
            v-if="item.slug"
            :to="`/product/${item.slug}`"
            size="sm"
            variant="outline"
            style="margin-block-start: 0.75rem"
          >
            View
          </UiButton>
        </article>
      </div>
      <p v-if="!items.length" class="m-0 text-white/65" style="margin-block-start: 1rem">No listings matched.</p>
    </section>

    <section class="snf-section snf-frame" style="max-width: var(--snf-content-narrow-w)">
      <h2 class="snf-display m-0">Request a quote</h2>
      <p v-if="rfqStatus === 'sent'" class="m-0 text-snf-cream" style="margin-block-start: 1rem">RFQ submitted.</p>
      <form v-else class="flex flex-col gap-3" style="margin-block-start: 1rem" @submit.prevent="submitRfq">
        <input v-model="rfq.name" required placeholder="Name" class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        <input v-model="rfq.email" type="email" required placeholder="Email" class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        <input v-model="rfq.org" placeholder="Organization" class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        <textarea v-model="rfq.message" required rows="4" placeholder="What do you need?" class="border border-white/25 bg-transparent text-white" style="margin: 0" />
        <p v-if="rfqStatus === 'error'" class="m-0 text-red-300">Could not submit RFQ.</p>
        <UiButton type="submit" :disabled="rfqStatus === 'sending'">Submit RFQ</UiButton>
      </form>
    </section>
  </div>
</template>
