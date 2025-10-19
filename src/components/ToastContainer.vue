<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <transition-group name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast"
        :class="toastClass(toast.type)"
      >
        <div class="flex items-start space-x-3">
          <component :is="getIcon(toast.type)" class="w-5 h-5 flex-shrink-0 mt-0.5" />
          <p class="flex-1 text-sm">{{ toast.message }}</p>
          <button
            @click="notificationStore.removeToast(toast.id)"
            class="flex-shrink-0 hover:opacity-70"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useNotificationStore } from '@/stores/notifications'
import { CheckCircle, XCircle, AlertTriangle, Info, X } from 'lucide-vue-next'

const notificationStore = useNotificationStore()
const toasts = computed(() => notificationStore.toasts)

const toastClass = (type) => {
  const classes = {
    success: 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 border-green-200 dark:border-green-800',
    error: 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 border-red-200 dark:border-red-800',
    warning: 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 border-yellow-200 dark:border-yellow-800',
    info: 'bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 border-blue-200 dark:border-blue-800'
  }
  return classes[type] || classes.info
}

const getIcon = (type) => {
  const icons = {
    success: CheckCircle,
    error: XCircle,
    warning: AlertTriangle,
    info: Info
  }
  return icons[type] || Info
}
</script>

<style scoped>
.toast {
  @apply min-w-[320px] max-w-md p-4 rounded-lg border shadow-lg;
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.toast-leave-to {
  transform: translateX(100%);
  opacity: 0;
}
</style>
