<template>
  <div class="weather-widget">
    <div class="weather-header">
      <div class="flex items-center gap-2">
        <Cloud class="w-5 h-5 text-blue-400" />
        <span class="font-semibold text-white">Weather & Solar Impact</span>
      </div>
      <button @click="showLocationSearch = !showLocationSearch" class="location-btn">
        <MapPin class="w-4 h-4" />
        <span>{{ currentLocation }}</span>
      </button>
    </div>

    <!-- Location Search -->
    <Transition name="slide">
      <div v-if="showLocationSearch" class="location-search">
        <div class="search-input-wrapper">
          <Search class="w-4 h-4 text-gray-400" />
          <input
            v-model="searchQuery"
            @input="searchLocations"
            placeholder="Search location..."
            class="search-input"
          />
        </div>
        <div v-if="searchResults.length > 0" class="search-results">
          <button
            v-for="location in searchResults"
            :key="location.id"
            @click="selectLocation(location)"
            class="search-result-item"
          >
            <MapPin class="w-4 h-4" />
            <span>{{ location.name }}, {{ location.country }}</span>
          </button>
        </div>
        <button @click="detectLocation" class="detect-btn">
          <Navigation class="w-4 h-4" />
          <span>Detect my location</span>
        </button>
      </div>
    </Transition>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <Loader2 class="w-8 h-8 text-amber-500 animate-spin" />
      <span class="text-gray-400 text-sm mt-2">Loading weather data...</span>
    </div>

    <!-- Weather Data -->
    <div v-else-if="weather" class="weather-content">
      <!-- Current Weather -->
      <div class="current-weather">
        <div class="weather-main">
          <img :src="weather.current.condition_icon" :alt="weather.current.condition" class="weather-icon" />
          <div class="temp-display">
            <span class="temp-value">{{ Math.round(weather.current.temp_c) }}</span>
            <span class="temp-unit">°C</span>
          </div>
        </div>
        <div class="weather-details">
          <p class="condition">{{ weather.current.condition }}</p>
          <p class="feels-like">Feels like {{ Math.round(weather.current.feelslike_c) }}°C</p>
        </div>
      </div>

      <!-- Weather Stats -->
      <div class="weather-stats">
        <div class="stat">
          <Wind class="w-4 h-4 text-blue-400" />
          <span class="stat-value">{{ weather.current.wind_kph }} km/h</span>
          <span class="stat-label">Wind</span>
        </div>
        <div class="stat">
          <Droplets class="w-4 h-4 text-cyan-400" />
          <span class="stat-value">{{ weather.current.humidity }}%</span>
          <span class="stat-label">Humidity</span>
        </div>
        <div class="stat">
          <Sun class="w-4 h-4 text-amber-400" />
          <span class="stat-value">{{ weather.current.uv }}</span>
          <span class="stat-label">UV Index</span>
        </div>
        <div class="stat">
          <CloudRain class="w-4 h-4 text-gray-400" />
          <span class="stat-value">{{ weather.current.cloud }}%</span>
          <span class="stat-label">Cloud</span>
        </div>
      </div>

      <!-- Solar Impact -->
      <div v-if="weather.solar_impact" class="solar-impact">
        <div class="impact-header">
          <Zap class="w-5 h-5 text-amber-500" />
          <span class="font-semibold">Solar Generation Impact</span>
        </div>
        <div class="impact-meter">
          <div class="meter-bar">
            <div 
              class="meter-fill"
              :style="{ width: weather.solar_impact.efficiency_percent + '%' }"
              :class="getEfficiencyClass(weather.solar_impact.efficiency_percent)"
            ></div>
          </div>
          <div class="meter-labels">
            <span>0%</span>
            <span class="efficiency-value">{{ weather.solar_impact.efficiency_percent }}%</span>
            <span>100%</span>
          </div>
        </div>
        <div class="impact-rating" :class="getRatingClass(weather.solar_impact.rating)">
          {{ weather.solar_impact.rating }}
        </div>
        <p class="impact-recommendation">{{ weather.solar_impact.recommendation }}</p>
      </div>

      <!-- Forecast -->
      <div v-if="forecast.length > 0" class="forecast">
        <div class="forecast-header">
          <Calendar class="w-4 h-4" />
          <span>3-Day Forecast</span>
        </div>
        <div class="forecast-days">
          <div v-for="day in forecast" :key="day.date" class="forecast-day">
            <span class="day-name">{{ formatDay(day.date) }}</span>
            <img :src="day.condition_icon" :alt="day.condition" class="day-icon" />
            <div class="day-temps">
              <span class="temp-high">{{ Math.round(day.maxtemp_c) }}°</span>
              <span class="temp-low">{{ Math.round(day.mintemp_c) }}°</span>
            </div>
            <div class="day-efficiency">
              <Zap class="w-3 h-3" />
              <span>{{ day.expected_efficiency }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <AlertCircle class="w-8 h-8 text-red-400" />
      <p class="text-gray-400 text-sm mt-2">{{ error }}</p>
      <button @click="loadWeather" class="retry-btn">Retry</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { 
  Cloud, MapPin, Search, Navigation, Loader2, Wind, Droplets, 
  Sun, CloudRain, Zap, Calendar, AlertCircle 
} from 'lucide-vue-next'
import { api } from '@/services/api'

const weather = ref(null)
const forecast = ref([])
const loading = ref(true)
const error = ref(null)
const currentLocation = ref('Loading...')
const showLocationSearch = ref(false)
const searchQuery = ref('')
const searchResults = ref([])

let searchTimeout = null

const loadWeather = async (location = 'auto:ip') => {
  loading.value = true
  error.value = null

  try {
    const response = await api.getWeather(location)
    
    if (response.success) {
      weather.value = response
      currentLocation.value = response.location?.name || 'Unknown'
      
      // Load forecast
      const forecastResponse = await api.getWeatherForecast(location)
      if (forecastResponse.success) {
        forecast.value = forecastResponse.forecast || []
      }
    } else {
      error.value = response.message || 'Failed to load weather data'
    }
  } catch (err) {
    error.value = 'Failed to connect to weather service'
    console.error('Weather error:', err)
  } finally {
    loading.value = false
  }
}

const detectLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const { latitude, longitude } = position.coords
        await loadWeather(`${latitude},${longitude}`)
        showLocationSearch.value = false
      },
      (err) => {
        console.error('Geolocation error:', err)
        error.value = 'Could not detect location'
      }
    )
  } else {
    error.value = 'Geolocation not supported'
  }
}

