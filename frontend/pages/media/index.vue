<script setup lang="ts">
const { api } = useWpApi()
const tab = ref<'videos' | 'audio'>('videos')

const { data: videos } = await useAsyncData('media-videos', () =>
  api<{ items: any[] }>('snf/v1/media/videos'),
)
const { data: audio } = await useAsyncData('media-audio', () =>
  api<{ items: any[] }>('snf/v1/media/audio'),
)

useSeoMeta({ title: 'Videos & Audio — SUPER NINETY FOUR' })
</script>

<template>
  <div>
    <SiteHero brand="SUPER NINETY FOUR" title="Videos & Audio" lede="Circuit media library." compact />
    <section class="snf-section snf-frame">
      <div class="flex gap-2" style="margin-block-end: 1.25rem">
        <UiButton :variant="tab === 'videos' ? 'default' : 'outline'" size="sm" @click="tab = 'videos'">Videos</UiButton>
        <UiButton :variant="tab === 'audio' ? 'default' : 'outline'" size="sm" @click="tab = 'audio'">Audio</UiButton>
      </div>

      <div v-if="tab === 'videos'" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <article v-for="item in videos?.items || []" :key="item.id || item.slug" class="m-0">
          <img
            v-if="item.thumb || item.thumbnail || item.image"
            :src="item.thumb || item.thumbnail || item.image"
            :alt="item.title || ''"
            class="m-0 w-full object-cover"
            style="aspect-ratio: 16 / 9"
          >
          <h2 class="m-0 text-[1.0625rem] font-semibold" style="margin-block-start: 0.65rem">
            {{ item.title }}
          </h2>
          <p v-if="item.excerpt || item.description" class="m-0 text-sm text-white/70" style="margin-block-start: 0.35rem">
            {{ item.excerpt || item.description }}
          </p>
          <UiButton
            v-if="item.url || item.watch_url || item.embed_url"
            :href="item.url || item.watch_url || item.embed_url"
            size="sm"
            variant="outline"
            style="margin-block-start: 0.75rem"
          >
            Watch
          </UiButton>
        </article>
        <p v-if="!(videos?.items || []).length" class="m-0 text-white/65">No videos available.</p>
      </div>

      <div v-else class="grid gap-4">
        <article
          v-for="item in audio?.items || []"
          :key="item.id || item.slug"
          class="m-0 border-t border-white/15"
          style="padding-block-start: 0.85rem"
        >
          <h2 class="m-0 text-[1.0625rem] font-semibold">{{ item.title }}</h2>
          <p v-if="item.excerpt" class="m-0 text-sm text-white/70" style="margin-block-start: 0.35rem">{{ item.excerpt }}</p>
          <audio
            v-if="item.src || item.audio_url || item.url"
            controls
            :src="item.src || item.audio_url || item.url"
            style="margin-block-start: 0.65rem; width: 100%; max-width: 36rem"
          />
        </article>
        <p v-if="!(audio?.items || []).length" class="m-0 text-white/65">No audio available.</p>
      </div>
    </section>
  </div>
</template>
