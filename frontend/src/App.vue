<template>
  <div class="soft-font">
    <h1 style="text-align: center;">Weather Info</h1>
    <div style="margin: 16px 0; text-align: center;">
      <input v-model="city" placeholder="Enter city" @keyup.enter="fetchWeather" style="padding: 12px 16px; font-size: 1.1em; width: 200px; border: 1px solid #ccc; border-radius: 4px;" />
      <button @click="fetchWeather" style="padding: 12px 24px; font-size: 1.2em; margin: 0 8px;">Search</button>
      <button @click="fetchGeoWeather" style="padding: 12px 24px; font-size: 1.2em; margin: 0 8px;">Use My Location</button>
    </div>
    <div v-if="weather" style="display: flex; flex-direction: row; justify-content: center; gap: 32px; width: 100%; margin-top: 32px;">
      <div style="max-width: 400px; width: 100%; text-align: center;">
        <h2>{{ weather.name }}</h2>
        <div>
          <p style="font-size: 3em; margin: 10px 0;">{{ getWeatherEmoji(weather.weather[0].main) }}</p>
          <p>{{ Math.round(weather.main.temp) }} C</p>
          <p>{{ weather.weather[0].description }}</p>
          <div>
            <p>Feels Like: {{ Math.round(weather.main.feels_like) }} C</p>
            <p>Humidity: {{ weather.main.humidity }}%</p>
            <p>Wind: {{ weather.wind?.speed || 0 }} m/s</p>
            <p>Pressure: {{ weather.main.pressure }} hPa</p>
          </div>
        </div>
      </div>
    </div>
    <div v-if="hourlySteps.length" class="hourly-strip">
      <div v-for="(hour, index) in hourlySteps" :key="index" class="hour-card">
        <div class="hour-time">{{ hour.time }}</div>
        <div class="hour-temp">{{ hour.temp }} C</div>
        <div class="hour-desc">{{ hour.description }}</div>
        <div class="hour-precip">Precip: {{ hour.precipMm }} mm</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const city = ref('')
const weather = ref(null)
const forecast = ref([])
const showInstallButton = ref(false)
const isInstalled = ref(false)
let deferredPrompt = null

function getWeatherEmoji(condition) {
  const conditionLower = condition.toLowerCase()
  if (conditionLower.includes('clear') || conditionLower.includes('sunny')) return 'Clear'
  if (conditionLower.includes('cloud')) return 'Clouds'
  if (conditionLower.includes('rain') || conditionLower.includes('drizzle')) return 'Rain'
  if (conditionLower.includes('thunder') || conditionLower.includes('storm')) return 'Thunder'
  if (conditionLower.includes('snow')) return 'Snow'
  if (conditionLower.includes('mist') || conditionLower.includes('fog')) return 'Fog'
  if (conditionLower.includes('wind')) return 'Wind'
  return 'Unknown'
}


// Check if app is already installed
const checkIfInstalled = () => {
  // Check if running in standalone mode (iOS)
  if (window.matchMedia('(display-mode: standalone)').matches) {
    isInstalled.value = true
    return
  }
  // Check if running in standalone mode (Android)
  if (window.navigator.standalone === true) {
    isInstalled.value = true
    return
  }
  // Check if installed via beforeinstallprompt
  if (localStorage.getItem('pwa-installed') === 'true') {
    isInstalled.value = true
  }
}

// Handle install prompt
onMounted(() => {
  checkIfInstalled()
  
  // Listen for beforeinstallprompt event
  window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing
    e.preventDefault()
    // Stash the event so it can be triggered later
    deferredPrompt = e
    // Show install button
    showInstallButton.value = true
  })
  
  // Listen for app installed event
  window.addEventListener('appinstalled', () => {
    isInstalled.value = true
    showInstallButton.value = false
    deferredPrompt = null
    localStorage.setItem('pwa-installed', 'true')
  })
  
  // Handle URL parameters for shortcuts
  const urlParams = new URLSearchParams(window.location.search)
  const action = urlParams.get('action')
  if (action === 'location') {
    fetchGeoWeather()
  } else if (action === 'search') {
    // Focus on search input
    setTimeout(() => {
      const input = document.querySelector('input[placeholder="Enter city"]')
      if (input) input.focus()
    }, 100)
  } else {
    // Auto-fetch user's location on first visit
    fetchGeoWeather()
  }
})

