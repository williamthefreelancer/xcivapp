<script setup lang="ts">
definePageMeta({ layout: 'club' })
const { api } = useWpApi()
const { data } = await useAsyncData('club-rfq', () => api<any>('snf/v1/club/desk/rfq'))
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="m-0 text-xl font-semibold">Marketplace / RFQ desk</h2>
    <p class="m-0 text-sm text-muted-foreground">{{ data?.lede }}</p>
    <UiCard>
      <UiCardContent>
        <p class="m-0 text-3xl font-bold">{{ data?.count ?? '—' }}</p>
        <p class="m-0 text-sm text-muted-foreground">RFQ posts</p>
      </UiCardContent>
    </UiCard>
    <div class="flex flex-wrap gap-2">
      <UiButton v-if="data?.listHref" :href="data.listHref" size="sm">WP RFQ list</UiButton>
      <UiButton to="/expo" variant="outline" size="sm">Public expo</UiButton>
    </div>
  </div>
</template>
