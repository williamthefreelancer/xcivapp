<script setup lang="ts">
definePageMeta({ layout: 'account' })

const { api } = useWpApi()
const { data, error, pending } = await useAsyncData('account-dashboard', () =>
  api<{ panels: any[] }>('snf/v1/account/dashboard'),
)
</script>

<template>
  <div class="flex flex-col gap-4">
    <p v-if="pending" class="m-0 text-sm text-muted-foreground">Loading dashboard…</p>
    <p v-else-if="error" class="m-0 text-sm text-red-300">Could not load dashboard panels.</p>
    <AccountPanelGrid v-else :panels="data?.panels || []" />
  </div>
</template>
