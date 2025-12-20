<template>
  <div class="card p-2 sm:p-4 hover:shadow-card-hover transition-shadow" :title="tooltip">
    <div class="flex items-start justify-between gap-1">
      <div class="flex-1 min-w-0">
        <p class="text-[10px] sm:text-xs text-text-light dark:text-gray-400 mb-0.5 sm:mb-1 truncate">{{ title }}</p>
        <div v-if="loading" class="h-4 sm:h-6 w-12 sm:w-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
        <p v-else class="text-sm sm:text-xl font-bold text-gray-900 dark:text-white truncate">{{ value }}</p>
      </div>
      <div
        class="w-6 h-6 sm:w-10 sm:h-10 rounded-md flex items-center justify-center flex-shrink-0"
        :class="iconBgClass"
      >
        <component :is="icon" class="w-3 h-3 sm:w-5 sm:h-5" :class="iconClass" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: String,
  value: [String, Number],
  icon: [Object, Function],
  color: {
    type: String,
    default: 'blue'
  },
  loading: Boolean,
  tooltip: String
})

const iconBgClass = computed(() => {
  const classes = {
    blue: 'bg-blue-100 dark:bg-blue-900/30',
    red: 'bg-red-100 dark:bg-red-900/30',
    green: 'bg-green-100 dark:bg-green-900/30',
    purple: 'bg-purple-100 dark:bg-purple-900/30',
    yellow: 'bg-yellow-100 dark:bg-yellow-900/30'
  }
  return classes[props.color] || classes.blue
})

const iconClass = computed(() => {
  const classes = {
    blue: 'text-blue-600 dark:text-blue-400',
    red: 'text-red-600 dark:text-red-400',
    green: 'text-green-600 dark:text-green-400',
    purple: 'text-purple-600 dark:text-purple-400',
    yellow: 'text-yellow-600 dark:text-yellow-400'
  }
  return classes[props.color] || classes.blue
})
</script>
