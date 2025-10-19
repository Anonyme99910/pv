<template>
  <div class="relative">
    <div class="grid grid-cols-16 gap-1 max-h-96 overflow-y-auto p-2">
      <div
        v-for="panel in panels"
        :key="panel.id"
        class="panel-cell"
        :class="panelClass(panel.status)"
        :title="`${panel.id}\nStatus: ${panel.status}\nEfficiency: ${panel.efficiency.toFixed(1)}%\nTemp: ${panel.temperature.toFixed(1)}°C`"
        @click="selectPanel(panel)"
      >
      </div>
    </div>
    
    <!-- Panel Details Modal -->
    <div
      v-if="selectedPanel"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click="selectedPanel = null"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4"
        @click.stop
      >
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
          {{ selectedPanel.id }}
        </h3>
        <div class="space-y-3">
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Status:</span>
            <span class="badge" :class="statusBadgeClass(selectedPanel.status)">
              {{ selectedPanel.status }}
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Efficiency:</span>
            <span class="font-medium text-gray-900 dark:text-white">
              {{ selectedPanel.efficiency.toFixed(1) }}%
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Temperature:</span>
            <span class="font-medium text-gray-900 dark:text-white">
              {{ selectedPanel.temperature.toFixed(1) }}°C
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Voltage:</span>
            <span class="font-medium text-gray-900 dark:text-white">
              {{ selectedPanel.voltage.toFixed(1) }}V
            </span>
          </div>
        </div>
        <button
          @click="selectedPanel = null"
          class="mt-6 w-full btn-primary"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  panels: {
    type: Array,
    default: () => []
  }
})

const selectedPanel = ref(null)

const panelClass = (status) => {
  const classes = {
    healthy: 'bg-green-500 hover:bg-green-600',
    warning: 'bg-yellow-500 hover:bg-yellow-600',
    fault: 'bg-red-500 hover:bg-red-600'
  }
  return classes[status] || classes.healthy
}

const statusBadgeClass = (status) => {
  const classes = {
    healthy: 'badge-success',
    warning: 'badge-warning',
    fault: 'badge-danger'
  }
  return classes[status] || 'badge-info'
}

const selectPanel = (panel) => {
  selectedPanel.value = panel
}
</script>

<style scoped>
.grid-cols-16 {
  grid-template-columns: repeat(16, minmax(0, 1fr));
}

.panel-cell {
  @apply w-full aspect-square rounded cursor-pointer transition-all duration-200;
}

.panel-cell:hover {
  @apply scale-110 shadow-lg;
}
</style>
