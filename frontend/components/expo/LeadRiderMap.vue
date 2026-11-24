<script setup lang="ts">
import mapboxgl from 'mapbox-gl'
import type { ExpoBootstrap, LeadRiderRoute } from '~/composables/useLeadRider'

const props = defineProps<{
  boot: ExpoBootstrap
}>()

const { api } = useWpApi()
const { restPath } = useLeadRider()

const mapEl = ref<HTMLElement | null>(null)
const fallback = ref('')
const statusKey = ref<'standby' | 'on_road'>('standby')
const activeRouteId = ref(props.boot.defaultRouteId || props.boot.routes[0]?.id || '')
const talking = ref(false)
const broadcasting = ref(false)
const pttLabel = computed(() =>
  talking.value ? props.boot.i18n.release || 'Release' : props.boot.i18n.holdToTalk || 'Hold to talk',
)

let map: mapboxgl.Map | null = null
let riderMarker: mapboxgl.Marker | null = null
let geoWatchId: number | null = null
let positionTimer: ReturnType<typeof setInterval> | null = null
let peerTimer: ReturnType<typeof setInterval> | null = null
let signalTimer: ReturnType<typeof setInterval> | null = null
let localStream: MediaStream | null = null
let lastSignalId = 0

const peerId =
  'snf-' + Math.random().toString(36).slice(2, 10) + '-' + Date.now().toString(36)
const peers = new Map<string, { peer_id: string; name?: string }>()
const pcMap = new Map<string, RTCPeerConnection>()

const iceServers =
  Array.isArray(props.boot.ice) && props.boot.ice.length
    ? props.boot.ice
    : [{ urls: 'stun:stun.l.google.com:19302' }]

const routes = computed(() => (Array.isArray(props.boot.routes) ? props.boot.routes : []))

function findRoute(id: string): LeadRiderRoute | null {
  return routes.value.find((r) => r.id === id) || routes.value[0] || null
}

function showFallback(message: string) {
  fallback.value = message
}

function ensureRiderMarker(lngLat: [number, number]) {
  if (!map) return
  if (!riderMarker) {
    const el = document.createElement('div')
    el.className = 'snf-expo-map__marker'
    el.innerHTML =
      '<span class="snf-expo-map__marker-dot"></span><span class="snf-expo-map__marker-label"></span>'
    const label = el.querySelector('.snf-expo-map__marker-label')
    if (label) label.textContent = props.boot.leadRiderName || 'William A. Johnson'
    riderMarker = new mapboxgl.Marker({ element: el, anchor: 'bottom' }).setLngLat(lngLat)
    riderMarker.addTo(map)
  } else {
    riderMarker.setLngLat(lngLat)
  }
}

function drawRoute(route: LeadRiderRoute | null) {
  if (!map || !route) return
  if (!map.isStyleLoaded()) {
    map.once('load', () => drawRoute(route))
    return
  }
  const sourceId = 'snf-route'
  const data = {
    type: 'Feature' as const,
    properties: {},
    geometry: {
      type: 'LineString' as const,
      coordinates: route.coordinates || [],
    },
  }
  const source = map.getSource(sourceId) as mapboxgl.GeoJSONSource | undefined
  if (source) {
    source.setData(data as any)
  } else {
    map.addSource(sourceId, { type: 'geojson', data: data as any })
    map.addLayer({
      id: 'snf-route-line',
      type: 'line',
      source: sourceId,
      layout: { 'line-join': 'round', 'line-cap': 'round' },
      paint: {
        'line-color': '#f3ebe8',
        'line-width': 3.5,
        'line-opacity': 0.85,
      },
    })
  }
  map.easeTo({
    center: route.center as [number, number],
    zoom: route.zoom || 6,
    duration: 700,
  })
}

function selectRoute(id: string) {
  activeRouteId.value = id
  drawRoute(findRoute(id))
}

