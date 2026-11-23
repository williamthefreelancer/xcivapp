<script setup lang="ts">
const route = useRoute()
const router = useRouter()
const { api } = useWpApi()
const { addToCart } = useStoreApi()
const { data, pending, error } = await useAsyncData(`event-${route.params.slug}`, () =>
  api<any>(`snf/v1/events/${route.params.slug}`),
)

const adding = ref(false)

async function buy() {
  if (!data.value?.id) {
    if (data.value?.addToCartUrl) {
      window.location.href = data.value.addToCartUrl
    }
    return
  }
  adding.value = true
  try {
    await addToCart(data.value.id, 1)
    await router.push('/cart')
  } catch {
    if (data.value?.addToCartUrl) {
      window.location.href = data.value.addToCartUrl
    }
  } finally {
    adding.value = false
  }
}

useSeoMeta({ title: () => `${data.value?.title || 'Event'} — SUPER NINETY FOUR` })
</script>

<template>
  <div>
    <SiteHero
      brand="SUPER NINETY FOUR"
      :title="data?.title || 'Event'"
      :lede="[data?.when, data?.location].filter(Boolean).join(' · ')"
      compact
    />
    <section class="snf-section snf-frame" style="max-width: var(--snf-content-w)">
      <UiButton to="/events" variant="ghost" size="sm">← Events</UiButton>
      <p v-if="pending" class="m-0 text-sm text-muted-foreground">Loading…</p>
      <p v-else-if="error" class="m-0 text-sm text-red-300">Event not found.</p>
      <template v-else>
        <img
          v-if="data?.image"
          :src="data.image"
          :alt="data.title"
          class="m-0 w-full object-cover"
          style="margin-block: 1rem; max-height: 28rem"
        >
        <div class="max-w-none text-white/85" v-html="data?.excerpt || data?.content" />
        <div class="flex flex-wrap gap-2" style="margin-block-start: 1.25rem">
          <UiButton :disabled="adding" @click="buy">
            {{ adding ? 'Adding…' : 'Add to cart' }}
          </UiButton>
          <UiButton to="/checkout" variant="secondary">Checkout</UiButton>
        </div>
      </template>
    </section>
  </div>
</template>
