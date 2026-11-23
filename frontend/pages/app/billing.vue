<script setup lang="ts">
definePageMeta({ layout: 'account' })

const { api } = useWpApi()
const { data, error, pending, refresh } = await useAsyncData('account-addresses', () =>
  api<{ panels: any[]; addresses: any[] }>('snf/v1/account/addresses'),
)

const editing = ref<'billing' | 'shipping' | null>(null)
const form = reactive({
  type: 'billing',
  first_name: '',
  last_name: '',
  company: '',
  address_1: '',
  address_2: '',
  city: '',
  state: '',
  postcode: '',
  country: '',
  phone: '',
  email: '',
})
const saveStatus = ref<'idle' | 'saving' | 'saved' | 'error'>('idle')

function startEdit(type: 'billing' | 'shipping') {
  editing.value = type
  form.type = type
  saveStatus.value = 'idle'
}

async function saveAddress() {
  saveStatus.value = 'saving'
  try {
    await api('snf/v1/account/addresses/update', {
      method: 'POST',
      body: JSON.stringify({ ...form }),
    })
    saveStatus.value = 'saved'
    editing.value = null
    await refresh()
  } catch {
    saveStatus.value = 'error'
  }
}
</script>

<template>
  <div class="flex flex-col gap-4">
    <h2 class="m-0 text-xl font-semibold">Billing & addresses</h2>
    <p v-if="pending" class="m-0 text-sm text-muted-foreground">Loading…</p>
    <p v-else-if="error" class="m-0 text-sm text-red-300">Could not load billing details.</p>
    <template v-else>
      <AccountPanelGrid v-if="data?.panels?.length" :panels="data.panels" />
      <div class="grid gap-4 md:grid-cols-2">
        <UiCard v-for="addr in data?.addresses || []" :key="addr.type">
          <UiCardHeader>
            <UiCardTitle>{{ addr.label }}</UiCardTitle>
          </UiCardHeader>
          <UiCardContent>
            <pre class="m-0 whitespace-pre-wrap font-sans text-sm text-white/85">{{ addr.formatted || 'Not set' }}</pre>
            <UiButton
              size="sm"
              variant="outline"
              class="mt-3"
              style="margin-block-start: 0.75rem"
              @click="startEdit(addr.type)"
            >
              Edit
            </UiButton>
          </UiCardContent>
        </UiCard>
      </div>

      <UiCard v-if="editing">
        <UiCardHeader>
          <UiCardTitle>Edit {{ editing }} address</UiCardTitle>
        </UiCardHeader>
        <UiCardContent>
          <form class="grid gap-3 md:grid-cols-2" @submit.prevent="saveAddress">
            <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
              First name
              <input v-model="form.first_name" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
            </label>
            <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
              Last name
              <input v-model="form.last_name" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
            </label>
            <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em] md:col-span-2">
              Company
              <input v-model="form.company" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
            </label>
            <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em] md:col-span-2">
              Address
              <input v-model="form.address_1" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
            </label>
            <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em] md:col-span-2">
              Address 2
              <input v-model="form.address_2" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
            </label>
            <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
              City
              <input v-model="form.city" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
            </label>
            <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
              State
              <input v-model="form.state" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
            </label>
            <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
              Postcode
              <input v-model="form.postcode" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
            </label>
            <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
              Country
              <input v-model="form.country" class="border border-white/25 bg-transparent" style="min-height: 2.75rem; margin: 0">
            </label>
            <div class="flex flex-wrap gap-2 md:col-span-2">
              <UiButton type="submit" size="sm" :disabled="saveStatus === 'saving'">
                {{ saveStatus === 'saving' ? 'Saving…' : 'Save address' }}
              </UiButton>
              <UiButton type="button" size="sm" variant="ghost" @click="editing = null">Cancel</UiButton>
              <p v-if="saveStatus === 'error'" class="m-0 text-sm text-red-300">Save failed.</p>
            </div>
          </form>
        </UiCardContent>
      </UiCard>
    </template>
  </div>
</template>
