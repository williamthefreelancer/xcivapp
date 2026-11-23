<script setup lang="ts">
definePageMeta({ layout: 'account' })

const { api } = useWpApi()
const { data, error, pending } = await useAsyncData('account-tickets', () =>
  api<{ panels: any[] }>('snf/v1/account/tickets'),
)
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="m-0 text-xl font-semibold">Tickets</h2>
    <p v-if="pending" class="m-0 text-sm text-muted-foreground">Loading tickets…</p>
    <p v-else-if="error" class="m-0 text-sm text-red-300">Could not load tickets.</p>
    <AccountPanelGrid v-else :panels="data?.panels || []" />
  </div>
</template>
