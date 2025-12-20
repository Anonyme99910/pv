<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Fault Detection</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">AI-powered fault monitoring and analysis</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
          {{ faults.length }} Faults
        </span>
      </div>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-for="filter in filters"
          :key="filter.value"
          @click="activeFilter = filter.value"
          class="px-4 py-2 rounded-lg font-medium text-sm transition-colors"
          :class="activeFilter === filter.value 
            ? 'bg-primary text-white' 
            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
        >
          {{ filter.label }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="card p-8 text-center">
      <div class="animate-spin w-8 h-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-4"></div>
      <p class="text-gray-500 dark:text-gray-400">Loading faults...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="card p-8 text-center">
      <div class="text-red-500 mb-4">
        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <p class="text-gray-900 dark:text-white font-medium mb-2">Failed to load faults</p>
      <p class="text-gray-500 dark:text-gray-400 mb-4">{{ error }}</p>
      <button @click="loadFaults" class="btn-primary">Try Again</button>
    </div>

    <!-- Faults List -->
    <div v-else class="space-y-4">
      <!-- Empty State -->
      <div v-if="filteredFaults.length === 0" class="card p-8 text-center">
        <div class="text-gray-400 mb-4">
          <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-gray-900 dark:text-white font-medium">No faults found</p>
        <p class="text-gray-500 dark:text-gray-400">All systems are operating normally</p>
      </div>

      <!-- Fault Cards -->
      <div v-else class="grid gap-4">
        <div
          v-for="fault in filteredFaults"
          :key="fault.id"
          class="card p-4 hover:shadow-lg transition-shadow cursor-pointer"
          @click="selectedFault = fault"
        >
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
              <div 
                class="w-10 h-10 rounded-full flex items-center justify-center"
                :class="getSeverityColor(fault.severity)"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <div>
                <p class="font-semibold text-gray-900 dark:text-white">{{ fault.panelId }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ fault.faultType }}</p>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <div class="text-right">
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ fault.confidence }}%</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Confidence</p>
              </div>
              <span 
                class="px-2 py-1 rounded-full text-xs font-medium"
                :class="getStatusColor(fault.status)"
              >
                {{ fault.status }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fault Detail Modal -->
    <div v-if="selectedFault" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="selectedFault = null">
      <div class="bg-white dark:bg-gray-800 rounded-xl max-w-lg w-full p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Fault Details</h3>
          <button @click="selectedFault = null" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Panel ID</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ selectedFault.panelId }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Fault Type</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ selectedFault.faultType }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Confidence</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ selectedFault.confidence }}%</p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
              <span 
                class="px-2 py-1 rounded-full text-xs font-medium"
                :class="getStatusColor(selectedFault.status)"
              >
                {{ selectedFault.status }}
              </span>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Severity</p>
              <p class="font-medium text-gray-900 dark:text-white capitalize">{{ selectedFault.severity }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Detected</p>
              <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(selectedFault.detectedOn) }}</p>
            </div>
          </div>
        </div>
        <div class="mt-6 flex justify-end">
          <button @click="selectedFault = null" class="btn-secondary">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '@/services/api'

const faults = ref([])
const loading = ref(true)
const error = ref(null)
const activeFilter = ref('all')
const selectedFault = ref(null)

const filters = [
  { value: 'all', label: 'All' },
  { value: 'active', label: 'Active' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'critical', label: 'Critical' }
]

const filteredFaults = computed(() => {
  if (activeFilter.value === 'all') return faults.value
  if (activeFilter.value === 'active') {
    return faults.value.filter(f => f.status === 'active' || f.status === 'investigating')
  }
  return faults.value.filter(f => f.status === activeFilter.value)
})

const loadFaults = async () => {
  loading.value = true
  error.value = null
  try {
    const result = await api.getFaults('all')
    faults.value = Array.isArray(result) ? result : []
  } catch (e) {
    console.error('Error loading faults:', e)
    error.value = e.message || 'Failed to load faults'
  } finally {
    loading.value = false
  }
}

const getSeverityColor = (severity) => {
  const colors = {
    critical: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    high: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    medium: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
    low: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
  }
  return colors[severity] || colors.medium
}

const getStatusColor = (status) => {
  const colors = {
    active: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    investigating: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    resolved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    critical: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
  }
  return colors[status] || colors.active
}

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A'
  try {
    return new Date(dateStr).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return dateStr
  }
}

onMounted(() => {
  loadFaults()
})
</script>
