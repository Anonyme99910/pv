<template>
  <aside 
    class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col transform transition-transform duration-300 lg:transform-none"
    :class="isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <!-- Logo -->
    <div class="h-16 flex items-center justify-between px-6 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-gradient-to-br from-primary to-accent rounded-lg flex items-center justify-center">
          <Sun class="w-6 h-6 text-white" />
        </div>
        <div>
          <h1 class="text-xl font-bold text-primary dark:text-white">SOMA</h1>
          <p class="text-xs text-text-light dark:text-gray-400">PV Monitoring</p>
        </div>
      </div>
      <!-- Close button for mobile -->
      <button 
        @click="$emit('close')"
        class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
      >
        <X class="w-5 h-5" />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
      <router-link
        v-for="item in navItems"
        :key="item.name"
        :to="item.path"
        class="nav-item"
        :class="{ 'active': isActive(item.path) }"
        @click="$emit('close')"
      >
        <component :is="item.icon" class="w-5 h-5" />
        <span>{{ t(item.label) }}</span>
      </router-link>
    </nav>

    <!-- Footer -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
      <div class="text-xs text-text-light dark:text-gray-400 text-center">
        © 2025 SOMA Platform
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { 
  LayoutDashboard, 
  AlertTriangle, 
  Wrench, 
  BarChart3, 
  Settings,
  Sun,
  X
} from 'lucide-vue-next'

defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
})

defineEmits(['close'])

const { t } = useI18n()
const route = useRoute()

const locale = computed(() => route.params.locale || 'en')

const navItems = computed(() => [
  {
    name: 'dashboard',
    path: `/${locale.value}`,
    label: 'nav.dashboard',
    icon: LayoutDashboard
  },
  {
    name: 'faultDetection',
    path: `/${locale.value}/fault-detection`,
    label: 'nav.faultDetection',
    icon: AlertTriangle
  },
  {
    name: 'maintenance',
    path: `/${locale.value}/maintenance`,
    label: 'nav.maintenance',
    icon: Wrench
  },
  {
    name: 'analytics',
    path: `/${locale.value}/analytics`,
    label: 'nav.analytics',
    icon: BarChart3
  },
  {
    name: 'settings',
    path: `/${locale.value}/settings`,
    label: 'nav.settings',
    icon: Settings
  }
])

const isActive = (path) => {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>

<style scoped>
.nav-item {
  @apply flex items-center space-x-3 px-4 py-3 rounded-lg text-text-light dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 cursor-pointer;
}

.nav-item.active {
  @apply bg-primary-50 dark:bg-primary-900/20 text-primary dark:text-primary-300 font-medium;
}
</style>
