<script setup lang="ts">
const { getCart, updateItem, removeItem, money, cartTotal } = useStoreApi()
const { data, error, pending, refresh } = await useAsyncData('cart', () => getCart())

async function setQty(key: string, quantity: number) {
  if (quantity < 1) {
    await removeItem(key)
  } else {
    await updateItem(key, quantity)
  }
  await refresh()
}

useSeoMeta({ title: 'Cart — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero brand="SUPER NINETY FOUR" title="Cart" compact />
    <section class="snf-section snf-frame" style="max-width: var(--snf-content-w)">
      <p v-if="pending" class="m-0 text-white/65">Loading cart…</p>
      <p v-else-if="error" class="m-0 text-red-300">Could not load cart.</p>
      <template v-else>
        <p v-if="!(data?.items || []).length" class="m-0 text-white/70">
          Your cart is empty.
          <NuxtLink to="/shop" class="underline">Browse the shop</NuxtLink>
        </p>
        <div v-else class="flex flex-col gap-4">
          <article
            v-for="item in data?.items || []"
            :key="item.key"
            class="m-0 grid gap-3 border-t border-white/15 sm:grid-cols-[auto_1fr_auto]"
            style="padding-block-start: 0.85rem"
          >
            <img
              v-if="item.images?.[0]?.thumbnail || item.images?.[0]?.src"
              :src="item.images[0].thumbnail || item.images[0].src"
              :alt="item.name"
              class="m-0 object-cover"
              style="width: 5rem; height: 5rem"
            >
            <div>
              <h2 class="m-0 text-[1.0625rem] font-semibold">{{ item.name }}</h2>
              <p class="m-0 text-snf-cream" style="margin-block-start: 0.25rem">{{ money(item.prices) }}</p>
              <div class="flex items-center gap-2" style="margin-block-start: 0.5rem">
                <UiButton size="sm" variant="outline" @click="setQty(item.key, item.quantity - 1)">−</UiButton>
                <span>{{ item.quantity }}</span>
                <UiButton size="sm" variant="outline" @click="setQty(item.key, item.quantity + 1)">+</UiButton>
                <UiButton size="sm" variant="ghost" @click="setQty(item.key, 0)">Remove</UiButton>
              </div>
            </div>
          </article>
          <p class="m-0 text-[1.125rem]" style="margin-block-start: 1rem">
            Total: <strong>{{ cartTotal(data) }}</strong>
          </p>
          <UiButton to="/checkout">Checkout</UiButton>
        </div>
      </template>
    </section>
  </div>
</template>
