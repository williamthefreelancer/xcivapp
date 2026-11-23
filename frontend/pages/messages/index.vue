<script setup lang="ts">
const { api } = useWpApi()
const { data, refresh } = await useAsyncData('messages-threads', () =>
  api<{ threads: any[] }>('snf/v1/messages/threads'),
)

const activeId = ref<number | null>(null)
const thread = ref<any>(null)
const draft = ref('')
const sending = ref(false)

async function openThread(id: number) {
  activeId.value = id
  thread.value = await api(`snf/v1/messages/threads/${id}`)
}

async function send() {
  if (!activeId.value || !draft.value.trim()) return
  sending.value = true
  try {
    await api(`snf/v1/messages/threads/${activeId.value}/send`, {
      method: 'POST',
      body: JSON.stringify({ message: draft.value }),
    })
    draft.value = ''
    await openThread(activeId.value)
    await refresh()
  } finally {
    sending.value = false
  }
}

useSeoMeta({ title: 'Messages — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero brand="SUPER NINETY FOUR" title="Messages" compact />
    <section class="snf-section snf-frame grid gap-6 lg:grid-cols-[18rem_1fr]">
      <aside class="flex flex-col gap-2">
        <button
          v-for="t in data?.threads || []"
          :key="t.id"
          type="button"
          class="border border-white/15 bg-transparent text-left text-white"
          style="margin: 0; min-height: 2.75rem"
          :class="activeId === t.id ? 'border-white/50' : ''"
          @click="openThread(t.id)"
        >
          <span style="margin-inline: 0.75rem">{{ t.title || t.peer_name || `Thread #${t.id}` }}</span>
        </button>
        <p v-if="!(data?.threads || []).length" class="m-0 text-white/65">No threads yet.</p>
      </aside>
      <div>
        <template v-if="thread">
          <div class="flex flex-col gap-3" style="min-height: 18rem">
            <article
              v-for="m in thread.messages || []"
              :key="m.id"
              class="m-0 border-t border-white/10"
              style="padding-block-start: 0.65rem"
            >
              <p class="m-0 text-[0.75rem] uppercase tracking-[0.1em] text-white/55">
                {{ m.author_name || m.author || 'Member' }}
              </p>
              <p class="m-0" style="margin-block-start: 0.25rem">{{ m.body || m.content || m.message }}</p>
            </article>
          </div>
          <form class="flex flex-col gap-2" style="margin-block-start: 1rem" @submit.prevent="send">
            <textarea v-model="draft" rows="3" class="border border-white/25 bg-transparent text-white" style="margin: 0" />
            <UiButton type="submit" size="sm" :disabled="sending">Send</UiButton>
          </form>
        </template>
        <p v-else class="m-0 text-white/65">Select a thread.</p>
      </div>
    </section>
  </div>
</template>
