<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ t('thermal.title') }}</h1>
        <p class="text-sm sm:text-base text-text-light dark:text-gray-400 mt-1">{{ t('thermal.subtitle') }}</p>
      </div>
      <div class="flex items-center space-x-2">
        <span 
          class="px-3 py-1 rounded-full text-xs sm:text-sm font-medium whitespace-nowrap"
          :class="aiServiceStatus ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
        >
          {{ aiServiceStatus ? t('thermal.aiOnline') : t('thermal.aiOffline') }}
        </span>
      </div>
    </div>

    <!-- Upload Section -->
    <div class="card p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        {{ t('thermal.uploadTitle') }}
      </h3>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Upload Area -->
        <div>
          <div
            class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
            :class="isDragging ? 'border-primary bg-primary/5' : 'border-gray-300 dark:border-gray-600 hover:border-primary'"
            @dragover.prevent="isDragging = true"
            @dragleave="isDragging = false"
            @drop.prevent="handleDrop"
          >
            <Upload class="w-12 h-12 mx-auto text-gray-400 mb-4" />
            <p class="text-gray-600 dark:text-gray-400 mb-2">
              {{ t('thermal.dropzone') }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mb-4">
              {{ t('thermal.supportedFormats') }}
            </p>
            <input
              ref="fileInput"
              type="file"
              accept="image/*"
              class="hidden"
              @change="handleFileSelect"
            />
            <button @click="$refs.fileInput.click()" class="btn-primary">
              {{ t('thermal.selectFile') }}
            </button>
          </div>

          <!-- Panel Selection -->
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ t('thermal.selectPanel') }}
            </label>
            <select v-model="selectedPanel" class="input-field">
              <option value="">{{ t('thermal.noPanel') }}</option>
              <option v-for="panel in panels" :key="panel.id" :value="panel.panel_code">
                {{ panel.panel_code }} - {{ panel.location }}
              </option>
            </select>
          </div>

          <!-- Analyze Button -->
          <button
            @click="analyzeImage"
            :disabled="!selectedFile || analyzing"
            class="btn-primary w-full mt-4"
          >
            <Loader v-if="analyzing" class="w-5 h-5 animate-spin mr-2" />
            {{ analyzing ? t('thermal.analyzing') : t('thermal.analyze') }}
          </button>
        </div>

        <!-- Preview -->
        <div>
          <div v-if="previewUrl" class="relative">
            <img
              :src="previewUrl"
              alt="Thermal preview"
              class="w-full rounded-lg border border-gray-200 dark:border-gray-700"
            />
            <button
              @click="clearImage"
              class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
          <div v-else class="h-64 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
            <div class="text-center text-gray-500 dark:text-gray-400">
              <ImageIcon class="w-12 h-12 mx-auto mb-2" />
              <p>{{ t('thermal.noPreview') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Section -->
    <div v-if="detectionResult" class="card p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ t('thermal.results') }}
        </h3>
        <span
          class="px-3 py-1 rounded-full text-sm font-medium"
          :class="severityClass(detectionResult.overall_severity)"
        >
          {{ detectionResult.overall_severity || 'OK' }}
        </span>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('thermal.faultDetected') }}</p>
          <p class="text-2xl font-bold" :class="detectionResult.fault_detected ? 'text-red-600' : 'text-green-600'">
            {{ detectionResult.fault_detected ? t('common.yes') : t('common.no') }}
          </p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('thermal.anomalyCount') }}</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ detectionResult.anomaly_count || 0 }}
          </p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('thermal.panel') }}</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ detectionResult.panel_code || '-' }}
          </p>
        </div>
      </div>

      <!-- Detections List -->
      <div v-if="detectionResult.detections?.length" class="mb-6">
        <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ t('thermal.detections') }}</h4>
        <div class="space-y-3">
          <div
            v-for="(detection, index) in detectionResult.detections"
            :key="index"
            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
          >
            <div class="flex items-center space-x-3">
              <AlertTriangle :class="severityIconClass(detection.severity)" class="w-5 h-5" />
              <div>
                <p class="font-medium text-gray-900 dark:text-white">
                  {{ formatFaultType(detection.fault_type) }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ detection.description }}
                </p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-medium text-gray-900 dark:text-white">
                {{ (detection.confidence * 100).toFixed(1) }}%
              </p>
              <span
                class="text-xs px-2 py-1 rounded-full"
                :class="severityClass(detection.severity)"
              >
                {{ detection.severity }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- AI Analysis -->
      <div v-if="detectionResult.ai_analysis" class="mb-6">
        <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ t('thermal.aiAnalysis') }}</h4>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
          <p class="text-gray-700 dark:text-gray-300">
            {{ detectionResult.ai_analysis.summary }}
          </p>
          <div v-if="detectionResult.ai_analysis.recommendations" class="mt-3">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ t('thermal.recommendations') }}:</p>
            <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
              <li v-for="(rec, i) in detectionResult.ai_analysis.recommendations" :key="i">
                {{ rec }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Suggested Actions -->
      <div v-if="detectionResult.suggested_actions?.length">
        <h4 class="font-medium text-gray-900 dark:text-white mb-3">{{ t('thermal.suggestedActions') }}</h4>
        <div class="space-y-2">
          <div
            v-for="(action, index) in detectionResult.suggested_actions"
            :key="index"
            class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
          >
            <div class="flex items-center space-x-3">
              <Wrench class="w-5 h-5 text-gray-400" />
              <span class="text-gray-900 dark:text-white">{{ action.action }}</span>
            </div>
            <div class="flex items-center space-x-4">
              <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ action.estimated_time }}
              </span>
              <span
                class="text-xs px-2 py-1 rounded-full"
                :class="priorityClass(action.priority)"
              >
                {{ action.priority }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fault Types Reference -->
    <div class="card p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        {{ t('thermal.faultTypesRef') }}
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="faultType in faultTypes"
          :key="faultType.name"
          class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg"
        >
          <div class="flex items-center space-x-2 mb-2">
            <div
              class="w-3 h-3 rounded-full"
              :class="severityDot(faultType.severity)"
            ></div>
            <span class="font-medium text-gray-900 dark:text-white">
              {{ formatFaultType(faultType.name) }}
            </span>
          </div>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ faultType.description }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { Upload, X, Image as ImageIcon, AlertTriangle, Wrench, Loader } from 'lucide-vue-next'
