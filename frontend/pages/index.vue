<script setup lang="ts">
const { api } = useWpApi()

type HomePayload = {
  brand?: {
    name?: string
    hero?: {
      title?: string
      titleLead?: string
      titleFocus?: string
      lede?: string
      showBrand?: boolean
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
    gatherings?: {
      title: string
      lede?: string
      cards: Array<{
        slug?: string
        title: string
        body?: string
        price?: string
        url?: string
        image?: string
        cta?: string
      }>
    }
    newsletter?: {
      title?: string
      lede?: string
      youtubeUrl?: string
      benefits?: string[]
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
} as const

const fallbackHelp = {
  title: 'How can SUPER NINETY FOUR help you',
  lede: 'You don’t need membership to open the desk. You need the right stop.',
  cards: [
    {
      title: 'Litter Pickup Service',
      body: 'Lots look like you mean business — overnight, not eventually.',
      url: 'https://www.litterpickupserviceatsuperninetyfour.com/',
      image: helpImages.litter,
      cta: 'Open the desk',
    },
    {
      title: 'Line Sitting & Courier',
      body: 'Hold the line. Move the goods. Stay off the guestlist.',
      url: 'https://www.xcivcourier.com/',
      image: helpImages.courier,
      cta: 'Open the desk',
    },
  ],
}

function withHelpImages(
  cards: Array<{ title: string; meta?: string; body?: string; url?: string; image?: string; cta?: string }>,
) {
  return cards
    .filter((card) => !/(janitor|janitorial)/i.test(card.title || ''))
    .map((card, i) => {
      const byOrder = [helpImages.litter, helpImages.courier][i]
      const title = (card.title || '').toLowerCase()
      const byTitle = title.includes('litter')
        ? helpImages.litter
        : title.includes('line') || title.includes('courier')
          ? helpImages.courier
          : byOrder
      return { ...card, image: byTitle || card.image }
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

const fallbackGatherings = {
  title: 'Upcoming gatherings',
  lede: 'Open admission for logged-out guests and members — City Club Private Party and the Lifestyle Concierge Club Workshop.',
  cards: [
    {
      slug: 'the-city-club-private-party',
      title: 'The City Club Private Party',
      body: 'Open admission City Clubs gathering — coach, credentials, and a closed room. For new guests and members.',
      price: '$397.00 · Open admission',
      image: '/images/events/city-club-private-party.webp',
      url: '/events/the-city-club-private-party',
      cta: 'Get e-ticket',
    },
    {
      slug: 'the-lifestyle-concierge-club-workshop',
      title: 'The Lifestyle Concierge Club Workshop',
      body: 'Open admission workshop desk for circuit operators and advisors. For new guests and members.',
      price: 'Open admission',
      image: '/images/events/lcc-workshop.webp',
      url: '/product/the-lifestyle-concierge-club-workshop',
      cta: 'Reserve seat',
    },
  ],
}

const fallbackNewsletter = {
  title: 'The Zircoria Cryon Newsletter',
  lede: 'Films, live rooms, and circuit drops — subscribe and SureContact delivers the details.',
  youtubeUrl: 'https://www.youtube.com/@williamslist',
  benefits: [
    'Upcoming event updates and private invitations',
    'Every new YouTube video, as soon as it lands',
    'Live video and audio details delivered through SureContact',
  ],
}

const help = computed(() => {
  const source = content.value?.help || fallbackHelp
  return {
    ...source,
    cards: withHelpImages(source.cards?.length ? source.cards : fallbackHelp.cards),
  }
})
const audienceFor = computed(() => content.value?.audience?.for || fallbackAudience.for)
const audienceNotFor = computed(() => content.value?.audience?.not_for || fallbackAudience.not_for)
const membership = computed(() => content.value?.membership || fallbackMembership)
const gatherings = computed(() => {
  const source = content.value?.gatherings
  if (source?.cards?.length) return source
  return fallbackGatherings
})
const newsletter = computed(() => content.value?.newsletter || fallbackNewsletter)

useSeoMeta({
  title: 'SUPER NINETY FOUR',
  description:
    hero.value?.lede
    || 'You’re already running the desk — apply for consideration, or see how we help your rooms, routes, and estates first.',
})

const route = useRoute()
const sectionIds = ['help', 'gatherings', 'fit', 'pricing', 'newsletter'] as const

function scrollToSection(id: string) {
  if (!import.meta.client) return
  const el = document.getElementById(id)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

onMounted(() => {
  const fromQuery = typeof route.query.s === 'string' ? route.query.s : ''
  const fromError = route.query.snf_newsletter === 'error' ? 'newsletter' : ''
  const fromHash = route.hash?.replace(/^#/, '') || ''
  const target = sectionIds.includes(fromQuery as typeof sectionIds[number])
    ? fromQuery
    : sectionIds.includes(fromError as typeof sectionIds[number])
      ? fromError
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
      :show-brand="false"
      :title="hero?.title || 'If you already run rooms, routes, or estates'"
      :title-lead="hero?.titleLead || 'If you already run'"
      :title-focus="hero?.titleFocus || 'rooms, routes, or estates'"
      :lede="hero?.lede || 'You’re already running the desk — apply for consideration, or see how we help your rooms, routes, and estates first.'"
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

    <HomeNewsletter
      :title="newsletter.title"
      :lede="newsletter.lede"
      :youtube-url="newsletter.youtubeUrl"
      :benefits="newsletter.benefits"
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
