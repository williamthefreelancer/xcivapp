<script setup lang="ts">
type ProductItem = {
  id: string
  label: string
  short: string
  glyph: string
  blurb: string
  url: string
  current: boolean
  external: boolean
}

const items: ProductItem[] = [
  {
    id: 'superninetyfour',
    label: 'Super Ninety Four',
    short: 'SN4',
    glyph: '94',
    blurb: 'Culture, drops & merch',
    url: '/',
    current: true,
    external: false,
  },
  {
    id: 'courier',
    label: 'XCIV Courier',
    short: 'Courier',
    glyph: 'C',
    blurb: 'On-demand delivery & pickup',
    url: 'https://xcivcourier.com/',
    current: false,
    external: true,
  },
  {
    id: 'xcivsocial',
    label: 'XCIV Social',
    short: 'Social',
    glyph: 'S',
    blurb: 'Connect with the community',
    url: 'https://xcivsocial.com/',
    current: false,
    external: true,
  },
  {
    id: 'litterpickup',
    label: 'Litter Pickup Service',
    short: 'Litter',
    glyph: 'L',
    blurb: 'Exterior litter pickup routes',
    url: 'https://www.litterpickupserviceatsuperninetyfour.com/',
    current: false,
    external: true,
  },
  {
    id: 'holdings',
    label: 'SUPR XCIV Holdings',
    short: 'Holdings',
    glyph: 'H',
    blurb: 'Parent company & platforms',
    url: 'https://www.suprxcivplatformholdings.com/',
    current: false,
    external: true,
  },
]

const open = ref(false)
const root = ref<HTMLElement | null>(null)
const current = computed(() => items.find((item) => item.current) ?? items[0])

const switchLabel = computed(
  () => `Switch product, current: ${current.value.short}`,
)

function toggle() {
  open.value = !open.value
}

function close() {
  open.value = false
}

function onDocumentClick(event: MouseEvent) {
  if (!root.value || !(event.target instanceof Node)) return
  if (!root.value.contains(event.target)) close()
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') close()
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div
    ref="root"
    class="snf-product-switcher"
    :class="{ 'is-open': open }"
    data-snf-product-switcher
  >
    <button
      type="button"
      class="snf-product-switcher__trigger"
      :aria-expanded="open"
      aria-haspopup="menu"
      aria-controls="snf-product-switcher-menu"
      :aria-label="switchLabel"
      @click.stop="toggle"
    >
      <span class="snf-product-switcher__glyph" aria-hidden="true">{{ current.glyph }}</span>
      <span class="snf-product-switcher__current">{{ current.short }}</span>
      <svg
        class="snf-product-switcher__chevron"
        width="12"
        height="12"
        viewBox="0 0 24 24"
        aria-hidden="true"
        focusable="false"
      >
        <path
          d="M6 9l6 6 6-6"
          fill="none"
          stroke="currentColor"
          stroke-width="2.2"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </button>

    <div
      id="snf-product-switcher-menu"
      class="snf-product-switcher__menu"
      role="menu"
      aria-label="Switch product"
      :hidden="!open"
    >
      <template v-for="item in items" :key="item.id">
        <a
          v-if="item.external"
          class="snf-product-switcher__item"
          :class="{ 'is-current': item.current }"
          role="menuitem"
          :href="item.url"
          rel="noopener noreferrer"
          :data-snf-product="item.id"
          @click="close"
        >
          <span class="snf-product-switcher__item-glyph" aria-hidden="true">{{ item.glyph }}</span>
          <span class="snf-product-switcher__item-text">
            <span class="snf-product-switcher__item-label">{{ item.label }}</span>
            <span v-if="item.blurb" class="snf-product-switcher__item-blurb">{{ item.blurb }}</span>
          </span>
          <span class="snf-product-switcher__check-spacer" aria-hidden="true" />
        </a>
        <NuxtLink
          v-else
          class="snf-product-switcher__item"
          :class="{ 'is-current': item.current }"
          role="menuitem"
          :to="item.url"
          :aria-current="item.current ? 'page' : undefined"
          :data-snf-product="item.id"
          @click="close"
        >
          <span class="snf-product-switcher__item-glyph" aria-hidden="true">{{ item.glyph }}</span>
          <span class="snf-product-switcher__item-text">
            <span class="snf-product-switcher__item-label">{{ item.label }}</span>
            <span v-if="item.blurb" class="snf-product-switcher__item-blurb">{{ item.blurb }}</span>
          </span>
          <svg
            v-if="item.current"
            class="snf-product-switcher__check"
            width="14"
            height="14"
            viewBox="0 0 24 24"
            aria-hidden="true"
            focusable="false"
          >
            <path
              d="M5 12.5l4.5 4.5L19 7.5"
              fill="none"
              stroke="currentColor"
              stroke-width="2.2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
          <span v-else class="snf-product-switcher__check-spacer" aria-hidden="true" />
        </NuxtLink>
      </template>
    </div>
  </div>
</template>
