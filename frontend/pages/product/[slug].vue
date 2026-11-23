<script setup lang="ts">
const route = useRoute()
const slug = computed(() => String(route.params.slug || ''))
const { getProducts, getProduct, addToCart, money } = useStoreApi()
const router = useRouter()

const eventLandingSlugs = new Set([
  'the-eight-hundred-plus-journeys-event',
  'the-township-adventures-event',
  'the-city-club-private-party',
  'the-badlands-bra-private-gathering',
  'harris-houston-to-wyoma-square',
  'harrisburg-to-lynn',
  'lynnfield-to-lynn',
  'lynnfield-to-salem',
  'the-northshore-experience',
  'the-dirty-pub-hour',
])

if (eventLandingSlugs.has(slug.value)) {
  await navigateTo(`/events/${slug.value}`, { redirectCode: 301 })
}

const { data, error } = await useAsyncData(
  () => `product-${slug.value}`,
  async () => {
    try {
      return await getProduct(slug.value)
    } catch {
      const list = await getProducts({ slug: slug.value, per_page: 1 })
      return Array.isArray(list) ? list[0] : null
    }
  },
  { watch: [slug] },
)

const status = ref<'idle' | 'adding' | 'error'>('idle')

async function buy() {
  if (!data.value?.id) return
  status.value = 'adding'
  try {
    await addToCart(data.value.id, 1)
    await router.push('/cart')
  } catch {
    status.value = 'error'
  }
}

useSeoMeta({ title: () => `${data.value?.name || 'Product'} — SUPER NINETY FOUR` })
</script>

<template>
  <div>
    <SiteHero brand="SUPER NINETY FOUR" :title="data?.name || 'Product'" compact />
    <section class="snf-section snf-frame grid gap-8 lg:grid-cols-2">
      <p v-if="error || !data" class="m-0 text-white/70">Product not found.</p>
      <template v-else>
        <img
          v-if="data.images?.[0]?.src"
          :src="data.images[0].src"
          :alt="data.images[0].alt || data.name"
          class="m-0 w-full object-cover"
        >
        <div>
          <p class="m-0 text-[1.25rem] text-snf-cream">{{ money(data.prices) }}</p>
          <div
            v-if="data.short_description || data.description"
            class="text-white/80"
            style="margin-block-start: 1rem"
            v-html="data.short_description || data.description"
          />
          <div class="flex flex-wrap gap-2" style="margin-block-start: 1.25rem">
            <UiButton
              :disabled="!data.is_purchasable || status === 'adding'"
              @click="buy"
            >
              {{ status === 'adding' ? 'Adding…' : (data.add_to_cart?.text || 'Add to cart') }}
            </UiButton>
            <UiButton to="/shop" variant="outline">Back to shop</UiButton>
          </div>
          <p v-if="status === 'error'" class="m-0 text-red-300" style="margin-block-start: 0.75rem">
            Could not add to cart.
          </p>
        </div>
      </template>
    </section>
  </div>
</template>