const searchLocations = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }

  searchTimeout = setTimeout(async () => {
    try {
      const response = await api.searchWeatherLocations(searchQuery.value)
      if (response.success) {
        searchResults.value = response.locations || []
      }
    } catch (err) {
      console.error('Search error:', err)
    }
  }, 300)
}

const selectLocation = async (location) => {
  searchQuery.value = ''
  searchResults.value = []
  showLocationSearch.value = false
  await loadWeather(`${location.lat},${location.lon}`)
}

const formatDay = (dateStr) => {
  const date = new Date(dateStr)
  const today = new Date()
  const tomorrow = new Date(today)
  tomorrow.setDate(tomorrow.getDate() + 1)

  if (date.toDateString() === today.toDateString()) return 'Today'
  if (date.toDateString() === tomorrow.toDateString()) return 'Tomorrow'
  return date.toLocaleDateString('en-US', { weekday: 'short' })
}

const getEfficiencyClass = (efficiency) => {
  if (efficiency >= 80) return 'excellent'
  if (efficiency >= 60) return 'good'
  if (efficiency >= 40) return 'moderate'
  return 'poor'
}

const getRatingClass = (rating) => {
  const ratingLower = rating?.toLowerCase() || ''
  if (ratingLower.includes('excellent')) return 'rating-excellent'
  if (ratingLower.includes('good')) return 'rating-good'
  if (ratingLower.includes('moderate')) return 'rating-moderate'
  return 'rating-poor'
}

onMounted(() => {
  loadWeather()
})
</script>

<style scoped>
.weather-widget {
  background: #1f2937;
  border-radius: 12px;
  padding: 12px;
  border: 1px solid #374151;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  overflow: hidden;
  box-sizing: border-box;
}

@media (max-width: 400px) {
  .weather-widget {
    padding: 8px;
  }
}

.weather-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  flex-wrap: wrap;
  gap: 8px;
}

@media (max-width: 400px) {
  .weather-header {
    flex-direction: column;
    align-items: flex-start;
  }
}

.location-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 10px;
  background: #374151;
  border: 1px solid #4b5563;
  border-radius: 6px;
  color: #d1d5db;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.location-btn:hover {
  border-color: #f59e0b;
}

.location-search {
  background: #111827;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 16px;
}

.search-input-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #374151;
  border-radius: 6px;
  padding: 8px 12px;
}

.search-input {
  flex: 1;
  background: transparent;
  border: none;
  color: white;
  font-size: 14px;
  outline: none;
}

.search-results {
  margin-top: 8px;
  max-height: 150px;
  overflow-y: auto;
}

