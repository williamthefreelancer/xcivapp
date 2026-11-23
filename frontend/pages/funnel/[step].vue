<script setup lang="ts">
const route = useRoute()
const router = useRouter()
const { api } = useWpApi()
const step = computed(() => String(route.params.step || 'upsell'))
const orderKey = computed(() => String(route.query.order || route.query.key || ''))

const { data, pending, error } = await useAsyncData(
  () => `funnel-${step.value}-${orderKey.value}`,
  () =>
    api<any>(
      `snf/v1/funnel/${encodeURIComponent(step.value)}${orderKey.value ? `?order=${encodeURIComponent(orderKey.value)}` : ''}`,
    ),
)

async function act(action: 'accept' | 'decline') {
  try {
    const res = await api<{ next?: string }>(`snf/v1/funnel/${encodeURIComponent(step.value)}/action`, {
      method: 'POST',
      body: JSON.stringify({ action, order: orderKey.value }),
    })
    const next = res?.next || (action === 'decline' ? '/' : '/checkout')
    if (next.startsWith('http')) {
      window.location.href = next
      return
    }
    await router.push(next)
  } catch {
    if (action === 'accept' && data.value?.acceptUrl) {
      window.location.href = data.value.acceptUrl
    } else if (data.value?.declineUrl) {
      window.location.href = data.value.declineUrl
    }
  }
}
</script>

<template>
  <div class="mx-auto flex min-h-dvh w-full max-w-[40rem] flex-col justify-center gap-5" style="margin-inline: auto">
    <div style="margin-inline: var(--snf-space-x)" class="flex flex-col gap-4">
      <p class="m-0 text-[0.6875rem] font-medium uppercase tracking-[0.14em] text-white/75">
        Funnel · {{ step }}
      </p>
      <p v-if="pending" class="m-0 text-sm text-muted-foreground">Loading offer…</p>
      <p v-else-if="error" class="m-0 text-sm text-red-300">Offer unavailable.</p>
      <UiCard v-else>
        <UiCardHeader>
          <UiCardTitle>{{ data?.title || 'Special offer' }}</UiCardTitle>
          <UiCardDescription>{{ data?.lede || 'Continue through checkout to finish.' }}</UiCardDescription>
        </UiCardHeader>
        <UiCardContent class="flex flex-col gap-3">
          <p v-if="data?.productTitle" class="m-0 text-sm">
            {{ data.productTitle }}
            <span v-if="data.price"> — {{ data.price }}</span>
          </p>
          <div class="flex flex-wrap gap-2">
            <UiButton @click="act('accept')">
              {{ data?.acceptLabel || 'Add & checkout' }}
            </UiButton>
            <UiButton variant="outline" @click="act('decline')">
              {{ data?.declineLabel || 'No thanks' }}
            </UiButton>
          </div>
        </UiCardContent>
      </UiCard>
    </div>
  </div>
</template>
