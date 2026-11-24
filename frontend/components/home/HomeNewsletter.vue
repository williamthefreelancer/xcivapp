<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string
    lede?: string
    youtubeUrl?: string
    benefits?: string[]
  }>(),
  {
    title: 'The Zircoria Cryon Newsletter',
    lede: 'Films, live rooms, and circuit drops — subscribe and SureContact delivers the details.',
    youtubeUrl: 'https://www.youtube.com/@williamslist',
    benefits: () => [
      'Upcoming event updates and private invitations',
      'Every new YouTube video, as soon as it lands',
      'Live video and audio details delivered through SureContact',
    ],
  },
)

const { api } = useWpApi()
const router = useRouter()

const form = reactive({ email: '', phone: '', website: '' })
const status = ref<'idle' | 'sending' | 'error'>('idle')

async function submit() {
  status.value = 'sending'
  try {
    const res = await api<{ status?: string; next?: string }>('snf/v1/forms/newsletter', {
      method: 'POST',
      body: JSON.stringify(form),
    })
    const result = res?.status || 'subscribed'
    if (result === 'error') {
      status.value = 'error'
      return
    }
    const next = res?.next || `/thank-you/newsletter?status=${encodeURIComponent(result)}`
    await router.push(next.startsWith('/') ? next : `/thank-you/newsletter?status=${encodeURIComponent(result)}`)
  } catch {
    status.value = 'error'
  }
}
</script>

<template>
  <section id="newsletter" class="snf-section snf-frame snf-home-reveal" aria-labelledby="snf-home-newsletter-heading">
    <div class="snf-home-newsletter">
      <header class="snf-home-newsletter__header">
        <h2 id="snf-home-newsletter-heading" class="snf-home-section__title">
          {{ title }}
        </h2>
        <p v-if="lede" class="snf-home-section__lede">{{ lede }}</p>
      </header>

      <ul v-if="benefits.length" class="snf-home-newsletter__benefits">
        <li v-for="(benefit, i) in benefits" :key="i">{{ benefit }}</li>
      </ul>

      <form class="snf-home-newsletter__form" @submit.prevent="submit">
        <input
          v-model="form.website"
          type="text"
          tabindex="-1"
          autocomplete="off"
          class="snf-home-newsletter__honeypot"
          aria-hidden="true"
        >
        <div class="snf-home-newsletter__fields">
          <label class="snf-home-newsletter__field">
            <span>Email</span>
            <input v-model="form.email" type="email" autocomplete="email" required placeholder="you@example.com">
          </label>
          <label class="snf-home-newsletter__field">
            <span>Phone number</span>
            <input v-model="form.phone" type="tel" autocomplete="tel" required placeholder="(555) 000-0000">
          </label>
        </div>

        <p v-if="status === 'error'" class="snf-home-newsletter__flash snf-home-newsletter__flash--err" role="alert">
          Could not subscribe. Try again.
        </p>

        <div class="snf-home-newsletter__actions">
          <UiButton type="submit" :disabled="status === 'sending'">
            {{ status === 'sending' ? 'Saving…' : 'Subscribe' }}
          </UiButton>
          <UiButton v-if="youtubeUrl" :href="youtubeUrl" variant="outline">
            Watch on YouTube
          </UiButton>
        </div>

        <p class="snf-home-newsletter__privacy">
          Live video and audio links stay private — SureContact sends them after you join. Unsubscribe any time.
        </p>
      </form>
    </div>
  </section>
</template>
