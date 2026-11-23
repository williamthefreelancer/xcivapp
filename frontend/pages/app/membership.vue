<script setup lang="ts">
definePageMeta({ layout: 'account' })

const { api } = useWpApi()
const { data, error, pending } = await useAsyncData('account-membership', () =>
  api<{ panels: any[]; engine_label?: string }>('snf/v1/account/membership'),
)
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="m-0 text-xl font-semibold">Membership</h2>
    <p v-if="data?.engine_label" class="m-0 text-sm text-muted-foreground">{{ data.engine_label }}</p>
    <p v-if="pending" class="m-0 text-sm text-muted-foreground">Loading membership…</p>
    <p v-else-if="error" class="m-0 text-sm text-red-300">Could not load membership.</p>
    <AccountPanelGrid v-else :panels="data?.panels || []" />
  </div>
</template>
