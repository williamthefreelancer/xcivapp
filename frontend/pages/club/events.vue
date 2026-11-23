<script setup lang="ts">
definePageMeta({ layout: 'club' })
const { api } = useWpApi()
const { data } = await useAsyncData('club-events', () => api<any>('snf/v1/club/desk/events'))
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="m-0 text-xl font-semibold">Events desk</h2>
    <p class="m-0 text-sm text-muted-foreground">{{ data?.lede || 'Manage published events and ticket products.' }}</p>
    <div class="flex flex-wrap gap-2">
      <UiButton v-if="data?.dashboardHref" :href="data.dashboardHref" size="sm">WP Events dashboard</UiButton>
      <UiButton v-if="data?.listHref" :href="data.listHref" variant="outline" size="sm">All events</UiButton>
    </div>
    <UiCard>
      <UiCardContent>
        <p class="m-0 text-3xl font-bold">{{ data?.count ?? '—' }}</p>
        <p class="m-0 text-sm text-muted-foreground">Published events</p>
      </UiCardContent>
    </UiCard>
  </div>
</template>
