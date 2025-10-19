<template>
  <div class="card p-6 hover:shadow-card-hover transition-shadow" :title="tooltip">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <p class="text-sm text-text-light dark:text-gray-400 mb-2">{{ title }}</p>
        <div v-if="loading" class="h-8 w-24 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
        <p v-else class="text-3xl font-bold text-gray-900 dark:text-white">{{ value }}</p>
      </div>
      <div
        class="w-12 h-12 rounded-lg flex items-center justify-center"
        :class="iconBgClass"
      >
        <component :is="icon" class="w-6 h-6" :class="iconClass" />
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
