<script setup lang="ts">
definePageMeta({ layout: 'club' })
const { api } = useWpApi()
const { data } = await useAsyncData('club-addons', () => api<any>('snf/v1/club/desk/addons'))
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="m-0 text-xl font-semibold">Add-ons desk</h2>
    <p class="m-0 text-sm text-muted-foreground">{{ data?.lede }}</p>
    <p class="m-0 text-sm text-white/70">
      CPT and plugin add-on configuration stays in WordPress admin for this release.
    </p>
    <div class="flex flex-wrap gap-2">
      <UiButton v-if="data?.dashboardHref" :href="data.dashboardHref" size="sm">Open SNF Club admin</UiButton>
      <UiButton to="/club" variant="outline" size="sm">Back to overview</UiButton>
    </div>
  </div>
</template>
