<template>
  <div v-if="items.length && center" class="cloud-map">
    <div class="controls">
      <input
        type="range"
        :min="0"
        :max="items.length - 1"
        v-model="index"
      />
      <div v-if="current" class="info">
        <p>{{ formattedTime }}</p>
        <p>{{ current.temp }} C</p>
        <p>{{ current.description }}</p>
        <p>Precipitation (1h): {{ current.precipMm }} mm</p>
      </div>
    </div>
    <div ref="mapEl" class="map"></div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import L from 'leaflet'

const props = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  center: {
    type: Array,
    default: null
  }
})

const index = ref(0)
const mapEl = ref(null)
let map = null
let marker = null
let precipCircle = null

watch(
  () => props.items.length,
  () => {
    if (index.value >= props.items.length) {
      index.value = 0
    }
  }
)

const current = computed(() => props.items[index.value])

const formattedTime = computed(() =>
  current.value
    ? new Date(current.value.time).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
      })
    : ''
)

const precipitationStyle = (amount) => {
  if (!amount || amount <= 0) {
    return { radius: 1500, color: '#0ea5e9', fillOpacity: 0 }
  }

  const clamped = Math.min(amount, 10)
  const radius = 1500 + clamped * 3000
  const opacity = Math.min(0.6, 0.2 + clamped * 0.04)
  return { radius, color: '#0ea5e9', fillOpacity: opacity }
}

const initMap = () => {
  if (!mapEl.value || !props.center) return
  map = L.map(mapEl.value, { zoomControl: true })
  map.setView(props.center, 7)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 12,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  marker = L.marker(props.center).addTo(map)

  const initial = precipitationStyle(current.value?.precipMm ?? 0)
  precipCircle = L.circle(props.center, {
    radius: initial.radius,
    color: initial.color,
    fillColor: initial.color,
    fillOpacity: initial.fillOpacity
  }).addTo(map)
}

onMounted(() => {
  if (props.center && props.items.length) {
    initMap()
  }
})

watch(
  () => props.center,
  (nextCenter) => {
    if (!nextCenter) return
    if (!map) {
      initMap()
      return
    }
    map.setView(nextCenter)
    if (marker) marker.setLatLng(nextCenter)
    if (precipCircle) precipCircle.setLatLng(nextCenter)
  }
)

watch(
  () => current.value && current.value.time,
  () => {
    if (!precipCircle || !current.value) return
    const next = precipitationStyle(current.value.precipMm)
    precipCircle.setRadius(next.radius)
    precipCircle.setStyle({
      color: next.color,
      fillColor: next.color,
      fillOpacity: next.fillOpacity
    })
  }
)

onBeforeUnmount(() => {
  if (map) {
    map.remove()
    map = null
  }
})
</script>

<style scoped>
.cloud-map {
  padding: 1rem;
}

.controls {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.info {
  display: grid;
  gap: 0.25rem;
}

.map {
  height: 320px;
  border-radius: 8px;
  overflow: hidden;
}
</style>
