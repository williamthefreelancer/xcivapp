<script setup lang="ts">
const route = useRoute()
const status = computed(() => {
  const raw = typeof route.query.status === 'string' ? route.query.status : 'subscribed'
  return raw === 'duplicate' ? 'duplicate' : 'subscribed'
})

useSeoMeta({
  title: 'You’re on the list — The Zircoria Cryon Newsletter',
  description: 'Live video and audio details arrive through SureContact after you subscribe.',
})
</script>

<template>
  <div>
    <SiteHero
      brand="SUPER NINETY FOUR"
      title="You’re on the list"
      :lede="status === 'duplicate'
        ? 'That email was already subscribed to The Zircoria Cryon Newsletter.'
        : 'Welcome to The Zircoria Cryon Newsletter.'"
      compact
    />

    <section class="snf-section snf-frame snf-newsletter-thanks" aria-labelledby="snf-newsletter-thanks-heading">
      <div class="snf-newsletter-thanks__panel">
        <h2 id="snf-newsletter-thanks-heading" class="snf-home-section__title">
          What happens next
        </h2>
        <p class="snf-home-section__lede snf-newsletter-thanks__lede">
          Live video and audio details are not posted publicly. You’ll get them through SureContact — watch your inbox for the next dispatch.
        </p>
        <ul class="snf-newsletter-thanks__list">
          <li>New YouTube films land in your inbox when they publish</li>
          <li>Live room links and listen-along audio arrive via SureContact</li>
          <li>Private invitations and circuit drops stay off the public page</li>
        </ul>
        <div class="snf-newsletter-thanks__actions">
          <UiButton to="/">Back to home</UiButton>
          <UiButton href="https://www.youtube.com/@williamslist" variant="outline">
            Watch on YouTube
          </UiButton>
        </div>
      </div>
    </section>
  </div>
</template>
