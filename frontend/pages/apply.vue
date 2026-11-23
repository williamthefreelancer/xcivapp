<script setup lang="ts">
const { api } = useWpApi()
const { data } = await useAsyncData('apply', () =>
  api<{ title?: string; lede?: string; next?: { path?: string } }>('snf/v1/content/apply'),
)

const form = reactive({ name: '', email: '', org: '', intent: '', message: '' })
const status = ref<'idle' | 'sending' | 'sent' | 'error'>('idle')
const router = useRouter()

async function submit() {
  status.value = 'sending'
  try {
    const res = await api<{ next?: string }>('snf/v1/forms/apply', {
      method: 'POST',
      body: JSON.stringify(form),
    })
    status.value = 'sent'
    await router.push(res?.next || data.value?.next?.path || '/under-consideration')
  } catch {
    status.value = 'error'
  }
}

useSeoMeta({ title: 'Apply — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero
      brand="SUPER NINETY FOUR"
      :title="data?.title || 'Apply for consideration'"
      :lede="data?.lede"
      compact
    />
    <section class="snf-section snf-frame" style="max-width: var(--snf-content-narrow-w)">
      <form class="flex flex-col gap-4" @submit.prevent="submit">
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Name
          <input v-model="form.name" required class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Email
          <input v-model="form.email" type="email" required class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Organization
          <input v-model="form.org" class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Intent
          <input v-model="form.intent" class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Message
          <textarea v-model="form.message" required rows="5" class="border border-white/25 bg-transparent text-white" style="margin: 0" />
        </label>
        <p v-if="status === 'error'" class="m-0 text-red-300">Could not send application.</p>
        <UiButton type="submit" :disabled="status === 'sending'">
          {{ status === 'sending' ? 'Sending…' : 'Submit application' }}
        </UiButton>
      </form>
    </section>
  </div>
</template>
