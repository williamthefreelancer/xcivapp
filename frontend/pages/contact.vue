<script setup lang="ts">
const { api } = useWpApi()
const { data } = await useAsyncData('contact', () =>
  api<{
    content?: {
      title: string
      lede: string
      days: Array<{ value: string; label: string }>
      times: Array<{ value: string; label: string }>
      intents: Array<{ value: string; label: string }>
    }
  }>('snf/v1/content/contact'),
)

const form = reactive({
  name: '',
  email: '',
  phone: '',
  org: '',
  day: '',
  time: '',
  intent: '',
  message: '',
})
const status = ref<'idle' | 'sending' | 'sent' | 'error'>('idle')
const errorMsg = ref('')

async function submit() {
  status.value = 'sending'
  errorMsg.value = ''
  try {
    await api('snf/v1/forms/contact', { method: 'POST', body: JSON.stringify(form) })
    status.value = 'sent'
  } catch (e: any) {
    status.value = 'error'
    errorMsg.value = e?.data?.message || e?.statusMessage || 'Could not send.'
  }
}

useSeoMeta({ title: 'Contact — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero
      brand="SUPER NINETY FOUR"
      :title="data?.content?.title || `Let's Connect`"
      :lede="data?.content?.lede"
      compact
    />
    <section class="snf-section snf-frame" style="max-width: var(--snf-content-narrow-w)">
      <p v-if="status === 'sent'" class="m-0 text-snf-cream">Request sent. William’s desk will follow up.</p>
      <form v-else class="flex flex-col gap-4" @submit.prevent="submit">
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Name
          <input v-model="form.name" required class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Email
          <input v-model="form.email" type="email" required class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Phone
          <input v-model="form.phone" required class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Organization
          <input v-model="form.org" class="border border-white/25 bg-transparent text-white" style="min-height: 2.75rem; margin: 0">
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Day
          <select v-model="form.day" required class="border border-white/25 bg-[var(--snf-grad-bottom)] text-white" style="min-height: 2.75rem; margin: 0">
            <option value="" disabled>Select</option>
            <option v-for="d in data?.content?.days || []" :key="d.value" :value="d.value">{{ d.label }}</option>
          </select>
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Time
          <select v-model="form.time" required class="border border-white/25 bg-[var(--snf-grad-bottom)] text-white" style="min-height: 2.75rem; margin: 0">
            <option value="" disabled>Select</option>
            <option v-for="t in data?.content?.times || []" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Intent
          <select v-model="form.intent" required class="border border-white/25 bg-[var(--snf-grad-bottom)] text-white" style="min-height: 2.75rem; margin: 0">
            <option value="" disabled>Select</option>
            <option v-for="i in data?.content?.intents || []" :key="i.value" :value="i.value">{{ i.label }}</option>
          </select>
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Message
          <textarea v-model="form.message" required rows="5" class="border border-white/25 bg-transparent text-white" style="margin: 0" />
        </label>
        <p v-if="errorMsg" class="m-0 text-[0.875rem] text-red-300">{{ errorMsg }}</p>
        <UiButton type="submit" :disabled="status === 'sending'">
          {{ status === 'sending' ? 'Sending…' : 'Send request' }}
        </UiButton>
      </form>
    </section>
  </div>
</template>
