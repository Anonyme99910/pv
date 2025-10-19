<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ t('faultDetection.title') }}</h1>
    </div>

    <!-- Filters and Search -->
    <div class="card p-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Search -->
        <div class="flex-1 max-w-md">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="t('faultDetection.search')"
              class="input-field pl-10"
            />
          </div>
        </div>

        <!-- Filter Buttons -->
        <div class="flex items-center space-x-2">
          <button
            v-for="filter in filters"
            :key="filter.value"
            @click="activeFilter = filter.value"
            class="px-4 py-2 rounded-lg font-medium transition-colors"
            :class="activeFilter === filter.value 
              ? 'bg-primary text-white' 
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
          >
            {{ t(filter.label) }}
          </button>
        </div>
      </div>
    </div>

    <!-- Faults Table -->
    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('panelId')">
                {{ t('faultDetection.table.panelId') }}
                <ChevronDown v-if="sortField === 'panelId'" class="inline w-4 h-4" />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('faultType')">
                {{ t('faultDetection.table.faultType') }}
                <ChevronDown v-if="sortField === 'faultType'" class="inline w-4 h-4" />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('confidence')">
                {{ t('faultDetection.table.confidence') }}
                <ChevronDown v-if="sortField === 'confidence'" class="inline w-4 h-4" />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('detectedOn')">
                {{ t('faultDetection.table.detectedOn') }}
                <ChevronDown v-if="sortField === 'detectedOn'" class="inline w-4 h-4" />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ t('faultDetection.table.status') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ t('faultDetection.table.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="fault in paginatedFaults"
              :key="fault.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
              @click="viewFaultDetails(fault)"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                {{ fault.panelId }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                {{ t(`faultDetection.faultTypes.${fault.faultType}`) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <div class="flex items-center">
                  <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                    <div
                      class="h-2 rounded-full"
                      :class="confidenceColor(fault.confidence)"
                      :style="{ width: fault.confidence + '%' }"
                    ></div>
                  </div>
                  <span class="text-gray-900 dark:text-white">{{ fault.confidence }}%</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                {{ formatDate(fault.detectedOn) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="badge" :class="statusBadgeClass(fault.status)">
                  {{ t(`faultDetection.status.${fault.status}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button
                  @click.stop="viewFaultDetails(fault)"
                  class="text-primary hover:text-primary-600 font-medium"
                >
                  {{ t('common.view') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
          Showing {{ (currentPage - 1) * pageSize + 1 }} to {{ Math.min(currentPage * pageSize, filteredFaults.length) }} of {{ filteredFaults.length }} results
        </div>
        <div class="flex items-center space-x-2">
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            Previous
          </button>
          <button
            v-for="page in totalPages"
            :key="page"
            @click="currentPage = page"
            class="px-3 py-1 rounded border transition-colors"
            :class="currentPage === page 
              ? 'bg-primary text-white border-primary' 
              : 'border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700'"
          >
            {{ page }}
          </button>
          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Fault Details Modal -->
    <FaultDetailsModal
      v-if="selectedFault"
      :fault="selectedFault"
      @close="selectedFault = null"
      @resolve="resolveFault"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Search, ChevronDown } from 'lucide-vue-next'
import { format } from 'date-fns'
import { enUS, fr } from 'date-fns/locale'
import { mockAPI, useAutoRefresh } from '@/services/api'
import { useNotificationStore } from '@/stores/notifications'
import FaultDetailsModal from '@/components/FaultDetailsModal.vue'

const { t, locale } = useI18n()
const notificationStore = useNotificationStore()

const searchQuery = ref('')
const activeFilter = ref('all')
const sortField = ref('detectedOn')
const sortDirection = ref('desc')
const currentPage = ref(1)
const pageSize = ref(10)
const selectedFault = ref(null)

const filters = [
  { value: 'all', label: 'faultDetection.filters.all' },
  { value: 'active', label: 'faultDetection.filters.active' },
  { value: 'resolved', label: 'faultDetection.filters.resolved' },
  { value: 'critical', label: 'faultDetection.filters.critical' }
]

const { data: faults, fetch } = useAutoRefresh(
  () => mockAPI.getFaults(activeFilter.value),
  10000
)

onMounted(() => {
  fetch() // Load data once, no auto-refresh
})

// Reset to first page when filter or search changes
watch([activeFilter, searchQuery], () => {
  currentPage.value = 1
})

const filteredFaults = computed(() => {
  let result = faults.value || []
  
  // Status filter (All, Active, Resolved, Critical)
  if (activeFilter.value !== 'all') {
    result = result.filter(f => {
      if (activeFilter.value === 'active') {
        return f.status === 'active' || f.status === 'investigating'
      } else if (activeFilter.value === 'resolved') {
        return f.status === 'resolved'
      } else if (activeFilter.value === 'critical') {
        return f.status === 'critical'
      }
      return true
    })
  }
  
  // Search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(f => 
      f.panelId.toLowerCase().includes(query) ||
      f.faultType.toLowerCase().includes(query)
    )
  }
  
  // Sort
  result.sort((a, b) => {
    let aVal = a[sortField.value]
    let bVal = b[sortField.value]
    
    if (sortField.value === 'detectedOn') {
      aVal = new Date(aVal).getTime()
      bVal = new Date(bVal).getTime()
    } else if (sortField.value === 'confidence') {
      aVal = parseFloat(aVal)
      bVal = parseFloat(bVal)
    }
    
    if (sortDirection.value === 'asc') {
      return aVal > bVal ? 1 : -1
    } else {
      return aVal < bVal ? 1 : -1
    }
  })
  
  return result
})

const totalPages = computed(() => Math.ceil(filteredFaults.value.length / pageSize.value))

const paginatedFaults = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredFaults.value.slice(start, end)
})

const sortBy = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'desc'
  }
}

const formatDate = (dateString) => {
  const localeMap = { en: enUS, fr }
  return format(new Date(dateString), 'MMM dd, yyyy HH:mm', { locale: localeMap[locale.value] })
}

const confidenceColor = (confidence) => {
  if (confidence >= 90) return 'bg-green-500'
  if (confidence >= 75) return 'bg-yellow-500'
  return 'bg-red-500'
}

const statusBadgeClass = (status) => {
  const classes = {
    active: 'badge-warning',
    resolved: 'badge-success',
    critical: 'badge-danger',
    investigating: 'badge-info'
  }
  return classes[status] || 'badge-info'
}

const viewFaultDetails = async (fault) => {
  const details = await mockAPI.getFaultDetails(fault.id)
  selectedFault.value = details
}

const resolveFault = (faultId) => {
  notificationStore.showToast(
    t('notifications.maintenanceCompleted', { panelId: selectedFault.value.panelId }),
    'success'
  )
  selectedFault.value = null
  fetch() // Reload data once
}
</script>
