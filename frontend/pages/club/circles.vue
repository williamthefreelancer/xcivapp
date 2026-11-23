<script setup lang="ts">
definePageMeta({ layout: 'club' })
const { api } = useWpApi()
const { data } = await useAsyncData('club-circles', () => api<any>('snf/v1/club/desk/circles'))
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="m-0 text-xl font-semibold">Circles desk</h2>
    <p class="m-0 text-sm text-muted-foreground">{{ data?.lede || 'Club circles and reciprocal groups.' }}</p>
    <div class="flex flex-wrap gap-2">
      <UiButton v-if="data?.dashboardHref" :href="data.dashboardHref" size="sm">WP Circles dashboard</UiButton>
      <UiButton v-if="data?.listHref" :href="data.listHref" variant="outline" size="sm">All circles</UiButton>
    </div>
    <UiCard>
      <UiCardContent>
        <p class="m-0 text-3xl font-bold">{{ data?.count ?? '—' }}</p>
        <p class="m-0 text-sm text-muted-foreground">Circles</p>
      </UiCardContent>
    </UiCard>
  </div>
</template>
