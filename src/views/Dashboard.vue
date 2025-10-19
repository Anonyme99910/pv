<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ t('dashboard.title') }}</h1>
      <p class="text-text-light dark:text-gray-400 mt-1">{{ t('dashboard.welcome') }}</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Power Output Chart -->
      <div class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          {{ t('dashboard.charts.powerOutput') }}
        </h3>
        <LineChart
          v-if="data?.powerOutput"
          :data="powerOutputData"
          :height="250"
        />
        <div v-else class="h-64 flex items-center justify-center">
          <Loader class="w-8 h-8 animate-spin text-primary" />
        </div>
      </div>

      <!-- System Trends Chart -->
      <div class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          {{ t('dashboard.charts.trends') }}
        </h3>
        <LineChart
          v-if="data?.trends"
          :data="trendsData"
          :height="250"
        />
        <div v-else class="h-64 flex items-center justify-center">
          <Loader class="w-8 h-8 animate-spin text-primary" />
        </div>
      </div>
    </div>

    <!-- System Map and Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Panel Grid -->
      <div class="lg:col-span-2 card p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ t('dashboard.systemMap.title') }}
          </h3>
          <div class="flex items-center space-x-4 text-sm">
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 bg-green-500 rounded-full"></div>
              <span class="text-gray-600 dark:text-gray-400">{{ t('dashboard.systemMap.healthy') }}</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
              <span class="text-gray-600 dark:text-gray-400">{{ t('dashboard.systemMap.warning') }}</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-3 h-3 bg-red-500 rounded-full"></div>
              <span class="text-gray-600 dark:text-gray-400">{{ t('dashboard.systemMap.fault') }}</span>
            </div>
          </div>
        </div>
        <PanelGrid :panels="data?.panelGrid || []" />
      </div>

      <!-- Alerts Feed -->
      <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ t('dashboard.alerts.title') }}
          </h3>
          <button class="text-sm text-primary hover:underline">
            {{ t('dashboard.alerts.viewAll') }}
          </button>
        </div>
        <div class="space-y-3">
          <div
            v-for="alert in data?.alerts || []"
            :key="alert.id"
            class="p-3 rounded-lg border transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
            :class="alertClass(alert.type)"
          >
            <div class="flex items-start space-x-3">
              <component
                :is="alertIcon(alert.type)"
                class="w-5 h-5 flex-shrink-0 mt-0.5"
                :class="alertIconClass(alert.type)"
              />
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-900 dark:text-white">{{ alert.message }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  {{ formatTime(alert.timestamp) }}
                </p>
              </div>
            </div>
          </div>
          <div v-if="!data?.alerts?.length" class="text-center py-8 text-gray-500 dark:text-gray-400">
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
import { mockAPI, useAutoRefresh } from '@/services/api'

const { t, locale } = useI18n()

const { data, loading, fetch } = useAutoRefresh(
  mockAPI.getLiveData,
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
