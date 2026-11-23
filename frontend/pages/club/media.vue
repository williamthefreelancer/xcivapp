<script setup lang="ts">
definePageMeta({ layout: 'club' })
const { api } = useWpApi()
const { data } = await useAsyncData('club-media', () => api<any>('snf/v1/club/desk/media'))
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="m-0 text-xl font-semibold">Videos & Audio</h2>
    <p class="m-0 text-sm text-muted-foreground">{{ data?.lede || 'Media library and live rooms.' }}</p>
    <div class="flex flex-wrap gap-2">
      <UiButton v-if="data?.dashboardHref" :href="data.dashboardHref" size="sm">WP Media dashboard</UiButton>
      <UiButton v-if="data?.listHref" :href="data.listHref" variant="outline" size="sm">All videos</UiButton>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
      <UiCard>
        <UiCardContent>
          <p class="m-0 text-3xl font-bold">{{ data?.videos ?? '—' }}</p>
          <p class="m-0 text-sm text-muted-foreground">Videos</p>
        </UiCardContent>
      </UiCard>
      <UiCard>
        <UiCardContent>
          <p class="m-0 text-3xl font-bold">{{ data?.audio ?? '—' }}</p>
          <p class="m-0 text-sm text-muted-foreground">Audio</p>
        </UiCardContent>
      </UiCard>
    </div>
  </div>
</template>
