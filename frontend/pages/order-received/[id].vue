<script setup lang="ts">
const route = useRoute()
const id = computed(() => String(route.params.id || ''))
const { api } = useWpApi()

const { data } = await useAsyncData(
  () => `order-${id.value}`,
  () => api<any>(`snf/v1/account/orders/${id.value}`).catch(() => null),
  { watch: [id] },
)

useSeoMeta({ title: 'Order received — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero
      brand="SUPER NINETY FOUR"
      title="Order received"
      :lede="data ? `Order #${data.number || id} — ${data.status || 'processing'}` : `Order #${id}`"
      primary-label="Member dashboard"
      primary-to="/app"
      secondary-label="Continue shopping"
      secondary-to="/shop"
      compact
    />
    <section v-if="data" class="snf-section snf-frame" style="max-width: var(--snf-content-narrow-w)">
      <p class="m-0">Total: {{ data.total }}</p>
      <ul class="m-0 list-none p-0" style="margin-block-start: 1rem">
        <li v-for="(item, i) in data.items || []" :key="i" style="margin-block: 0.35rem">
          {{ item.name }} × {{ item.qty }} — {{ item.total }}
        </li>
      </ul>
    </section>
  </div>
</template>
