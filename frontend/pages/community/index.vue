<script setup lang="ts">
const { api } = useWpApi()
const { data, refresh } = await useAsyncData('community-circles', () =>
  api<{ circles: any[] }>('snf/v1/community/circles'),
)

async function join(id: number) {
  await api(`snf/v1/community/circles/${id}/join`, { method: 'POST', body: '{}' })
  await refresh()
}

async function leave(id: number) {
  await api(`snf/v1/community/circles/${id}/leave`, { method: 'POST', body: '{}' })
  await refresh()
}

useSeoMeta({ title: 'Community — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero brand="SUPER NINETY FOUR" title="Community" lede="Circles on the private desk." compact />
    <section class="snf-section snf-frame grid gap-6 md:grid-cols-2">
      <article
        v-for="circle in data?.circles || []"
        :key="circle.id"
        class="m-0 border border-white/15"
      >
        <div style="margin: 1rem">
          <h2 class="m-0 font-display text-[1.5rem] uppercase">{{ circle.title || circle.name }}</h2>
          <p class="m-0 text-white/70" style="margin-block-start: 0.5rem">
            {{ circle.excerpt || circle.description || '' }}
          </p>
          <p class="m-0 text-sm text-white/55" style="margin-block-start: 0.5rem">
            Members: {{ circle.members_count ?? circle.memberCount ?? '—' }}
          </p>
          <div class="flex gap-2" style="margin-block-start: 0.85rem">
            <UiButton
              v-if="circle.is_member || circle.joined"
              size="sm"
              variant="outline"
              @click="leave(circle.id)"
            >
              Leave
            </UiButton>
            <UiButton v-else size="sm" @click="join(circle.id)">Join</UiButton>
          </div>
        </div>
      </article>
      <p v-if="!(data?.circles || []).length" class="m-0 text-white/65">No circles published yet.</p>
    </section>
  </div>
</template>