import { api } from '@/services/api'

const { t } = useI18n()

const fileInput = ref(null)
const selectedFile = ref(null)
const previewUrl = ref(null)
const selectedPanel = ref('')
const isDragging = ref(false)
const analyzing = ref(false)
const aiServiceStatus = ref(false)
const detectionResult = ref(null)
const panels = ref([])

const faultTypes = ref([
  { name: 'single_hotspot', severity: 'medium', description: 'Localized overheating detected' },
  { name: 'multiple_hotspots', severity: 'high', description: 'Multiple heat points detected' },
  { name: 'single_diode_fault', severity: 'high', description: 'Bypass diode failure' },
  { name: 'multiple_diode_faults', severity: 'critical', description: 'Multiple diode failures' },
  { name: 'single_bypassed_substring', severity: 'medium', description: 'Partial module bypass' },
  { name: 'multiple_bypassed_substrings', severity: 'high', description: 'Multiple module bypasses' },
  { name: 'open_circuit_string', severity: 'critical', description: 'Broken string connection' },
  { name: 'reversed_polarity_string', severity: 'critical', description: 'Wiring polarity error' },
])

onMounted(async () => {
  await checkAIService()
  await loadPanels()
})

async function checkAIService() {
  try {
    await api.getAIServiceHealth()
    aiServiceStatus.value = true
  } catch {
    aiServiceStatus.value = false
  }
}

async function loadPanels() {
  try {
    const response = await api.getPanels({ limit: 100 })
    panels.value = response.data || response
  } catch (e) {
    console.error('Failed to load panels:', e)
  }
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    setFile(file)
  }
}

function handleDrop(event) {
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file && file.type.startsWith('image/')) {
    setFile(file)
  }
}

function setFile(file) {
  selectedFile.value = file
  previewUrl.value = URL.createObjectURL(file)
  detectionResult.value = null
}

function clearImage() {
  selectedFile.value = null
  previewUrl.value = null
  detectionResult.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

async function analyzeImage() {
  if (!selectedFile.value) return

  analyzing.value = true
  try {
    const result = await api.detectThermalDefects(selectedFile.value, selectedPanel.value || null)
    detectionResult.value = result
  } catch (e) {
    console.error('Detection failed:', e)
    alert(t('thermal.detectionFailed'))
  } finally {
    analyzing.value = false
  }
}

function formatFaultType(type) {
  return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

function severityClass(severity) {
  const classes = {
    low: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    critical: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  }
  return classes[severity] || classes.medium
}

function severityIconClass(severity) {
  const classes = {
    low: 'text-blue-500',
    medium: 'text-yellow-500',
    high: 'text-orange-500',
    critical: 'text-red-500',
  }
  return classes[severity] || classes.medium
}

function severityDot(severity) {
  const classes = {
    low: 'bg-blue-500',
    medium: 'bg-yellow-500',
    high: 'bg-orange-500',
    critical: 'bg-red-500',
  }
  return classes[severity] || classes.medium
}

function priorityClass(priority) {
  const classes = {
    low: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    medium: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    urgent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  }
  return classes[priority] || classes.medium
}
</script>
