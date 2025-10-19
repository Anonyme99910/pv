<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="flex flex-col space-y-4 sm:space-y-0 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ t('analytics.title') }}</h1>
      </div>
      
      <!-- Time Range Filters -->
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-for="range in timeRanges"
          :key="range.value"
          @click="selectedRange = range.value"
          class="px-3 sm:px-4 py-2 rounded-lg font-medium transition-colors text-sm"
          :class="selectedRange === range.value 
            ? 'bg-primary text-white' 
            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
        >
          {{ t(range.label) }}
        </button>
      </div>
    </div>

    <!-- Export Buttons -->
    <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
      <button
        @click="exportPDF"
        class="btn-secondary flex items-center justify-center space-x-2 w-full sm:w-auto"
      >
        <FileText class="w-5 h-5" />
        <span>{{ t('analytics.export.pdf') }}</span>
      </button>
      <button
        @click="exportExcel"
        class="btn-accent flex items-center justify-center space-x-2 w-full sm:w-auto"
      >
        <Download class="w-5 h-5" />
        <span>{{ t('analytics.export.excel') }}</span>
      </button>
    </div>

    <!-- Key Insights -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      <div class="card p-6">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">
            {{ t('analytics.insights.avgImprovement') }}
          </h3>
          <TrendingUp class="w-5 h-5 text-green-600 dark:text-green-400" />
        </div>
        <p class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ data?.insights.avgImprovement }}%
        </p>
        <p class="text-sm text-green-600 dark:text-green-400 mt-1">Post-maintenance</p>
      </div>
      
      <div class="card p-6">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">
            {{ t('analytics.insights.criticalPanels') }}
          </h3>
          <AlertCircle class="w-5 h-5 text-red-600 dark:text-red-400" />
        </div>
        <p class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ data?.insights.criticalPanels }}
        </p>
        <p class="text-sm text-red-600 dark:text-red-400 mt-1">Require attention</p>
      </div>
      
      <div class="card p-6">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">
            {{ t('analytics.insights.maintenanceImpact') }}
          </h3>
          <Activity class="w-5 h-5 text-blue-600 dark:text-blue-400" />
        </div>
        <p class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ data?.insights.maintenanceImpact }}%
        </p>
        <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">Overall impact</p>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
      <!-- Efficiency Trend -->
      <div class="card p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 space-y-2 sm:space-y-0">
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">
            {{ t('analytics.charts.efficiencyTrend') }}
          </h3>
          <div class="flex items-center space-x-2 text-xs">
            <div class="flex items-center space-x-1">
              <div class="w-3 h-3 bg-red-500 rounded"></div>
              <span class="text-gray-600 dark:text-gray-400">{{ t('analytics.charts.before') }}</span>
            </div>
            <div class="flex items-center space-x-1">
              <div class="w-3 h-3 bg-green-500 rounded"></div>
              <span class="text-gray-600 dark:text-gray-400">{{ t('analytics.charts.after') }}</span>
            </div>
          </div>
        </div>
        <LineChart
          v-if="efficiencyData"
          :data="efficiencyData"
          :height="250"
        />
        <div v-else class="h-64 flex items-center justify-center">
          <Loader class="w-8 h-8 animate-spin text-primary" />
        </div>
      </div>

      <!-- Dust Accumulation -->
      <div class="card p-4 sm:p-6">
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-4">
          {{ t('analytics.charts.dustAccumulation') }}
        </h3>
        <LineChart
          v-if="dustData"
          :data="dustData"
          :height="250"
        />
        <div v-else class="h-64 flex items-center justify-center">
          <Loader class="w-8 h-8 animate-spin text-primary" />
        </div>
      </div>

      <!-- Temperature vs Output Correlation -->
      <div class="card p-4 sm:p-6">
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-4">
          {{ t('analytics.charts.tempVsOutput') }}
        </h3>
        <ScatterChart
          v-if="tempOutputData"
          :data="tempOutputData"
          :height="250"
        />
        <div v-else class="h-64 flex items-center justify-center">
          <Loader class="w-8 h-8 animate-spin text-primary" />
        </div>
      </div>

      <!-- Predicted RUL -->
      <div class="card p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 space-y-2 sm:space-y-0">
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">
            {{ t('analytics.charts.predictedRUL') }}
          </h3>
          <span
            class="text-xs text-gray-600 dark:text-gray-400 cursor-help"
            :title="t('tooltips.rul')"
          >
            ℹ️ {{ t('tooltips.rul') }}
          </span>
        </div>
        <BarChart
          v-if="rulData"
          :data="rulData"
          :height="250"
        />
        <div v-else class="h-64 flex items-center justify-center">
          <Loader class="w-8 h-8 animate-spin text-primary" />
        </div>
      </div>
    </div>

    <!-- Detailed Analysis Table -->
    <div class="card overflow-hidden">
      <div class="px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">
          Detailed Panel Analysis
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Panel ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Current Efficiency
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Predicted RUL
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Health Score
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Status
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="panel in samplePanels"
              :key="panel.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                {{ panel.id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                {{ panel.efficiency }}%
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                {{ panel.rul }} days
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                    <div
                      class="h-2 rounded-full"
                      :class="healthScoreColor(panel.healthScore)"
                      :style="{ width: panel.healthScore + '%' }"
                    ></div>
                  </div>
                  <span class="text-sm text-gray-900 dark:text-white">{{ panel.healthScore }}%</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="badge" :class="panelStatusClass(panel.status)">
                  {{ panel.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { 
  TrendingUp, 
  AlertCircle, 
  Activity, 
  FileText, 
  Download,
  Loader
} from 'lucide-vue-next'
import { mockAPI, useAutoRefresh } from '@/services/api'
import { useNotificationStore } from '@/stores/notifications'
import { exportToPDF, exportToExcel } from '@/utils/exportReports'
import LineChart from '@/components/LineChart.vue'
import ScatterChart from '@/components/ScatterChart.vue'
import BarChart from '@/components/BarChart.vue'

const { t, locale } = useI18n()
const notificationStore = useNotificationStore()

const selectedRange = ref('month')

const timeRanges = [
  { value: 'day', label: 'analytics.filters.day' },
  { value: 'week', label: 'analytics.filters.week' },
  { value: 'month', label: 'analytics.filters.month' },
  { value: 'year', label: 'analytics.filters.year' }
]

const { data, fetch } = useAutoRefresh(
  () => mockAPI.getAnalytics(selectedRange.value),
  30000
)

onMounted(() => {
  fetch() // Load data once, no auto-refresh
})

watch(selectedRange, () => {
  fetch()
})

const efficiencyData = computed(() => {
  if (!data.value?.efficiencyTrend) return null
  
  return {
    labels: data.value.efficiencyTrend.labels,
    datasets: [
      {
        label: t('analytics.charts.before'),
        data: data.value.efficiencyTrend.before,
        borderColor: '#EF4444',
        backgroundColor: 'transparent',
        tension: 0.4
      },
      {
        label: t('analytics.charts.after'),
        data: data.value.efficiencyTrend.after,
        borderColor: '#10B981',
        backgroundColor: 'transparent',
        tension: 0.4
      }
    ]
  }
})

const dustData = computed(() => {
  if (!data.value?.dustAccumulation) return null
  
  return {
    labels: data.value.dustAccumulation.labels,
    datasets: [
      {
        label: t('analytics.charts.dustLevel'),
        data: data.value.dustAccumulation.data,
        borderColor: '#FEC601',
        backgroundColor: 'rgba(254, 198, 1, 0.1)',
        tension: 0.4,
        fill: true
      }
    ]
  }
})

const tempOutputData = computed(() => {
  if (!data.value?.tempVsOutput) return null
  
  return {
    datasets: [
      {
        label: t('analytics.charts.tempVsOutput'),
        data: data.value.tempVsOutput.temperature.map((temp, i) => ({
          x: temp,
          y: data.value.tempVsOutput.output[i]
        })),
        backgroundColor: 'rgba(0, 76, 151, 0.6)',
        borderColor: '#004C97'
      }
    ]
  }
})

const rulData = computed(() => {
  if (!data.value?.predictedRUL) return null
  
  // Show only first 20 panels for readability
  const labels = data.value.predictedRUL.labels.slice(0, 20)
  const rulValues = data.value.predictedRUL.data.slice(0, 20)
  
  return {
    labels,
    datasets: [
      {
        label: t('analytics.charts.rul'),
        data: rulValues,
        backgroundColor: rulValues.map(val => {
          if (val < 365) return '#EF4444'
          if (val < 730) return '#FEC601'
          return '#10B981'
        }),
        borderColor: 'transparent'
      }
    ]
  }
})

const samplePanels = computed(() => {
  if (!data.value?.predictedRUL) return []
  
  return Array.from({ length: 10 }, (_, i) => ({
    id: data.value.predictedRUL.labels[i],
    efficiency: (85 + Math.random() * 10).toFixed(1),
    rul: data.value.predictedRUL.data[i],
    healthScore: Math.round(75 + Math.random() * 25),
    status: data.value.predictedRUL.data[i] < 365 ? 'Critical' : data.value.predictedRUL.data[i] < 730 ? 'Warning' : 'Healthy'
  }))
})

const healthScoreColor = (score) => {
  if (score >= 85) return 'bg-green-500'
  if (score >= 70) return 'bg-yellow-500'
  return 'bg-red-500'
}

const panelStatusClass = (status) => {
  const classes = {
    'Healthy': 'badge-success',
    'Warning': 'badge-warning',
    'Critical': 'badge-danger'
  }
  return classes[status] || 'badge-info'
}

const exportPDF = () => {
  try {
    notificationStore.showToast(t('analytics.export.generating'), 'info')
    
    // Prepare data for export
    const exportData = {
      kpis: {
        avgImprovement: data.value?.avgImprovement || '12.1%',
        criticalPanels: data.value?.criticalPanels || '7',
        maintenanceImpact: data.value?.maintenanceImpact || '85.4%',
        avgRUL: data.value?.avgRUL || '8.2 years'
      },
      performanceTable: samplePanels.value.slice(0, 5).map(panel => [
        panel.id,
        `${panel.efficiency}%`,
        `${Math.round(40 + Math.random() * 20)}°C`,
        `${Math.round(250 + Math.random() * 50)}W`,
        `${panel.healthScore}%`
      ]),
      efficiencyTrend: data.value?.efficiencyTrend,
      dustAccumulation: data.value?.dustAccumulation
    }
    
    exportToPDF(exportData, locale.value)
    
    setTimeout(() => {
      notificationStore.showToast(t('analytics.export.pdfSuccess'), 'success')
    }, 500)
  } catch (error) {
    console.error('PDF export error:', error)
    notificationStore.showToast(t('analytics.export.error'), 'error')
  }
}

const exportExcel = () => {
  try {
    notificationStore.showToast(t('analytics.export.generating'), 'info')
    
    // Prepare data for export
    const exportData = {
      kpis: {
        avgImprovement: data.value?.avgImprovement || '12.1%',
        criticalPanels: data.value?.criticalPanels || '7',
        maintenanceImpact: data.value?.maintenanceImpact || '85.4%',
        avgRUL: data.value?.avgRUL || '8.2 years'
      },
      performanceTable: samplePanels.value.slice(0, 5).map(panel => [
        panel.id,
        panel.efficiency,
        Math.round(40 + Math.random() * 20),
        Math.round(250 + Math.random() * 50),
        panel.healthScore
      ]),
      efficiencyTrend: data.value?.efficiencyTrend,
      dustAccumulation: data.value?.dustAccumulation
    }
    
    exportToExcel(exportData, locale.value)
    
    setTimeout(() => {
      notificationStore.showToast(t('analytics.export.excelSuccess'), 'success')
    }, 500)
  } catch (error) {
    console.error('Excel export error:', error)
    notificationStore.showToast(t('analytics.export.error'), 'error')
  }
}
</script>