function initMap() {
  if (!mapEl.value) return
  const token = props.boot.mapboxToken
  if (!token) {
    showFallback(props.boot.i18n.noToken || 'Mapbox token missing.')
    return
  }

  mapboxgl.accessToken = token
  const route = findRoute(activeRouteId.value)
  const center = (route?.center || [-77.5, 38.2]) as [number, number]

  map = new mapboxgl.Map({
    container: mapEl.value,
    style: 'mapbox://styles/mapbox/dark-v11',
    center,
    zoom: route?.zoom || 5.4,
    attributionControl: true,
  })
  map.addControl(new mapboxgl.NavigationControl({ visualizePitch: false }), 'top-right')

  map.on('error', (event) => {
    const err = (event && (event as any).error) || {}
    const status = err.status || (err.response && err.response.status)
    const message = String(err.message || '')
    if (status === 401 || status === 403 || /access token|unauthorized|forbidden/i.test(message)) {
      showFallback(props.boot.i18n.mapError || props.boot.i18n.noToken || 'Map error')
    }
  })

  map.on('load', () => {
    fallback.value = ''
    drawRoute(route)
    if (route?.coordinates?.[0]) {
      ensureRiderMarker(route.coordinates[0] as [number, number])
    }
  })
}

async function fetchPosition() {
  if (!props.boot.canLive || !props.boot.rest?.position) return
  try {
    const data = await api<any>(restPath(props.boot.rest.position))
    if (data && data.lat != null && data.lng != null) {
      ensureRiderMarker([data.lng, data.lat])
      statusKey.value = data.status === 'standby' ? 'standby' : 'on_road'
    } else {
      statusKey.value = 'standby'
    }
  } catch {
    /* ignore poll errors */
  }
}

function startPositionPoll() {
  if (!props.boot.canLive) return
  fetchPosition()
  positionTimer = setInterval(fetchPosition, props.boot.pollMs || 4000)
}

async function postPosition(coords: GeolocationCoordinates) {
  if (!props.boot.canBroadcast || !props.boot.rest?.position) return
  try {
    await api(restPath(props.boot.rest.position), {
      method: 'POST',
      body: JSON.stringify({
        lat: coords.latitude,
        lng: coords.longitude,
        heading: coords.heading || null,
        status: 'on_road',
      }),
    })
    ensureRiderMarker([coords.longitude, coords.latitude])
    statusKey.value = 'on_road'
  } catch {
    /* ignore */
  }
}

function startBroadcast() {
  if (!props.boot.canBroadcast || !navigator.geolocation || broadcasting.value) return
  broadcasting.value = true
  geoWatchId = navigator.geolocation.watchPosition(
    (pos) => postPosition(pos.coords),
    () => {
      broadcasting.value = false
      window.alert(props.boot.i18n.geoDenied || 'Location denied')
    },
    { enableHighAccuracy: true, maximumAge: 2000, timeout: 15000 },
  )
}

async function ensureMic() {
  if (localStream) return localStream
  if (!navigator.mediaDevices?.getUserMedia) throw new Error('media')
  localStream = await navigator.mediaDevices.getUserMedia({
    audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true },
    video: false,
  })
  localStream.getAudioTracks().forEach((t) => {
    t.enabled = false
  })
  return localStream
}

function createPeerConnection(remoteId: string) {
  if (pcMap.has(remoteId)) return pcMap.get(remoteId)!
  const pc = new RTCPeerConnection({ iceServers: iceServers as RTCIceServer[] })
  pcMap.set(remoteId, pc)
  if (localStream) {
    localStream.getTracks().forEach((track) => pc.addTrack(track, localStream!))
  }
  pc.onicecandidate = (event) => {
    if (!event.candidate || !props.boot.rest?.signal) return
    api(restPath(props.boot.rest.signal), {
      method: 'POST',
      body: JSON.stringify({
        from: peerId,
        to: remoteId,
        type: 'ice',
        payload: event.candidate,
      }),
    }).catch(() => {})
  }
  pc.ontrack = (event) => {
    let audio = document.getElementById('snf-expo-remote-' + remoteId) as HTMLAudioElement | null
    if (!audio) {
      audio = document.createElement('audio')
      audio.id = 'snf-expo-remote-' + remoteId
      audio.autoplay = true
      ;(audio as any).playsInline = true
      audio.style.display = 'none'
      document.body.appendChild(audio)
    }
    audio.srcObject = event.streams[0]
  }
  pc.onconnectionstatechange = () => {
    if (pc.connectionState === 'failed' || pc.connectionState === 'closed') {
      pcMap.delete(remoteId)
    }
  }
  return pc
}

