<script setup lang="ts">
const { api } = useWpApi()
const { data } = await useAsyncData('newsletter', () =>
  api<{ title?: string; lede?: string; youtubeUrl?: string }>('snf/v1/content/newsletter'),
)

const form = reactive({ name: '', email: '', website: '' })
const status = ref<'idle' | 'sending' | 'subscribed' | 'duplicate' | 'error'>('idle')

async function submit() {
  status.value = 'sending'
  try {
    const res = await api<{ status?: string }>('snf/v1/forms/newsletter', {
      method: 'POST',
      body: JSON.stringify(form),
    })
    status.value = (res?.status as any) || 'subscribed'
  } catch {
    status.value = 'error'
  }
}

useSeoMeta({ title: 'Newsletter — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero
      brand="SUPER NINETY FOUR"
      :title="data?.title || 'Video newsletter'"
      :lede="data?.lede"
      compact
    />
    <section class="snf-section snf-frame" style="max-width: var(--snf-content-narrow-w)">
      <p v-if="status === 'subscribed' || status === 'duplicate'" class="m-0 text-snf-cream">
        {{ status === 'duplicate' ? 'You are already on the list.' : 'Subscribed. Watch for circuit updates.' }}
      </p>
      <form v-else class="flex flex-col gap-4" @submit.prevent="submit">
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Name
          <input v-model="form.name" class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Email
          <input v-model="form.email" type="email" required class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        </label>
        <input v-model="form.website" type="text" tabindex="-1" autocomplete="off" class="absolute left-[-100vw]" aria-hidden="true">
        <UiButton type="submit" :disabled="status === 'sending'">
          {{ status === 'sending' ? 'Saving…' : 'Subscribe' }}
        </UiButton>
        <p v-if="status === 'error'" class="m-0 text-red-300">Could not subscribe. Try again.</p>
      </form>
      <p v-if="data?.youtubeUrl" class="m-0" style="margin-block-start: 1.5rem">
        <UiButton :href="data.youtubeUrl" variant="outline">YouTube channel</UiButton>
      </p>
    </section>
  </div>
</template>
