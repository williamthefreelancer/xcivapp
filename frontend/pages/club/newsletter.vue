<script setup lang="ts">
definePageMeta({ layout: 'club' })
const { api } = useWpApi()
const { data } = await useAsyncData('club-newsletter', () => api<any>('snf/v1/club/desk/newsletter'))
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="m-0 text-xl font-semibold">Newsletter desk</h2>
    <p class="m-0 text-sm text-muted-foreground">{{ data?.lede }}</p>
    <UiCard>
      <UiCardContent>
        <p class="m-0 text-3xl font-bold">{{ data?.count ?? '—' }}</p>
        <p class="m-0 text-sm text-muted-foreground">Subscribers</p>
      </UiCardContent>
    </UiCard>
    <div class="flex flex-wrap gap-2">
      <UiButton v-if="data?.dashboardHref" :href="data.dashboardHref" size="sm">WP subscribers</UiButton>
      <UiButton v-if="data?.publicPath" :to="data.publicPath" variant="outline" size="sm">Public signup</UiButton>
    </div>
  </div>
</template>
