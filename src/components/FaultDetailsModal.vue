<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click="$emit('close')">
    <div
      class="bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto"
      @click.stop
    >
      <!-- Header -->
      <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ t('faultDetection.modal.title') }}
          </h2>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ fault.panelId }} - {{ t(`faultDetection.faultTypes.${fault.faultType}`) }}
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
        >
          <X class="w-6 h-6 text-gray-600 dark:text-gray-400" />
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 space-y-6">
        <!-- Fault Info -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('faultDetection.table.confidence') }}</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ fault.confidence }}%</p>
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('faultDetection.table.status') }}</p>
            <span class="badge" :class="statusBadgeClass(fault.status)">
              {{ t(`faultDetection.status.${fault.status}`) }}
            </span>
          </div>
          <div class="col-span-2">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('faultDetection.table.detectedOn') }}</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatDate(fault.detectedOn) }}</p>
          </div>
        </div>

        <!-- Sensor Data Charts -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            {{ t('faultDetection.modal.sensorData') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="card p-4">
              <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-3">
                {{ t('faultDetection.modal.voltage') }}
              </h4>
              <LineChart
                v-if="voltageData"
                :data="voltageData"
                :height="150"
              />
            </div>
            <div class="card p-4">
              <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-3">
                {{ t('faultDetection.modal.temperature') }}
              </h4>
              <LineChart
                v-if="temperatureData"
                :data="temperatureData"
                :height="150"
              />
            </div>
            <div class="card p-4">
              <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-3">
                {{ t('faultDetection.modal.dust') }}
              </h4>
              <LineChart
                v-if="dustData"
                :data="dustData"
                :height="150"
              />
            </div>
          </div>
        </div>

        <!-- AI Analysis -->
        <div class="card p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
          <div class="flex items-start space-x-3">
            <Brain class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                {{ t('faultDetection.modal.aiAnalysis') }}
              </h3>
              <p class="text-gray-700 dark:text-gray-300 mb-4">
                {{ fault.aiAnalysis?.rootCause }}
              </p>
              <div v-if="fault.aiAnalysis?.contributingFactors" class="space-y-2">
                <p class="text-sm font-medium text-gray-900 dark:text-white">Contributing Factors:</p>
                <ul class="list-disc list-inside space-y-1 text-sm text-gray-700 dark:text-gray-300">
                  <li v-for="(factor, index) in fault.aiAnalysis.contributingFactors" :key="index">
                    {{ factor }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Suggested Actions -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            {{ t('faultDetection.modal.suggestedActions') }}
          </h3>
          <div class="space-y-3">
            <div
              v-for="(action, index) in fault.suggestedActions"
              :key="index"
              class="card p-4 flex items-start space-x-4"
            >
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                :class="priorityClass(action.priority)"
              >
                <span class="text-sm font-bold">{{ index + 1 }}</span>
              </div>
              <div class="flex-1">
                <div class="flex items-center justify-between mb-1">
                  <p class="font-medium text-gray-900 dark:text-white">{{ action.action }}</p>
                  <span class="badge" :class="priorityBadgeClass(action.priority)">
                    {{ action.priority }}
                  </span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Estimated time: {{ action.estimatedTime }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="sticky bottom-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-end space-x-3">
        <button
          @click="$emit('close')"
          class="btn-secondary"
        >
          {{ t('faultDetection.modal.close') }}
        </button>
        <button
          v-if="canEdit && fault.status !== 'resolved'"
          @click="$emit('resolve', fault.id)"
          class="btn-accent"
        >
          {{ t('faultDetection.modal.markResolved') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { X, Brain } from 'lucide-vue-next'
import { format } from 'date-fns'
import { enUS, fr } from 'date-fns/locale'
import { useAuthStore } from '@/stores/auth'
import LineChart from './LineChart.vue'

const props = defineProps({
  fault: {
    type: Object,
    required: true
  }
})

defineEmits(['close', 'resolve'])

const { t, locale } = useI18n()
const authStore = useAuthStore()

const canEdit = computed(() => authStore.canEdit)

const voltageData = computed(() => {
  if (!props.fault.sensorData) return null
  return {
    labels: props.fault.sensorData.labels,
    datasets: [{
      label: 'Voltage (V)',
      data: props.fault.sensorData.voltage,
      borderColor: '#004C97',
      backgroundColor: 'transparent',
      tension: 0.4
    }]
  }
})

const temperatureData = computed(() => {
  if (!props.fault.sensorData) return null
  return {
    labels: props.fault.sensorData.labels,
    datasets: [{
      label: 'Temperature (°C)',
      data: props.fault.sensorData.temperature,
      borderColor: '#EF4444',
      backgroundColor: 'transparent',
      tension: 0.4
    }]
  }
})

const dustData = computed(() => {
  if (!props.fault.sensorData) return null
  return {
    labels: props.fault.sensorData.labels,
    datasets: [{
      label: 'Dust Level (%)',
      data: props.fault.sensorData.dust,
      borderColor: '#FEC601',
      backgroundColor: 'transparent',
      tension: 0.4
    }]
  }
})

const formatDate = (dateString) => {
  const localeMap = { en: enUS, fr }
  return format(new Date(dateString), 'PPpp', { locale: localeMap[locale.value] })
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

const priorityClass = (priority) => {
  const classes = {
    high: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    medium: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
    low: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400'
  }
  return classes[priority] || classes.medium
}

const priorityBadgeClass = (priority) => {
  const classes = {
    high: 'badge-danger',
    medium: 'badge-warning',
    low: 'badge-info'
  }
  return classes[priority] || 'badge-info'
}
</script>
