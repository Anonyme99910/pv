<template>
  <div class="dashboard-container">
    <!-- Header -->
    <div>
      <h1 class="text-lg sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ t('dashboard.title') }}</h1>
      <p class="text-xs sm:text-sm text-text-light dark:text-gray-400 mt-1 truncate">{{ t('dashboard.welcome') }}</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 gap-2 sm:gap-4 lg:grid-cols-4">
      <KPICard
        :title="t('dashboard.kpi.totalPanels')"
        :value="data?.kpis.activePanels + '/' + data?.kpis.totalPanels"
        :icon="Layers"
        color="blue"
        :loading="loading"
      />
      <KPICard
        :title="t('dashboard.kpi.predictedFaults')"
        :value="data?.kpis.predictedFaults"
        :icon="AlertTriangle"
        color="red"
        :loading="loading"
        :tooltip="t('tooltips.predictedFaults')"
      />
      <KPICard
        :title="t('dashboard.kpi.avgEfficiency')"
        :value="data?.kpis.avgEfficiency + '%'"
        :icon="TrendingUp"
        color="green"
        :loading="loading"
        :tooltip="t('tooltips.efficiency')"
      />
      <KPICard
        :title="t('dashboard.kpi.nextMaintenance')"
        :value="formatDate(data?.kpis.nextMaintenance)"
        :icon="Calendar"
        color="purple"
        :loading="loading"
      />
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 gap-3 sm:gap-6 lg:grid-cols-2">
      <!-- Power Output Chart -->
      <div class="card p-2 sm:p-4">
        <h3 class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white mb-2">
          {{ t('dashboard.charts.powerOutput') }}
        </h3>
        <div class="h-40 sm:h-64">
          <LineChart
            v-if="data?.powerOutput"
            :data="powerOutputData"
            :height="150"
          />
          <div v-else class="h-full flex items-center justify-center">
            <Loader class="w-6 h-6 animate-spin text-primary" />
          </div>
        </div>
      </div>

      <!-- System Trends Chart -->
      <div class="card p-2 sm:p-4">
        <h3 class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white mb-2">
          {{ t('dashboard.charts.trends') }}
        </h3>
        <div class="h-40 sm:h-64">
          <LineChart
            v-if="data?.trends"
            :data="trendsData"
            :height="150"
          />
          <div v-else class="h-full flex items-center justify-center">
            <Loader class="w-6 h-6 animate-spin text-primary" />
          </div>
        </div>
      </div>
    </div>

    <!-- Weather Widget -->
    <div class="grid grid-cols-1 gap-3 sm:gap-6">
      <WeatherWidget />
    </div>

    <!-- System Map and Alerts -->
    <div class="grid grid-cols-1 gap-3 sm:gap-6 lg:grid-cols-3">
      <!-- Panel Grid -->
      <div class="lg:col-span-2 card p-2 sm:p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
          <h3 class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white">
            {{ t('dashboard.systemMap.title') }}
          </h3>
          <div class="flex items-center gap-2 sm:gap-4 text-[10px] sm:text-xs flex-wrap">
            <div class="flex items-center gap-1">
              <div class="w-2 h-2 bg-green-500 rounded-full"></div>
              <span class="text-gray-600 dark:text-gray-400">OK</span>
            </div>
            <div class="flex items-center gap-1">
              <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
              <span class="text-gray-600 dark:text-gray-400">Warn</span>
            </div>
            <div class="flex items-center gap-1">
              <div class="w-2 h-2 bg-red-500 rounded-full"></div>
              <span class="text-gray-600 dark:text-gray-400">Fault</span>
            </div>
          </div>
        </div>
        <PanelGrid :panels="data?.panelGrid || []" />
      </div>

      <!-- Alerts Feed -->
      <div class="card p-2 sm:p-4">
        <div class="flex items-center justify-between mb-2 sm:mb-4">
          <h3 class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white">
            {{ t('dashboard.alerts.title') }}
          </h3>
          <button class="text-[10px] sm:text-xs text-primary hover:underline">
            {{ t('dashboard.alerts.viewAll') }}
          </button>
        </div>
        <div class="space-y-2">
          <div
            v-for="alert in data?.alerts || []"
            :key="alert.id"
            class="p-2 rounded-lg border transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
            :class="alertClass(alert.type)"
          >
            <div class="flex items-start gap-2">
              <component
                :is="alertIcon(alert.type)"
                class="w-4 h-4 flex-shrink-0 mt-0.5"
                :class="alertIconClass(alert.type)"
              />
              <div class="flex-1 min-w-0">
                <p class="text-[10px] sm:text-xs text-gray-900 dark:text-white line-clamp-2">{{ alert.message }}</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                  {{ formatTime(alert.timestamp) }}
                </p>
              </div>
            </div>
          </div>
          <div v-if="!data?.alerts?.length" class="text-center py-4 text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">
            {{ t('dashboard.alerts.noAlerts') }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { 
  Layers, 
  AlertTriangle, 
  TrendingUp, 
  Calendar,
  Loader,
  Info,
  AlertCircle
} from 'lucide-vue-next'
import { format } from 'date-fns'
import { enUS, fr } from 'date-fns/locale'
import { formatDistanceToNow } from 'date-fns'
import KPICard from '@/components/KPICard.vue'
import LineChart from '@/components/LineChart.vue'
import PanelGrid from '@/components/PanelGrid.vue'
import WeatherWidget from '@/components/WeatherWidget.vue'
import { api, useAutoRefresh } from '@/services/api'

const { t, locale } = useI18n()

const { data, loading, fetch } = useAutoRefresh(
  api.getLiveData,
  5000
)

onMounted(() => {
  fetch() // Load data once, no auto-refresh
})

const powerOutputData = computed(() => {
  if (!data.value?.powerOutput) return null
  
  return {
    labels: data.value.powerOutput.labels,
    datasets: [
      {
        label: 'Power (kW)',
        data: data.value.powerOutput.data,
        borderColor: '#004C97',
        backgroundColor: 'rgba(0, 76, 151, 0.1)',
        tension: 0.4,
        fill: true
      }
    ]
  }
})

const trendsData = computed(() => {
  if (!data.value?.trends) return null
  
  return {
    labels: data.value.trends.labels,
    datasets: [
      {
        label: t('dashboard.charts.voltage'),
        data: data.value.trends.voltage,
        borderColor: '#004C97',
        backgroundColor: 'transparent',
        tension: 0.4
      },
      {
        label: t('dashboard.charts.current'),
        data: data.value.trends.current,
        borderColor: '#FEC601',
        backgroundColor: 'transparent',
        tension: 0.4
      },
      {
        label: t('dashboard.charts.temperature'),
        data: data.value.trends.temperature,
        borderColor: '#EF4444',
        backgroundColor: 'transparent',
        tension: 0.4
      }
    ]
  }
})

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const localeMap = { en: enUS, fr }
  return format(new Date(dateString), 'MMM dd, yyyy', { locale: localeMap[locale.value] })
}

const formatTime = (timestamp) => {
  const localeMap = { en: enUS, fr }
  return formatDistanceToNow(new Date(timestamp), {
    addSuffix: true,
    locale: localeMap[locale.value]
  })
}

const alertClass = (type) => {
  const classes = {
    critical: 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/10',
    warning: 'border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/10',
    info: 'border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/10'
  }
  return classes[type] || classes.info
}

const alertIcon = (type) => {
  return type === 'critical' ? AlertCircle : Info
}

const alertIconClass = (type) => {
  const classes = {
    critical: 'text-red-600 dark:text-red-400',
    warning: 'text-yellow-600 dark:text-yellow-400',
    info: 'text-blue-600 dark:text-blue-400'
  }
  return classes[type] || classes.info
}
</script>

<style scoped>
.dashboard-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  overflow: hidden;
}

@media (min-width: 640px) {
  .dashboard-container {
    gap: 16px;
  }
}
</style>
