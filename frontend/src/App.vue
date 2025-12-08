<template>
  <div class="soft-font">
    <h1 style="text-align: center;">Weather PWA</h1>
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
          <p>{{ Math.round(weather.main.temp) }}°C</p>
          <p>{{ weather.weather[0].description }}</p>
          <div>
            <p>Feels Like: {{ Math.round(weather.main.feels_like) }}°C</p>
            <p>Humidity: {{ weather.main.humidity }}%</p>
            <p>Wind: {{ weather.wind?.speed || 0 }} m/s</p>
            <p>Pressure: {{ weather.main.pressure }} hPa</p>
          </div>
        </div>
      </div>
      <div style="max-width: 400px; width: 100%; text-align: center;">
        <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
          <h3 style="margin: 0;">Recommended Clothing</h3>
          <button v-if="clothingRecommendations.length > 0" @click="toggleAllInfo"
            style="width: 40px; height: 40px; border-radius: 8px; background: #e0e0e0; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; margin-left: 4px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="12" fill="#2196f3"/>
              <text x="12" y="16" text-anchor="middle" fill="#fff" font-size="14" font-family="Arial" font-weight="bold">i</text>
            </svg>
          </button>
        </div>
        <div v-if="clothingRecommendations.length > 0" style="display: flex; flex-direction: row; align-items: flex-start; position: relative; margin-left: 40px;">
          <div style="flex: 1;">
            <div v-for="(item, index) in clothingRecommendations" :key="index" style="margin-bottom: 8px; display: flex; flex-direction: row; align-items: flex-start; position: relative; min-height: 3.5em;">
              <span style="flex:1; text-align:left; padding-top: 4px;">{{ item.name }}</span>
              <span style="font-size:2em; margin: 0 12px; flex-shrink:0; padding-top: 2px;">{{ item.emoji }}</span>
              <span :style="{
                flex: 2,
                fontSize: '0.95em',
                color: '#555',
                minHeight: '3.2em',
                display: 'flex',
                flexDirection: 'column',
                opacity: showAllInfo ? 1 : 0,
                transition: 'opacity 0.2s',
                textAlign: 'left',
                marginLeft: '8px',
              }">{{ item.reason }}</span>
            </div>
          </div>
        </div>
        <div v-else>
          <p>No recommendations available</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const city = ref('')
const weather = ref(null)
const showInstallButton = ref(false)
const isInstalled = ref(false)
let deferredPrompt = null

const showAllInfo = ref(false)
function toggleAllInfo() {
  showAllInfo.value = !showAllInfo.value
}

function getWeatherEmoji(condition) {
  const conditionLower = condition.toLowerCase()
  if (conditionLower.includes('clear') || conditionLower.includes('sunny')) return '☀️'
  if (conditionLower.includes('cloud')) return '☁️'
  if (conditionLower.includes('rain') || conditionLower.includes('drizzle')) return '🌧️'
  if (conditionLower.includes('thunder') || conditionLower.includes('storm')) return '⛈️'
  if (conditionLower.includes('snow')) return '❄️'
  if (conditionLower.includes('mist') || conditionLower.includes('fog')) return '🌫️'
  if (conditionLower.includes('wind')) return '💨'
  return '🌤️'
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

const fetchWeather = async () => {
  if (!city.value) return
  try {
    const response = await fetch(`/weather?city=${city.value}`)
    weather.value = await response.json()
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
      const response = await fetch(`/weather?lat=${latitude}&lon=${longitude}`)
      weather.value = await response.json()
    } catch (error) {
      alert("Error fetching weather data")
      console.error(error)
    }
  }, (err) => {
    alert("Unable to retrieve your location")
    console.error(err)
  })
}

