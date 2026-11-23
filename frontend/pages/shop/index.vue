<script setup lang="ts">
const { getProducts, money } = useStoreApi()
const { data, error, pending } = await useAsyncData('shop-products', () =>
  getProducts({ per_page: 48 }).catch(() => [] as any[]),
)

const reciprocalProducts = [
  {
    slug: 'the-clubs-at-back-creek',
    name: 'The Clubs at Back Creek',
    scope: 'North Carolina only',
    body: 'Charlotte ↔ outside Boston. Credentials that travel the backway.',
    price: '$1,000.00',
    image: '/images/reciprocal/the-clubs-at-back-creek.webp',
  },
  {
    slug: 'the-clubs-at-wyoma-square',
    name: 'The Clubs at Wyoma Square',
    scope: 'Massachusetts North Shore and New Hampshire only',
    body: 'Plaza hospitality without the plaza crowd.',
    price: '$2,899.00',
    image: '/images/reciprocal/the-clubs-at-wyoma-square.webp',
  },
  {
    slug: 'the-city-clubs-at-super-ninety-four',
    name: 'The City Clubs of SUPER NINETY FOUR',
    scope: 'City-to-city interstate: Harrisburg / Harris Houston / Back Creek → North Shore',
    body: 'City to city. Coach, not chaos.',
    price: '$3,649.00',
    image: '/images/reciprocal/the-city-clubs-at-super-ninety-four.webp',
  },
] as const

const reciprocalSlugs = new Set(reciprocalProducts.map((p) => p.slug))

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

const catalogProducts = computed(() =>
  (data.value || []).filter((p) => p?.slug && !reciprocalSlugs.has(p.slug)),
)

function productPath(p: { slug?: string; categories?: Array<{ slug?: string }> }) {
  const slug = p.slug || ''
  const cats = (p.categories || []).map((c) => c.slug || '')
  if (eventLandingSlugs.has(slug) || cats.includes('events')) {
    return `/events/${slug}`
  }
  return `/product/${slug}`
}

useSeoMeta({ title: 'Shop — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero
      brand="SUPER NINETY FOUR"
      title="Shop"
      lede="Reciprocal programs and circuit products."
      compact
    />

    <section class="snf-section snf-frame">
      <header class="snf-home-section__header" style="margin-block-end: 1.25rem">
        <h2 class="snf-home-section__title" style="font-size: clamp(1.35rem, 2.5vw, 1.75rem)">
          Reciprocal programs
        </h2>
        <p class="snf-home-section__lede m-0" style="margin-block-start: 0.5rem">
          Three doors. Same circuit. No public guestlist.
        </p>
      </header>
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <article v-for="p in reciprocalProducts" :key="p.slug" class="m-0">
          <NuxtLink :to="`/product/${p.slug}`" class="no-underline">
            <img
              :src="p.image"
              :alt="p.name"
              class="m-0 w-full object-cover"
              style="aspect-ratio: 4 / 3"
            >
            <h2 class="m-0 text-[1.0625rem] font-semibold" style="margin-block-start: 0.65rem">
              {{ p.name }}
            </h2>
            <p
              class="m-0 text-[0.68rem] uppercase tracking-[0.1em] text-white/65"
              style="margin-block-start: 0.4rem"
            >
              {{ p.scope }}
            </p>
            <p class="m-0 text-white/70" style="margin-block-start: 0.4rem; font-size: 0.92rem">
              {{ p.body }}
            </p>
            <p class="m-0 text-snf-cream" style="margin-block-start: 0.45rem">
              {{ p.price }}
              <span class="text-white/60">/mo</span>
            </p>
          </NuxtLink>
        </article>
      </div>
    </section>

    <section class="snf-section snf-frame">
      <header class="snf-home-section__header" style="margin-block-end: 1.25rem">
        <h2 class="snf-home-section__title" style="font-size: clamp(1.35rem, 2.5vw, 1.75rem)">
          Circuit catalog
        </h2>
      </header>
      <p v-if="pending" class="m-0 text-white/65">Loading catalog…</p>
      <p v-else-if="error && !catalogProducts.length" class="m-0 text-red-300">
        Could not load products. Is WooCommerce Store API enabled?
      </p>
      <p v-else-if="!catalogProducts.length" class="m-0 text-white/65">
        No additional catalog products yet.
      </p>
      <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <article v-for="p in catalogProducts" :key="p.id" class="m-0">
          <NuxtLink :to="productPath(p)" class="no-underline">
            <img
              v-if="p.images?.[0]?.src || p.images?.[0]?.thumbnail"
              :src="p.images[0].src || p.images[0].thumbnail"
              :alt="p.images[0].alt || p.name"
              class="m-0 w-full object-cover"
              style="aspect-ratio: 4 / 3"
            >
            <h2 class="m-0 text-[1.0625rem] font-semibold" style="margin-block-start: 0.65rem">{{ p.name }}</h2>
            <p class="m-0 text-snf-cream" style="margin-block-start: 0.35rem">{{ money(p.prices) }}</p>
          </NuxtLink>
        </article>
      </div>
    </section>
  </div>
</template>
