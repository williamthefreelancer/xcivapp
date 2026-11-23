<script setup lang="ts">
definePageMeta({ layout: 'account' })

const { api } = useWpApi()
const { data, error, pending } = await useAsyncData('account-orders', () =>
  api<{ panels: any[]; orders: any[] }>('snf/v1/account/orders'),
)
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="m-0 text-xl font-semibold">Orders</h2>
    <p v-if="pending" class="m-0 text-sm text-muted-foreground">Loading orders…</p>
    <p v-else-if="error" class="m-0 text-sm text-red-300">Could not load orders.</p>
    <template v-else>
      <AccountPanelGrid v-if="data?.panels?.length" :panels="data.panels" />
      <div v-else class="flex flex-col gap-3">
        <UiCard v-for="order in data?.orders || []" :key="order.id">
          <UiCardHeader>
            <UiCardTitle>Order #{{ order.number }}</UiCardTitle>
            <UiCardDescription>{{ order.date }} · {{ order.status }}</UiCardDescription>
          </UiCardHeader>
          <UiCardContent class="flex flex-wrap items-center gap-3">
            <span class="text-sm">{{ order.total }}</span>
            <UiButton v-if="order.url" :href="order.url" size="sm" variant="outline">View</UiButton>
          </UiCardContent>
        </UiCard>
        <p v-if="!(data?.orders || []).length" class="m-0 text-sm text-muted-foreground">No orders yet.</p>
      </div>
    </template>
  </div>
</template>