// Clothing recommendations based on weather
const clothingRecommendations = computed(() => {
  if (!weather.value) return []
  
  const temp = weather.value.main.temp
  const feelsLike = weather.value.main.feels_like
  const condition = weather.value.weather[0].main.toLowerCase()
  const description = weather.value.weather[0].description.toLowerCase()
  const windSpeed = weather.value.wind?.speed || 0
  const isRaining = description.includes('rain') || condition === 'rain' || condition === 'drizzle'
  const isSnowing = description.includes('snow') || condition === 'snow'
  const isSunny = description.includes('sun') || condition === 'clear'
  
  const recommendations = []
  
  // 1. UNDER LAYERS (Base layer - t-shirt or thermal)
  if (temp <= 5) {
    recommendations.push({
      emoji: '🥶',
      name: 'Thermal Underwear',
      reason: 'Essential base layer for very cold weather',
      category: 'under'
    })
  } else if (temp <= 15) {
    recommendations.push({
      emoji: '👕',
      name: 'Long-Sleeved T-Shirt',
      reason: 'Good base layer for cool weather',
      category: 'under'
    })
  } else {
    recommendations.push({
      emoji: '👕',
      name: 'T-Shirt',
      reason: 'Light and comfortable base layer',
      category: 'under'
    })
  }
  
  // 2. JUMPER/SWEATER (Mid layer - light or heavy)
  if (temp <= 0) {
    recommendations.push({
      emoji: '🧶',
      name: 'Heavy Wool Jumper',
      reason: 'Maximum warmth for freezing conditions',
      category: 'jumper'
    })
  } else if (temp <= 10) {
    recommendations.push({
      emoji: '🧶',
      name: 'Heavy Jumper or Sweater',
      reason: 'Warm mid layer for cold weather',
      category: 'jumper'
    })
  } else if (temp <= 18) {
    recommendations.push({
      emoji: '🧥',
      name: 'Light Jumper or Cardigan',
      reason: 'Comfortable layering for mild weather',
      category: 'jumper'
    })
  } else if (temp <= 22) {
    recommendations.push({
      emoji: '👔',
      name: 'Light Sweater (Optional)',
      reason: 'May need light layer in the evening',
      category: 'jumper'
    })
  }
  // No jumper needed above 22°C
  
  // 3. OUTER LAYER (Jacket/Coat)
  if (temp <= 0) {
    recommendations.push({
      emoji: '🧥',
      name: 'Heavy Winter Coat',
      reason: 'Maximum insulation for freezing temperatures',
      category: 'outer'
    })
  } else if (temp <= 10) {
    recommendations.push({
      emoji: '🧥',
      name: 'Warm Winter Jacket',
      reason: 'Essential for cold weather protection',
      category: 'outer'
    })
  } else if (temp <= 15) {
    recommendations.push({
      emoji: '🧥',
      name: 'Light Jacket',
      reason: 'Comfortable outer layer for cool weather',
      category: 'outer'
    })
  }
  
  // Weather-specific outer layers
  if (isRaining) {
    recommendations.push({
      emoji: '🧥',
      name: 'Rain Jacket',
      reason: 'Waterproof protection from rain',
      category: 'outer'
    })
  }
  
  if (windSpeed > 7 && temp <= 20) {
    recommendations.push({
      emoji: '🧥',
      name: 'Windbreaker',
      reason: 'Protection from strong winds',
      category: 'outer'
    })
  }
  
  // 4. HEADWEAR
  if (temp <= 5) {
    recommendations.push({
      emoji: '🧢',
      name: 'Winter Beanie or Wool Hat',
      reason: 'Keep your head warm in cold weather',
      category: 'headwear'
    })
  } else if (temp <= 15 && (windSpeed > 5 || isRaining)) {
    recommendations.push({
      emoji: '🧢',
      name: 'Beanie or Cap',
      reason: 'Protect from wind and rain',
      category: 'headwear'
    })
  } else if (isSunny && temp > 20) {
    recommendations.push({
      emoji: '🧢',
      name: 'Cap or Sun Hat',
      reason: 'Protect from sun and UV rays',
      category: 'headwear'
    })
  }
  
  // 5. SCARF
  if (temp <= 5) {
    recommendations.push({
      emoji: '🧣',
      name: 'Warm Scarf',
      reason: 'Essential for protecting neck and face from cold',
      category: 'scarf'
    })
  } else if (temp <= 12 && (windSpeed > 5 || feelsLike < temp - 3)) {
    recommendations.push({
      emoji: '🧣',
      name: 'Scarf',
      reason: 'Wind makes it feel colder, scarf helps',
      category: 'scarf'
    })
  }
  
  // 5.5. TROUSERS/BOTTOMS
  if (temp <= 0) {
    recommendations.push({
      emoji: '🩳',
      name: 'Thermal Leggings or Thick Pants',
      reason: 'Essential insulation for freezing temperatures',
      category: 'trousers'
    })
  } else if (temp <= 10) {
    recommendations.push({
      emoji: '👖',
      name: 'Long Pants or Jeans',
      reason: 'Warm protection for cold weather',
      category: 'trousers'
    })
  } else if (temp <= 18) {
    recommendations.push({
      emoji: '👖',
      name: 'Long Pants',
      reason: 'Comfortable for mild weather',
      category: 'trousers'
    })
  } else {
    recommendations.push({
      emoji: '🩳',
      name: 'Shorts or Light Pants',
      reason: 'Breathable for warm weather',
      category: 'trousers'
    })
  }
  
  // 6. FOOTWEAR/SHOEWARE
  if (isSnowing || temp <= 0) {
    recommendations.push({
      emoji: '👢',
      name: 'Insulated Winter Boots',
      reason: 'Warm and waterproof for snow and ice',
      category: 'footwear'
    })
  } else if (isRaining || (temp <= 10 && description.includes('wet'))) {
    recommendations.push({
      emoji: '👢',
      name: 'Waterproof Boots',
      reason: 'Keep feet dry in wet conditions',
      category: 'footwear'
    })
  } else if (temp <= 10) {
    recommendations.push({
      emoji: '👟',
      name: 'Closed-Toe Shoes or Boots',
      reason: 'Warm and comfortable for cold weather',
      category: 'footwear'
    })
  } else if (temp <= 20) {
    recommendations.push({
      emoji: '👟',
      name: 'Sneakers or Comfortable Shoes',
      reason: 'Suitable for mild weather',
      category: 'footwear'
    })
  } else {
    recommendations.push({
      emoji: '👟',
      name: 'Light Shoes or Sandals',
      reason: 'Comfortable for warm weather',
      category: 'footwear'
    })
  }
  
  // 7. ACCESSORIES
  if (isRaining) {
    recommendations.push({
      emoji: '☂️',
      name: 'Umbrella',
      reason: 'Essential protection from rain',
      category: 'accessory'
    })
  }
  
  if (isSunny && temp > 20) {
    recommendations.push({
      emoji: '🕶️',
      name: 'Sunglasses',
      reason: 'Protect your eyes from UV rays',
      category: 'accessory'
    })
  }
  
  if (temp <= 5 || isSnowing) {
    recommendations.push({
      emoji: '🧤',
      name: 'Gloves or Mittens',
      reason: 'Essential for cold and snowy conditions',
      category: 'accessory'
    })
  } else if (temp <= 10 && windSpeed > 5) {
    recommendations.push({
      emoji: '🧤',
      name: 'Light Gloves',
      reason: 'Wind makes hands feel colder',
      category: 'accessory'
    })
  }
  
  // Remove duplicates by name and return all recommendations
  const unique = []
  const seen = new Set()
  for (const item of recommendations) {
    if (!seen.has(item.name)) {
      seen.add(item.name)
      unique.push(item)
    }
  }
  
  return unique
})
</script>

<style>
.soft-font {
  font-family: 'Segoe UI', 'Quicksand', 'Nunito', 'Arial Rounded MT Bold', 'Arial', sans-serif;
  font-weight: 400;
  letter-spacing: 0.01em;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>