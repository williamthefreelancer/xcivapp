export function initExpoMarketplace() {
const cfg = window.snfExpo || null;
  if (!cfg) return;

  const mapEl = document.getElementById("snf-expo-map");
  const fallback = document.getElementById("snf-expo-map-fallback");
  const statusEl = document.getElementById("snf-expo-status");
  const pttBtn = document.getElementById("snf-expo-ptt");
  const broadcastBtn = document.getElementById("snf-expo-broadcast");
  const chips = Array.from(document.querySelectorAll(".snf-expo__chip"));

  const routes = Array.isArray(cfg.routes) ? cfg.routes : [];
  let activeRouteId = cfg.defaultRouteId || (routes[0] && routes[0].id) || "";
  let map = null;
  let riderMarker = null;
  let geoWatchId = null;
  let broadcasting = false;

  const peerId =
    "snf-" +
    Math.random().toString(36).slice(2, 10) +
    "-" +
    Date.now().toString(36);
  const peers = new Map();
  const pcMap = new Map();
  let localStream = null;
  let talking = false;
  let lastSignalId = 0;
  let iceServers = [{ urls: "stun:stun.l.google.com:19302" }];

  function headers() {
    return {
      "Content-Type": "application/json",
      "X-WP-Nonce": cfg.rest && cfg.rest.nonce ? cfg.rest.nonce : "",
    };
  }

  function findRoute(id) {
    return routes.find((r) => r.id === id) || routes[0] || null;
  }

  function setStatus(key, live) {
    if (!statusEl) return;
    const onRoad = key === "on_road" || live;
    statusEl.dataset.status = onRoad ? "on_road" : "standby";
    statusEl.textContent = onRoad
      ? cfg.i18n.onRoad
      : cfg.i18n.standby;
  }

  function showFallback(message) {
    if (!fallback) return;
    fallback.hidden = false;
    const text = fallback.querySelector(".snf-expo__fallback-text");
    if (text) text.textContent = message;
  }

  function routeLineGeoJSON(route) {
    return {
      type: "Feature",
      properties: {},
      geometry: {
        type: "LineString",
        coordinates: route.coordinates || [],
      },
    };
  }

  function drawRoute(route) {
    if (!map || !route) return;
    const sourceId = "snf-route";
    const data = routeLineGeoJSON(route);

    if (map.getSource(sourceId)) {
      map.getSource(sourceId).setData(data);
    } else {
      map.addSource(sourceId, { type: "geojson", data });
      map.addLayer({
        id: "snf-route-line",
        type: "line",
        source: sourceId,
        layout: { "line-join": "round", "line-cap": "round" },
        paint: {
          "line-color": "#f3ebe8",
          "line-width": 3.5,
          "line-opacity": 0.85,
        },
      });
    }

    map.easeTo({
      center: route.center,
      zoom: route.zoom || 6,
      duration: 700,
    });
  }

  function ensureRiderMarker(lngLat) {
    if (!map || typeof mapboxgl === "undefined") return;
    if (!riderMarker) {
      const el = document.createElement("div");
      el.className = "snf-expo__marker";
      el.innerHTML =
        '<span class="snf-expo__marker-dot"></span><span class="snf-expo__marker-label"></span>';
      const label = el.querySelector(".snf-expo__marker-label");
      if (label) label.textContent = cfg.leadRiderName || "William A. Johnson";
      riderMarker = new mapboxgl.Marker({ element: el, anchor: "bottom" }).setLngLat(
        lngLat
      );
      riderMarker.addTo(map);
    } else {
      riderMarker.setLngLat(lngLat);
    }
  }

  function initMap() {
    if (!mapEl) return;
    if (!cfg.mapboxToken || typeof mapboxgl === "undefined") {
      showFallback(cfg.i18n.noToken);
      return;
    }

    mapboxgl.accessToken = cfg.mapboxToken;
    const route = findRoute(activeRouteId);
    const center = route && route.center ? route.center : [-77.5, 38.2];

    map = new mapboxgl.Map({
      container: mapEl,
      style: "mapbox://styles/mapbox/dark-v11",
      center,
      zoom: (route && route.zoom) || 5.4,
      attributionControl: true,
    });

    map.addControl(new mapboxgl.NavigationControl({ visualizePitch: false }), "top-right");

    map.on("load", () => {
      drawRoute(route);
      if (route && route.coordinates && route.coordinates[0]) {
        ensureRiderMarker(route.coordinates[0]);
      }
    });
  }

  function selectRoute(id) {
    activeRouteId = id;
    chips.forEach((chip) => {
      chip.classList.toggle("is-active", chip.dataset.routeId === id);
    });
    drawRoute(findRoute(id));
  }

  chips.forEach((chip) => {
    chip.addEventListener("click", () => {
      selectRoute(chip.dataset.routeId);
    });
  });
  if (chips.length) {
    const initial =
      chips.find((c) => c.dataset.routeId === activeRouteId) || chips[0];
    if (initial) initial.classList.add("is-active");
  }

  async function fetchPosition() {
    if (!cfg.canLive || !cfg.rest || !cfg.rest.position) return;
    try {
      const res = await fetch(cfg.rest.position, {
        headers: headers(),
        credentials: "same-origin",
      });
      if (!res.ok) return;
      const data = await res.json();
      if (data && data.lat != null && data.lng != null) {
        ensureRiderMarker([data.lng, data.lat]);
        setStatus(data.status || "on_road", true);
      } else {
        setStatus("standby", false);
      }
    } catch (_err) {
      /* ignore poll errors */
    }
  }

  function startPositionPoll() {
    if (!cfg.canLive) return;
    fetchPosition();
    window.setInterval(fetchPosition, cfg.pollMs || 4000);
  }

  async function postPosition(coords) {
    if (!cfg.canBroadcast || !cfg.rest || !cfg.rest.position) return;
    try {
      await fetch(cfg.rest.position, {
        method: "POST",
        headers: headers(),
        credentials: "same-origin",
        body: JSON.stringify({
          lat: coords.latitude,
          lng: coords.longitude,
          heading: coords.heading || null,
          status: "on_road",
        }),
      });
      ensureRiderMarker([coords.longitude, coords.latitude]);
      setStatus("on_road", true);
    } catch (_err) {
      /* ignore */
    }
  }

  function startBroadcast() {
    if (!cfg.canBroadcast || !navigator.geolocation) return;
    if (broadcasting) return;
    broadcasting = true;
    if (broadcastBtn) {
      broadcastBtn.classList.add("is-live");
      broadcastBtn.textContent = cfg.i18n.onRoad;
    }
    geoWatchId = navigator.geolocation.watchPosition(
      (pos) => {
        postPosition(pos.coords);
      },
      () => {
        if (broadcastBtn) {
          broadcastBtn.classList.remove("is-live");
          broadcastBtn.textContent =
            "Broadcast my position as lead rider";
        }
        broadcasting = false;
        window.alert(cfg.i18n.geoDenied);
      },
      { enableHighAccuracy: true, maximumAge: 2000, timeout: 15000 }
    );
  }

  if (broadcastBtn) {
    broadcastBtn.addEventListener("click", startBroadcast);
  }

  /* ——— Walkie / WebRTC PTT ——— */

  async function ensureMic() {
    if (localStream) return localStream;
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      throw new Error("media");
    }
    localStream = await navigator.mediaDevices.getUserMedia({
      audio: {
        echoCancellation: true,
        noiseSuppression: true,
        autoGainControl: true,
      },
      video: false,
    });
    localStream.getAudioTracks().forEach((t) => {
      t.enabled = false;
    });
    return localStream;
  }

  async function restPost(url, body) {
    const res = await fetch(url, {
      method: "POST",
      headers: headers(),
      credentials: "same-origin",
      body: JSON.stringify(body),
    });
    if (!res.ok) throw new Error("rest");
    return res.json();
  }

  async function restGet(url) {
    const res = await fetch(url, {
      headers: headers(),
      credentials: "same-origin",
    });
    if (!res.ok) throw new Error("rest");
    return res.json();
  }

  async function heartbeatPeer() {
    if (!cfg.canLive || !cfg.rest || !cfg.rest.peers) return [];
    const list = await restPost(cfg.rest.peers, {
      peer_id: peerId,
      name: cfg.canBroadcast ? cfg.leadRiderName : "",
    });
    const ids = [];
    (Array.isArray(list) ? list : []).forEach((p) => {
      if (!p || !p.peer_id || p.peer_id === peerId) return;
      peers.set(p.peer_id, p);
      ids.push(p.peer_id);
    });
    return ids;
  }

  function createPeerConnection(remoteId) {
    if (pcMap.has(remoteId)) return pcMap.get(remoteId);
    const pc = new RTCPeerConnection({ iceServers });
    pcMap.set(remoteId, pc);

    if (localStream) {
      localStream.getTracks().forEach((track) => {
        pc.addTrack(track, localStream);
      });
    }

    pc.onicecandidate = (event) => {
      if (!event.candidate) return;
      restPost(cfg.rest.signal, {
        from: peerId,
        to: remoteId,
        type: "ice",
        payload: event.candidate,
      }).catch(() => {});
    };

    pc.ontrack = (event) => {
      let audio = document.getElementById("snf-expo-remote-" + remoteId);
      if (!audio) {
        audio = document.createElement("audio");
        audio.id = "snf-expo-remote-" + remoteId;
        audio.autoplay = true;
        audio.playsInline = true;
        audio.style.display = "none";
        document.body.appendChild(audio);
      }
      audio.srcObject = event.streams[0];
    };

    pc.onconnectionstatechange = () => {
      if (pc.connectionState === "failed" || pc.connectionState === "closed") {
        pcMap.delete(remoteId);
      }
    };

    return pc;
  }

  async function callPeer(remoteId) {
    await ensureMic();
    const pc = createPeerConnection(remoteId);
    if (pc.signalingState !== "stable") return;
    const offer = await pc.createOffer({ offerToReceiveAudio: true });
    await pc.setLocalDescription(offer);
    await restPost(cfg.rest.signal, {
      from: peerId,
      to: remoteId,
      type: "offer",
      payload: offer,
    });
  }

  async function handleSignal(signal) {
    const remoteId = signal.from;
    const type = signal.type;
    const payload = signal.payload;

    if (type === "bye") {
      const pc = pcMap.get(remoteId);
      if (pc) {
        pc.close();
        pcMap.delete(remoteId);
      }
      return;
    }

    await ensureMic();
    const pc = createPeerConnection(remoteId);

    if (type === "offer") {
      await pc.setRemoteDescription(payload);
      const answer = await pc.createAnswer();
      await pc.setLocalDescription(answer);
      await restPost(cfg.rest.signal, {
        from: peerId,
        to: remoteId,
        type: "answer",
        payload: answer,
      });
    } else if (type === "answer") {
      if (!pc.currentRemoteDescription) {
        await pc.setRemoteDescription(payload);
      }
    } else if (type === "ice" && payload) {
      try {
        await pc.addIceCandidate(payload);
      } catch (_err) {
        /* ignore late ice */
      }
    }
  }

  async function pollSignals() {
    if (!cfg.canLive || !cfg.rest || !cfg.rest.signal) return;
    try {
      const url =
        cfg.rest.signal +
        "?peer_id=" +
        encodeURIComponent(peerId) +
        "&after=" +
        encodeURIComponent(String(lastSignalId));
      const data = await restGet(url);
      const signals = (data && data.signals) || [];
      for (const signal of signals) {
        lastSignalId = Math.max(lastSignalId, signal.id || 0);
        await handleSignal(signal);
      }
    } catch (_err) {
      /* ignore */
    }
  }

  async function syncPeersAndOffers() {
    if (!cfg.canLive) return;
    try {
      const remoteIds = await heartbeatPeer();
      for (const remoteId of remoteIds) {
        if (!pcMap.has(remoteId) && peerId > remoteId) {
          await callPeer(remoteId);
        }
      }
    } catch (_err) {
      /* ignore */
    }
  }

  function setTalking(on) {
    talking = on;
    if (localStream) {
      localStream.getAudioTracks().forEach((t) => {
        t.enabled = on;
      });
    }
    if (pttBtn) {
      pttBtn.classList.toggle("is-talking", on);
      pttBtn.setAttribute("aria-pressed", on ? "true" : "false");
      const label = pttBtn.querySelector(".snf-expo__ptt-label");
      if (label) {
        label.textContent = on ? cfg.i18n.release : cfg.i18n.holdToTalk;
      }
    }
  }

  async function startTalk(event) {
    if (event) event.preventDefault();
    if (!cfg.canLive || talking) return;
    try {
      await ensureMic();
      await syncPeersAndOffers();
      setTalking(true);
    } catch (_err) {
      window.alert(cfg.i18n.micDenied);
    }
  }

  function stopTalk(event) {
    if (event) event.preventDefault();
    if (!talking) return;
    setTalking(false);
  }

  function bindPtt() {
    if (!pttBtn || !cfg.canLive) return;
    pttBtn.addEventListener("mousedown", startTalk);
    pttBtn.addEventListener("mouseup", stopTalk);
    pttBtn.addEventListener("mouseleave", stopTalk);
    pttBtn.addEventListener("touchstart", startTalk, { passive: false });
    pttBtn.addEventListener("touchend", stopTalk);
    pttBtn.addEventListener("touchcancel", stopTalk);
    pttBtn.addEventListener("keydown", (e) => {
      if (e.code === "Space" || e.key === " ") {
        e.preventDefault();
        startTalk();
      }
    });
    pttBtn.addEventListener("keyup", (e) => {
      if (e.code === "Space" || e.key === " ") {
        e.preventDefault();
        stopTalk();
      }
    });
  }

  /* Drive cards → map */
  document.querySelectorAll('[data-listing="scenic-drives"] .snf-dashboard__card').forEach((card) => {
    card.addEventListener("click", (e) => {
      const href = card.getAttribute("href") || "";
      const match = href.match(/#snf-expo-lead-rider--([a-z0-9-]+)/i);
      if (match) {
        e.preventDefault();
        selectRoute(match[1]);
        const stage = document.getElementById("snf-expo-lead-rider");
        if (stage) stage.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    });
  });

  /* Boot */
  initMap();
  startPositionPoll();
  bindPtt();

  if (cfg.canLive) {
    syncPeersAndOffers();
    window.setInterval(syncPeersAndOffers, 5000);
    window.setInterval(pollSignals, 2000);
  }

  const root = document.querySelector(".snf-expo");
  if (root) {
    requestAnimationFrame(() => root.classList.add("is-ready"));
  }
}
