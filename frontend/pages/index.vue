<script setup lang="ts">
const { api } = useWpApi()

type HomePayload = {
  brand?: {
    name?: string
    hero?: {
      title?: string
      lede?: string
      cta?: { label?: string; path?: string }
      secondary?: { label?: string; path?: string }
    }
  }
  content?: {
    help?: {
      title: string
      lede?: string
      cards: Array<{
        title: string
        meta?: string
        body?: string
        url?: string
        image?: string
        cta?: string
      }>
    }
    gatherings?: {
      title: string
      lede?: string
      cards: Array<{
        title: string
        body?: string
        price?: string
        url?: string
        image?: string
        cta?: string
        slug?: string
      }>
    }
    audience?: {
      for?: { title: string; cards: Array<{ title: string; body: string }> }
      not_for?: { title: string; cards: Array<{ title: string; body: string }> }
    }
    membership?: {
      title: string
      lede?: string
      initiation?: { label: string; amount: string; meta?: string }
      plans?: Array<{ name: string; price: string; cadence?: string; term?: string; benefits?: string[] }>
      cta?: { label?: string; url?: string }
    }
  }
}

const { data } = await useAsyncData('home', () => api<HomePayload>('snf/v1/content/home'))

const hero = computed(() => data.value?.brand?.hero)
const content = computed(() => data.value?.content)
const brandName = computed(() => data.value?.brand?.name || 'SUPER NINETY FOUR')

const helpImages = {
  litter: '/images/services/litter-pickup-service-image-2.webp',
  courier: '/images/services/lifestyle-service-image-1.webp',
  janitorial: '/images/services/commercial-janitor-cleaning.webp',
} as const

const gatheringImages: Record<string, string> = {
  'the-eight-hundred-plus-journeys-event': '/images/events/eight-hundred-journeys-event.webp',
  'the-township-adventures-event': '/images/events/township-adventures-event.webp',
  'the-city-club-private-party': '/images/events/city-club-private-party.webp',
}

const fallbackHelp = {
  title: 'How can SUPER NINETY FOUR help you',
  lede: 'You don’t need membership to open the desk. You need the right stop.',
  cards: [
    {
      title: 'Litter Pickup Service',
      body: 'Lots look like you mean business — overnight, not eventually.',
      url: '/product/litter-pickup-service',
      image: helpImages.litter,
      cta: 'Open the desk',
    },
    {
      title: 'Line Sitting & Courier',
      body: 'Hold the line. Move the goods. Stay off the guestlist.',
      url: '/product/professional-line-sitting-courier-service',
      image: helpImages.courier,
      cta: 'Open the desk',
    },
    {
      title: 'Janitorial Cleaning',
      body: 'Suites and bars reset before the next room walks in.',
      url: '/product/janitorial-cleaning-service',
      image: helpImages.janitorial,
      cta: 'Open the desk',
    },
  ],
}

const fallbackGatherings = {
  title: 'Upcoming gatherings',
  lede: 'Three seats on the circuit. Prices as posted.',
  cards: [
    {
      title: 'The Eight Hundred Plus Interstate Event',
      body: 'Scenic city-to-city immersion along the SUPER NINETY FOUR journeys circuit.',
      price: '$497.00',
      url: '/events/the-eight-hundred-plus-journeys-event',
      slug: 'the-eight-hundred-plus-journeys-event',
      image: gatheringImages['the-eight-hundred-plus-journeys-event'],
      cta: 'View event',
    },
    {
      title: 'The Township Adventures Event',
      body: 'Cultural township immersion on the SUPER NINETY FOUR circuit.',
      price: '$97.00',
      url: '/events/the-township-adventures-event',
      slug: 'the-township-adventures-event',
      image: gatheringImages['the-township-adventures-event'],
      cta: 'View event',
    },
    {
      title: 'The City Club Private Party',
      body: 'Private City Clubs gathering — coach, credentials, and a closed room.',
      price: '$397.00',
      url: '/events/the-city-club-private-party',
      slug: 'the-city-club-private-party',
      image: gatheringImages['the-city-club-private-party'],
      cta: 'View event',
    },
  ],
}

function withHelpImages(
  cards: Array<{ title: string; meta?: string; body?: string; url?: string; image?: string; cta?: string }>,
) {
  return cards.map((card, i) => {
    const byOrder = [helpImages.litter, helpImages.courier, helpImages.janitorial][i]
    const title = (card.title || '').toLowerCase()
    const byTitle = title.includes('litter')
      ? helpImages.litter
      : title.includes('line') || title.includes('courier')
        ? helpImages.courier
        : title.includes('janitor')
          ? helpImages.janitorial
          : byOrder
    return { ...card, image: byTitle || card.image }
  })
}

function withGatheringImages(
  cards: Array<{ title: string; body?: string; price?: string; url?: string; image?: string; cta?: string; slug?: string }>,
) {
  return cards.map((card) => {
    const slug = card.slug
      || (card.url || '').split('/').filter(Boolean).pop()
      || ''
    return {
      ...card,
      slug,
      image: gatheringImages[slug] || card.image,
    }
  })
}

