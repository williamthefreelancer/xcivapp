<script setup lang="ts">
definePageMeta({ layout: 'default' })

const { login, me, fetchMe } = useAuth()
const route = useRoute()
const router = useRouter()

await fetchMe()
if (me.value) {
  await router.replace(typeof route.query.redirect === 'string' ? route.query.redirect : '/app')
}

const form = reactive({ username: '', password: '', remember: true })
const status = ref<'idle' | 'sending' | 'error'>('idle')
const errorMsg = ref('')

async function submit() {
  status.value = 'sending'
  errorMsg.value = ''
  try {
    await login(form.username, form.password, form.remember)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/app'
    await router.push(redirect)
  } catch (e: any) {
    status.value = 'error'
    errorMsg.value = e?.data?.message || e?.statusMessage || 'Invalid credentials.'
  }
}

useSeoMeta({ title: 'Member login — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero brand="SUPER NINETY FOUR" title="Member login" lede="Sign in to your private desk." compact />
    <section class="snf-section snf-frame" style="max-width: 28rem">
      <form class="flex flex-col gap-4" @submit.prevent="submit">
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Username or email
          <input
            v-model="form.username"
            required
            autocomplete="username"
            class="border border-white/25 bg-transparent text-white"
            style="min-height: 2.75rem; margin: 0"
          >
        </label>
        <label class="flex flex-col gap-1 text-[0.75rem] uppercase tracking-[0.1em]">
          Password
          <input
            v-model="form.password"
            type="password"
            required
            autocomplete="current-password"
            class="border border-white/25 bg-transparent text-white"
            style="min-height: 2.75rem; margin: 0"
          >
        </label>
        <label class="flex items-center gap-2 text-[0.8125rem] text-white/80">
          <input v-model="form.remember" type="checkbox">
          Remember me
        </label>
        <p v-if="errorMsg" class="m-0 text-red-300">{{ errorMsg }}</p>
        <UiButton type="submit" :disabled="status === 'sending'">
          {{ status === 'sending' ? 'Signing in…' : 'Sign in' }}
        </UiButton>
      </form>
      <p class="m-0 text-white/65" style="margin-block-start: 1.25rem">
        New to the circuit?
        <NuxtLink to="/apply" class="underline">Apply for consideration</NuxtLink>
      </p>
    </section>
  </div>
</template>
