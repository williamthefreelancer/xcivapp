<script setup lang="ts">
definePageMeta({ layout: 'account' })

const route = useRoute()
const { api } = useWpApi()
const { data, error, pending } = await useAsyncData(`account-order-${route.params.id}`, () =>
  api<any>(`snf/v1/account/orders/${route.params.id}`),
)
</script>

<template>
  <div class="flex flex-col gap-4">
    <UiButton to="/app/orders" variant="ghost" size="sm">← Orders</UiButton>
    <p v-if="pending" class="m-0 text-sm text-muted-foreground">Loading order…</p>
    <p v-else-if="error" class="m-0 text-sm text-red-300">Order not found.</p>
    <UiCard v-else>
      <UiCardHeader>
        <UiCardTitle>Order #{{ data?.number }}</UiCardTitle>
        <UiCardDescription>{{ data?.date }} · {{ data?.status }}</UiCardDescription>
      </UiCardHeader>
      <UiCardContent class="flex flex-col gap-3">
        <p class="m-0 text-sm">Total: {{ data?.total }}</p>
        <ul class="m-0 flex list-none flex-col gap-2">
          <li v-for="(item, i) in data?.items || []" :key="i" class="text-sm text-white/85">
            {{ item.name }} × {{ item.qty }} — {{ item.total }}
          </li>
        </ul>
        <UiButton v-if="data?.checkoutUrl" :href="data.checkoutUrl" size="sm">Pay / checkout</UiButton>
      </UiCardContent>
    </UiCard>
  </div>
</template>
