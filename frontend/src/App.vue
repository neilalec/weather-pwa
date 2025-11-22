<template>
    <div class="min-h-screen bg-gray-100 p-4 flex flex-col items-center">
    <h1 class="text-3xl font-bold text-blue-600 mb-6">SkyScope Weather</h1>
    <div class="w-full max-w-md bg-white p-4 rounded-2xl shadow-md">
    <input
    v-model="city"
    placeholder="Enter city"
    class="w-full p-3 border border-gray-300 rounded-xl mb-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
    />
    <button
    @click="fetchWeather"
    class="w-full bg-blue-600 text-white p-3 rounded-xl font-semibold hover:bg-blue-700 transition"
    >
    Search
    </button>
    <button
    @click="fetchGeoWeather"
    class="w-full bg-green-500 text-white p-3 rounded-xl font-semibold hover:bg-green-600 transition mt-2"
    >
    Use My Location
    </button>

    </div>
    
    
    <div v-if="weather" class="w-full max-w-md mt-6 bg-white p-6 rounded-2xl shadow-md text-center">
    <h2 class="text-2xl font-semibold mb-2">{{ weather.name }}</h2>
    <p class="text-4xl font-bold mb-2">{{ weather.main.temp }}°C</p>
    <p class="text-gray-600 text-lg capitalize">{{ weather.weather[0].description }}</p>
    </div>
    </div>
</template>
    
    
<script setup>
import { ref } from 'vue'


const city = ref('')
const weather = ref(null)


const fetchWeather = async () => {
    if (!city.value) return
    const response = await fetch(`/weather?city=${city.value}`)
    weather.value = await response.json()
}

const fetchGeoWeather = () => {
  if (!navigator.geolocation) {
    alert("Geolocation is not supported by your browser")
    return
  }

  navigator.geolocation.getCurrentPosition(async (position) => {
    const { latitude, longitude } = position.coords
    const response = await fetch(`/weather?lat=${latitude}&lon=${longitude}`)
    weather.value = await response.json()
  }, (err) => {
    alert("Unable to retrieve your location")
    console.error(err)
  })
}

</script>


<style>
</style>