const fallbackAudience = {
  for: {
    title: 'Who this is for',
    cards: [
      { title: 'Operators', body: 'Already run rooms, routes, or estates.' },
      { title: 'Firms', body: 'Want reciprocal reach — not another listing board.' },
      { title: 'Principals', body: 'Pay for discretion, not applause.' },
    ],
  },
  not_for: {
    title: 'Who this is not for',
    cards: [
      { title: 'Browsers', body: 'This desk doesn’t educate tourists.' },
      { title: 'Almost affluent', body: 'Probable isn’t enough.' },
      { title: 'Loud rooms', body: 'If your guestlist leaks, you don’t belong here.' },
      { title: 'Badge hunters', body: 'Status without spend stays outside.' },
      { title: 'Spectacle seekers', body: 'We sell alignment, not a scene.' },
    ],
  },
}

const fallbackMembership = {
  title: 'Membership',
  lede: 'Initiation is the filter. Dues keep the desk awake.',
  initiation: {
    label: 'Initiation fee',
    amount: '$75,000.00',
    meta: 'Required. No exceptions.',
  },
  plans: [
    {
      name: 'Back Creek Plan',
      price: '$1,000.00',
      cadence: '/mo',
      term: 'for 12 months',
      benefits: ['North Carolina only — guest credentials on the Back Creek backway.'],
    },
    {
      name: 'Regions NE Plan',
      price: '$2,899.00',
      cadence: '/mo',
      term: 'for 12 months',
      benefits: ['MA North Shore and New Hampshire only — Wyoma Square hospitality.'],
    },
    {
      name: 'City to City',
      price: '$3,649.00',
      cadence: '/mo',
      term: 'for 12 months',
      benefits: ['Harrisburg / Back Creek → North Shore interstate — coach, credentials, desk.'],
    },
  ],
  cta: { label: 'Apply for consideration', url: '/apply' },
}

const help = computed(() => {
  const source = content.value?.help || fallbackHelp
  return {
    ...source,
    cards: withHelpImages(source.cards?.length ? source.cards : fallbackHelp.cards),
  }
})
const gatherings = computed(() => {
  const source = content.value?.gatherings || fallbackGatherings
  return {
    ...source,
    cards: withGatheringImages(source.cards?.length ? source.cards : fallbackGatherings.cards),
  }
})
const audienceFor = computed(() => content.value?.audience?.for || fallbackAudience.for)
const audienceNotFor = computed(() => content.value?.audience?.not_for || fallbackAudience.not_for)
const membership = computed(() => content.value?.membership || fallbackMembership)

useSeoMeta({
  title: 'SUPER NINETY FOUR',
  description:
    hero.value?.lede
    || 'Not a marketplace. A private hospitality circuit — if you already clear the bar.',
})

const route = useRoute()
const sectionIds = ['help', 'gatherings', 'fit', 'pricing'] as const

function scrollToSection(id: string) {
  if (!import.meta.client) return
  const el = document.getElementById(id)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

onMounted(() => {
  const fromQuery = typeof route.query.s === 'string' ? route.query.s : ''
  const fromHash = route.hash?.replace(/^#/, '') || ''
  const target = sectionIds.includes(fromQuery as typeof sectionIds[number])
    ? fromQuery
    : sectionIds.includes(fromHash as typeof sectionIds[number])
      ? fromHash
      : ''
  if (target) {
    nextTick(() => scrollToSection(target))
  }
})
</script>

<template>
  <div>
    <div id="top" class="snf-home-top-anchor" aria-hidden="true" />
    <SiteHero
      :brand="brandName"
      :title="hero?.title || brandName"
      :lede="hero?.lede || 'Not a marketplace. A private hospitality circuit — if you already clear the bar.'"
      :primary-label="hero?.cta?.label || 'Apply'"
      :primary-to="hero?.cta?.path || '/apply'"
      :secondary-label="hero?.secondary?.label || 'See how we help'"
      :secondary-to="hero?.secondary?.path || '/#help'"
    />

    <HomeHelpOverlay
      :title="help.title"
      :lede="help.lede"
      :cards="help.cards"
    />

    <HomeGatherings
      :title="gatherings.title"
      :lede="gatherings.lede"
      :cards="gatherings.cards"
    />

    <HomeAudience
      :for-title="audienceFor.title"
      :for-cards="audienceFor.cards"
      :not-for-title="audienceNotFor.title"
      :not-for-cards="audienceNotFor.cards"
    />

    <HomePricing
      :title="membership.title"
      :lede="membership.lede"
      :initiation="membership.initiation"
      :plans="membership.plans || []"
      :cta-label="membership.cta?.label || 'Apply for consideration'"
      :cta-to="membership.cta?.url || '/apply'"
    />

    <section id="apply-again" class="snf-section snf-frame snf-home-reveal">
      <div class="snf-home-closing">
        <h2 class="snf-home-section__title">Ready when you clear the bar</h2>
        <p class="snf-home-section__lede">
          You’re in the right room — or you’re not. Apply, or go back up and confirm the fit.
        </p>
        <div class="snf-home-closing__actions">
          <UiButton to="/apply">Apply for consideration</UiButton>
          <UiButton to="/#top" variant="outline" @click.prevent="scrollToSection('top')">
            Back to top
          </UiButton>
        </div>
      </div>
    </section>
  </div>
</template>