.search-result-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px;
  background: transparent;
  border: none;
  color: #d1d5db;
  font-size: 13px;
  cursor: pointer;
  border-radius: 4px;
  text-align: left;
}

.search-result-item:hover {
  background: #374151;
}

.detect-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  padding: 10px;
  margin-top: 8px;
  background: #f59e0b;
  border: none;
  border-radius: 6px;
  color: white;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.detect-btn:hover {
  background: #d97706;
}

.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}

.retry-btn {
  margin-top: 12px;
  padding: 8px 16px;
  background: #374151;
  border: 1px solid #4b5563;
  border-radius: 6px;
  color: #d1d5db;
  font-size: 13px;
  cursor: pointer;
}

.current-weather {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: linear-gradient(135deg, #1e3a5f, #1e293b);
  border-radius: 10px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

@media (max-width: 400px) {
  .current-weather {
    padding: 8px;
    gap: 6px;
  }
}

.weather-main {
  display: flex;
  align-items: center;
  gap: 8px;
}

.weather-icon {
  width: 48px;
  height: 48px;
}

@media (max-width: 400px) {
  .weather-icon {
    width: 36px;
    height: 36px;
  }
}

.temp-display {
  display: flex;
  align-items: flex-start;
}

.temp-value {
  font-size: 36px;
  font-weight: 700;
  color: white;
  line-height: 1;
}

@media (max-width: 400px) {
  .temp-value {
    font-size: 28px;
  }
}

.temp-unit {
  font-size: 20px;
  color: #9ca3af;
  margin-top: 8px;
}

.weather-details {
  flex: 1;
}

.condition {
  font-size: 16px;
  font-weight: 500;
  color: white;
}

.feels-like {
  font-size: 13px;
  color: #9ca3af;
}

.weather-stats {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 6px;
  margin-bottom: 12px;
}

@media (min-width: 400px) {
  .weather-stats {
    grid-template-columns: repeat(4, 1fr);
  }
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 10px;
  background: #111827;
  border-radius: 8px;
}

.stat-value {
  font-size: 14px;
  font-weight: 600;
  color: white;
  margin-top: 4px;
}

.stat-label {
  font-size: 10px;
  color: #6b7280;
  margin-top: 2px;
}

.solar-impact {
  background: #111827;
  border-radius: 10px;
  padding: 16px;
  margin-bottom: 16px;
}

.impact-header {
  display: flex;
  align-items: center;
  gap: 8px;
  color: white;
  margin-bottom: 12px;
}

.impact-meter {
  margin-bottom: 12px;
}

.meter-bar {
  height: 8px;
  background: #374151;
  border-radius: 4px;
  overflow: hidden;
}

.meter-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.5s ease;
}

.meter-fill.excellent {
  background: linear-gradient(90deg, #10b981, #34d399);
}

.meter-fill.good {
  background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.meter-fill.moderate {
  background: linear-gradient(90deg, #f97316, #fb923c);
}

.meter-fill.poor {
  background: linear-gradient(90deg, #ef4444, #f87171);
}

.meter-labels {
  display: flex;
  justify-content: space-between;
  font-size: 10px;
  color: #6b7280;
  margin-top: 4px;
}

.efficiency-value {
  font-weight: 600;
  color: #f59e0b;
}

.impact-rating {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 8px;
}

.rating-excellent {
  background: rgba(16, 185, 129, 0.2);
  color: #10b981;
}

.rating-good {
  background: rgba(245, 158, 11, 0.2);
  color: #f59e0b;
}

.rating-moderate {
  background: rgba(249, 115, 22, 0.2);
  color: #f97316;
}

.rating-poor {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
}

.impact-recommendation {
  font-size: 12px;
  color: #9ca3af;
  line-height: 1.5;
}

.forecast {
  background: #111827;
  border-radius: 10px;
  padding: 12px;
}

.forecast-header {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #9ca3af;
  font-size: 12px;
  margin-bottom: 12px;
}

.forecast-days {
  display: flex;
  justify-content: space-between;
}

.forecast-day {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  flex: 1;
}

.day-name {
  font-size: 11px;
  color: #9ca3af;
}

.day-icon {
  width: 36px;
  height: 36px;
}

.day-temps {
  display: flex;
  gap: 4px;
  font-size: 12px;
}

.temp-high {
  color: white;
  font-weight: 600;
}

.temp-low {
  color: #6b7280;
}

.day-efficiency {
  display: flex;
  align-items: center;
  gap: 2px;
  font-size: 10px;
  color: #f59e0b;
}

/* Transitions */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