async function callPeer(remoteId: string) {
  await ensureMic()
  const pc = createPeerConnection(remoteId)
  if (pc.signalingState !== 'stable' || !props.boot.rest?.signal) return
  const offer = await pc.createOffer({ offerToReceiveAudio: true })
  await pc.setLocalDescription(offer)
  await api(restPath(props.boot.rest.signal), {
    method: 'POST',
    body: JSON.stringify({ from: peerId, to: remoteId, type: 'offer', payload: offer }),
  })
}

async function handleSignal(signal: any) {
  const remoteId = signal.from
  const type = signal.type
  const payload = signal.payload
  if (type === 'bye') {
    const pc = pcMap.get(remoteId)
    if (pc) {
      pc.close()
      pcMap.delete(remoteId)
    }
    return
  }
  await ensureMic()
  const pc = createPeerConnection(remoteId)
  if (type === 'offer') {
    await pc.setRemoteDescription(payload)
    const answer = await pc.createAnswer()
    await pc.setLocalDescription(answer)
    await api(restPath(props.boot.rest.signal), {
      method: 'POST',
      body: JSON.stringify({ from: peerId, to: remoteId, type: 'answer', payload: answer }),
    })
  } else if (type === 'answer') {
    if (!pc.currentRemoteDescription) await pc.setRemoteDescription(payload)
  } else if (type === 'ice' && payload) {
    try {
      await pc.addIceCandidate(payload)
    } catch {
      /* ignore late ice */
    }
  }
}

async function pollSignals() {
  if (!props.boot.canLive || !props.boot.rest?.signal) return
  try {
    const path =
      restPath(props.boot.rest.signal) +
      `?peer_id=${encodeURIComponent(peerId)}&after=${encodeURIComponent(String(lastSignalId))}`
    const data = await api<{ signals?: any[] }>(path)
    for (const signal of data.signals || []) {
      lastSignalId = Math.max(lastSignalId, signal.id || 0)
      await handleSignal(signal)
    }
  } catch {
    /* ignore */
  }
}

async function heartbeatPeer() {
  if (!props.boot.canLive || !props.boot.rest?.peers) return [] as string[]
  const list = await api<any[]>(restPath(props.boot.rest.peers), {
    method: 'POST',
    body: JSON.stringify({
      peer_id: peerId,
      name: props.boot.canBroadcast ? props.boot.leadRiderName : '',
    }),
  })
  const ids: string[] = []
  ;(Array.isArray(list) ? list : []).forEach((p) => {
    if (!p?.peer_id || p.peer_id === peerId) return
    peers.set(p.peer_id, p)
    ids.push(p.peer_id)
  })
  return ids
}

async function syncPeersAndOffers() {
  if (!props.boot.canLive) return
  try {
    const remoteIds = await heartbeatPeer()
    for (const remoteId of remoteIds) {
      if (!pcMap.has(remoteId) && peerId > remoteId) {
        await callPeer(remoteId)
      }
    }
  } catch {
    /* ignore */
  }
}

function setTalking(on: boolean) {
  talking.value = on
  if (localStream) {
    localStream.getAudioTracks().forEach((t) => {
      t.enabled = on
    })
  }
}

async function startTalk(event?: Event) {
  event?.preventDefault()
  if (!props.boot.canLive || talking.value) return
  try {
    await ensureMic()
    await syncPeersAndOffers()
    setTalking(true)
  } catch {
    window.alert(props.boot.i18n.micDenied || 'Mic denied')
  }
}

function stopTalk(event?: Event) {
  event?.preventDefault()
  if (!talking.value) return
  setTalking(false)
}

onMounted(() => {
  initMap()
  startPositionPoll()
  if (props.boot.canLive) {
    syncPeersAndOffers()
    peerTimer = setInterval(syncPeersAndOffers, 5000)
    signalTimer = setInterval(pollSignals, 2000)
  }
  requestAnimationFrame(() => map?.resize())
})