const installApp = async () => {
  if (!deferredPrompt) {
    // Fallback for iOS
    alert('To install this app on your iOS device, tap the Share button and then "Add to Home Screen"')
    return
  }
  
  // Show the install prompt
  deferredPrompt.prompt()
  
  // Wait for the user to respond to the prompt
  const { outcome } = await deferredPrompt.userChoice
  
  if (outcome === 'accepted') {
    console.log('User accepted the install prompt')
    isInstalled.value = true
    localStorage.setItem('pwa-installed', 'true')
  } else {
    console.log('User dismissed the install prompt')
  }
  
  // Clear the deferredPrompt
  deferredPrompt = null
  showInstallButton.value = false
}

const normalizeOneCall = (data) => {
  if (!data || !Array.isArray(data.hourly)) return []
  return data.hourly.slice(0, 24).map((entry) => ({
    time: entry.dt * 1000,
    temp: Math.round(entry.temp ?? 0),
    clouds: entry.clouds ?? 0,
    precipMm: Math.round((entry.rain?.['1h'] ?? entry.snow?.['1h'] ?? 0) * 10) / 10,
    description: entry.weather?.[0]?.description ?? '',
    icon: entry.weather?.[0]?.icon ?? ''
  }))
}

const fetchOneCall = async (lat, lon) => {
  try {
    const params = new URLSearchParams({ type: 'onecall', lat: String(lat), lon: String(lon) })
    const response = await fetch(`/weather?${params.toString()}`)
    const data = await response.json()
    forecast.value = normalizeOneCall(data)
  } catch (error) {
    forecast.value = []
    console.error(error)
  }
}

const fetchWeather = async () => {
  if (!city.value) return
  try {
    const params = new URLSearchParams({ city: city.value })
    const response = await fetch(`/weather?${params.toString()}`)
    weather.value = await response.json()
    if (weather.value?.coord) {
      await fetchOneCall(weather.value.coord.lat, weather.value.coord.lon)
    }
  } catch (error) {
    alert("Error fetching weather data")
    console.error(error)
  }
}

const fetchGeoWeather = () => {
  if (!navigator.geolocation) {
    alert("Geolocation is not supported by your browser")
    return
  }

  navigator.geolocation.getCurrentPosition(async (position) => {
    const { latitude, longitude } = position.coords
    try {
      const params = new URLSearchParams({ lat: String(latitude), lon: String(longitude) })
      const response = await fetch(`/weather?${params.toString()}`)
      weather.value = await response.json()
      await fetchOneCall(latitude, longitude)
    } catch (error) {
      alert("Error fetching weather data")
      console.error(error)
    }
  }, (err) => {
    alert("Unable to retrieve your location")
    console.error(err)
  })
}

const hourlySteps = computed(() =>
  forecast.value.map((entry) => ({
    time: new Date(entry.time).toLocaleTimeString([], {
      hour: '2-digit',
      minute: '2-digit'
    }),
    temp: entry.temp,
    description: entry.description,
    precipMm: entry.precipMm
  }))
)
</script>

<style>
.soft-font {
  font-family: 'Segoe UI', 'Quicksand', 'Nunito', 'Arial Rounded MT Bold', 'Arial', sans-serif;
  font-weight: 400;
  letter-spacing: 0.01em;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.hourly-strip {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  padding: 16px 8px;
  margin: 24px auto 0;
  max-width: 900px;
}

.hour-card {
  min-width: 140px;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  text-align: center;
  background: #fafafa;
}

.hour-time {
  font-weight: 600;
  margin-bottom: 6px;
}

.hour-temp {
  font-size: 1.2em;
  margin-bottom: 4px;
}

.hour-desc,
.hour-precip {
  font-size: 0.9em;
  color: #555;
}
</style>
