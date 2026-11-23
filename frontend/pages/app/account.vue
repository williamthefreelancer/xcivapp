<script setup lang="ts">
definePageMeta({ layout: 'account' })

const { me, fetchMe, logout } = useAuth()
const { api } = useWpApi()
await fetchMe()

const form = reactive({
  display_name: me.value?.name || '',
  email: me.value?.email || '',
  first_name: '',
  last_name: '',
  password: '',
})
const status = ref<'idle' | 'saving' | 'saved' | 'error'>('idle')
const errorMsg = ref('')

async function save() {
  status.value = 'saving'
  errorMsg.value = ''
  try {
    const body: Record<string, string> = {
      display_name: form.display_name,
      email: form.email,
      first_name: form.first_name,
      last_name: form.last_name,
    }
    if (form.password) {
      body.password = form.password
    }
    await api('snf/v1/account/profile', { method: 'POST', body: JSON.stringify(body) })
    status.value = 'saved'
    form.password = ''
    await fetchMe(true)
  } catch (e: any) {
    status.value = 'error'
    errorMsg.value = e?.data?.message || 'Could not save profile.'
  }
}

async function signOut() {
  await logout()
  await navigateTo('/')
}
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="m-0 text-xl font-semibold">Account details</h2>
    <UiCard>
      <UiCardHeader>
        <UiCardTitle>{{ me?.name }}</UiCardTitle>
        <UiCardDescription>{{ me?.email }}</UiCardDescription>
      </UiCardHeader>
      <UiCardContent>
        <form class="flex flex-col gap-3" @submit.prevent="save">
          <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
            Display name
            <input v-model="form.display_name" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
          </label>
          <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
            Email
            <input v-model="form.email" type="email" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
          </label>
          <div class="grid gap-3 md:grid-cols-2">
            <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
              First name
              <input v-model="form.first_name" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
            </label>
            <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
              Last name
              <input v-model="form.last_name" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
            </label>
          </div>
          <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
            New password
            <input v-model="form.password" type="password" autocomplete="new-password" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
          </label>
          <p v-if="status === 'saved'" class="m-0 text-sm text-snf-cream">Saved.</p>
          <p v-if="errorMsg" class="m-0 text-sm text-red-300">{{ errorMsg }}</p>
          <div class="flex flex-wrap gap-2">
            <UiButton type="submit" size="sm" :disabled="status === 'saving'">
              {{ status === 'saving' ? 'Saving…' : 'Save profile' }}
            </UiButton>
            <UiButton type="button" size="sm" variant="outline" @click="signOut">Sign out</UiButton>
          </div>
        </form>
      </UiCardContent>
    </UiCard>
  </div>
</template>