onBeforeUnmount(() => {
  if (positionTimer) clearInterval(positionTimer)
  if (peerTimer) clearInterval(peerTimer)
  if (signalTimer) clearInterval(signalTimer)
  if (geoWatchId != null) navigator.geolocation.clearWatch(geoWatchId)
  pcMap.forEach((pc) => pc.close())
  pcMap.clear()
  localStream?.getTracks().forEach((t) => t.stop())
  riderMarker?.remove()
  map?.remove()
  map = null
})
</script>

<template>
  <section id="snf-expo-lead-rider" class="snf-expo-map" aria-labelledby="snf-expo-lead-title">
    <div class="snf-expo-map__copy">
      <p class="snf-expo-map__eyebrow m-0">Live from the road</p>
      <h2 id="snf-expo-lead-title" class="snf-display m-0" style="margin-block-start: 0.35rem">
        Ride along with the lead rider
      </h2>
      <p class="m-0 text-white/70" style="margin-block-start: 0.55rem; max-width: 42rem">
        Follow the scenic route in real time and listen in with the walkie-talkie while the club is on the road.
      </p>
      <div
        v-if="routes.length"
        class="snf-expo-map__chips"
        role="group"
        aria-label="Choose a route"
        style="margin-block-start: 1rem"
      >
        <button
          v-for="route in routes"
          :id="`snf-expo-lead-rider--${route.id}`"
          :key="route.id"
          type="button"
          class="snf-expo-map__chip"
          :class="{ 'is-active': route.id === activeRouteId }"
          :aria-pressed="route.id === activeRouteId ? 'true' : 'false'"
          @click="selectRoute(route.id)"
        >
          {{ route.title }}
        </button>
      </div>
    </div>

    <div class="snf-expo-map__stage">
      <div
        ref="mapEl"
        class="snf-expo-map__canvas"
        role="img"
        aria-label="Lead rider route map"
      />
      <div v-if="fallback" class="snf-expo-map__fallback">
        <p class="m-0">{{ fallback }}</p>
      </div>

      <div class="snf-expo-map__panel">
        <div>
          <p class="snf-expo-map__name m-0">{{ boot.leadRiderName }}</p>
          <p class="snf-expo-map__status m-0" :data-status="statusKey">
            {{ statusKey === 'on_road' ? boot.i18n.onRoad : boot.i18n.standby }}
          </p>
        </div>

        <div v-if="boot.canLive" class="snf-expo-map__walkie">
          <button
            type="button"
            class="snf-expo-map__ptt"
            :class="{ 'is-talking': talking }"
            :aria-pressed="talking ? 'true' : 'false'"
            @mousedown="startTalk"
            @mouseup="stopTalk"
            @mouseleave="stopTalk"
            @touchstart.prevent="startTalk"
            @touchend.prevent="stopTalk"
            @touchcancel.prevent="stopTalk"
            @keydown.space.prevent="startTalk"
            @keyup.space.prevent="stopTalk"
          >
            {{ pttLabel }}
          </button>
          <p class="snf-expo-map__hint m-0">{{ boot.i18n.walkieHint }}</p>
          <button
            v-if="boot.canBroadcast"
            type="button"
            class="snf-expo-map__broadcast"
            :class="{ 'is-live': broadcasting }"
            @click="startBroadcast"
          >
            {{ broadcasting ? boot.i18n.onRoad : boot.i18n.broadcast }}
          </button>
        </div>
        <div v-else class="snf-expo-map__gate">
          <p class="m-0 text-white/70">{{ boot.i18n.membersOnly }}</p>
          <UiButton
            v-if="boot.applyUrl"
            :to="boot.applyUrl.replace(/^https?:\/\/[^/]+/, '') || '/apply'"
            size="sm"
            style="margin-block-start: 0.5rem"
          >
            Apply for membership
          </UiButton>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.snf-expo-map {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  margin-block-start: clamp(2.5rem, 5vw, 4rem);
}

.snf-expo-map__eyebrow {
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--snf-ink-soft, rgb(255 255 255 / 0.42));
}

.snf-expo-map__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
}

.snf-expo-map__chip {
  appearance: none;
  border: 0.0625rem solid var(--snf-surface-border, rgb(255 255 255 / 0.18));
  background: var(--snf-surface, rgb(27 19 19 / 0.42));
  color: var(--snf-ink-muted, rgb(255 255 255 / 0.72));
  margin: 0;
  font-size: 0.82rem;
  font-weight: 500;
  cursor: pointer;
  min-height: 2.5rem;
}

.snf-expo-map__chip.is-active,
.snf-expo-map__chip:hover,
.snf-expo-map__chip:focus-visible {
  background: rgb(243 235 232 / 0.16);
  border-color: rgb(243 235 232 / 0.34);
  color: #fff;
  outline: none;
}

.snf-expo-map__stage {
  position: relative;
  min-height: min(68vh, 40rem);
  overflow: hidden;
  border-block: 0.0625rem solid var(--snf-surface-border, rgb(255 255 255 / 0.18));
  background:
    radial-gradient(ellipse at 30% 20%, rgb(129 91 90 / 0.35), transparent 55%),
    linear-gradient(180deg, rgb(27 19 19 / 0.2), rgb(27 19 19 / 0.72));
}

.snf-expo-map__canvas {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.snf-expo-map__fallback {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: grid;
  place-items: center;
  margin: 0;
  background: rgb(27 19 19 / 0.72);
  color: var(--snf-ink-muted, rgb(255 255 255 / 0.72));
  text-align: center;
}

.snf-expo-map__panel {
  position: absolute;
  z-index: 3;
  left: max(1rem, var(--snf-space-x, 1rem));
  right: max(1rem, var(--snf-space-x, 1rem));
  bottom: 1.1rem;
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin: 0.85rem;
  background: var(--snf-surface-strong, rgb(27 19 19 / 0.72));
  border: 0.0625rem solid var(--snf-surface-border, rgb(255 255 255 / 0.18));
  backdrop-filter: blur(0.625rem);
}

.snf-expo-map__name {
  font-family: var(--snf-font-display, "League Gothic", sans-serif);
  font-size: 1.15rem;
  font-weight: 600;
}

.snf-expo-map__status {
  margin-block-start: 0.2rem;
  font-size: 0.82rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--snf-ink-soft, rgb(255 255 255 / 0.42));
}

.snf-expo-map__status[data-status='on_road'] {
  color: #b7f0c8;
}

.snf-expo-map__walkie,
.snf-expo-map__gate {
  display: grid;
  gap: 0.45rem;
  justify-items: end;
  max-width: 18rem;
}

.snf-expo-map__ptt {
  appearance: none;
  min-width: 11rem;
  min-height: 3.25rem;
  margin: 0;
  border: 0.0625rem solid rgb(243 235 232 / 0.28);
  background: rgb(243 235 232 / 0.14);
  color: #fff;
  font-size: 0.92rem;
  font-weight: 600;
  cursor: pointer;
  touch-action: none;
  user-select: none;
}

.snf-expo-map__ptt.is-talking {
  background: rgb(183 240 200 / 0.22);
  border-color: rgb(183 240 200 / 0.55);
}

.snf-expo-map__hint {
  font-size: 0.72rem;
  line-height: 1.4;
  color: var(--snf-ink-soft, rgb(255 255 255 / 0.42));
  text-align: right;
}

.snf-expo-map__broadcast {
  appearance: none;
  margin: 0;
  border: 0;
  background: transparent;
  color: var(--snf-ink-muted, rgb(255 255 255 / 0.72));
  font-size: 0.78rem;
  text-decoration: underline;
  cursor: pointer;
}

.snf-expo-map__broadcast.is-live {
  color: #b7f0c8;
  text-decoration: none;
}
</style>

<style>
.snf-expo-map__marker {
  display: grid;
  justify-items: center;
  gap: 0.25rem;
  transform: translateY(-0.25rem);
}

.snf-expo-map__marker-dot {
  width: 0.875rem;
  height: 0.875rem;
  border-radius: 50%;
  background: #f3ebe8;
  box-shadow:
    0 0 0 0.25rem rgb(129 91 90 / 0.55),
    0 0 1.125rem rgb(243 235 232 / 0.35);
}

.snf-expo-map__marker-label {
  margin: 0;
  border: 0.0625rem solid rgb(243 235 232 / 0.22);
  background: rgb(27 19 19 / 0.82);
  color: #fff;
  font-size: 0.68rem;
  font-weight: 600;
  white-space: nowrap;
}
</style>
