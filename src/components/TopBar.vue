<template>
  <header class="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-4 sm:px-6">
    <!-- Mobile Menu Button -->
    <button
      @click="$emit('toggle-sidebar')"
      class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    >
      <Menu class="w-6 h-6 text-gray-600 dark:text-gray-300" />
    </button>

    <!-- Search -->
    <div class="flex-1 max-w-md mx-4 hidden sm:block">
      <div class="relative">
        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          type="text"
          :placeholder="t('topbar.search')"
          class="w-full pl-10 pr-4 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
        />
      </div>
    </div>

    <!-- Right Section -->
    <div class="flex items-center space-x-2 sm:space-x-4">
      <!-- Language Switcher -->
      <div class="relative">
        <button
          @click="toggleLanguageMenu"
          class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          <Languages class="w-5 h-5 text-gray-600 dark:text-gray-300" />
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ locale.toUpperCase() }}</span>
        </button>
        
        <div
          v-if="showLanguageMenu"
          class="absolute right-0 mt-2 w-32 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
        >
          <button
            @click="changeLanguage('en')"
            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            :class="{ 'bg-primary-50 dark:bg-primary-900/20 text-primary': locale === 'en' }"
          >
            English
          </button>
          <button
            @click="changeLanguage('fr')"
            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            :class="{ 'bg-primary-50 dark:bg-primary-900/20 text-primary': locale === 'fr' }"
          >
            Français
          </button>
        </div>
      </div>

      <!-- Notifications -->
      <div class="relative">
        <button
          @click="toggleNotifications"
          class="relative p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          <Bell class="w-5 h-5 text-gray-600 dark:text-gray-300" />
          <span
            v-if="unreadCount > 0"
            class="absolute top-1 right-1 w-4 h-4 bg-red-500 rounded-full text-xs text-white flex items-center justify-center"
          >
            {{ unreadCount > 9 ? '9+' : unreadCount }}
          </span>
        </button>

        <div
          v-if="showNotifications"
          class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
        >
          <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">{{ t('topbar.notifications') }}</h3>
            <button
              @click="notificationStore.markAllAsRead"
              class="text-xs text-primary hover:underline"
            >
              Mark all read
            </button>
          </div>
          <div class="max-h-96 overflow-y-auto">
            <div
              v-for="notification in notifications.slice(0, 5)"
              :key="notification.id"
              class="p-4 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
              :class="{ 'bg-blue-50 dark:bg-blue-900/10': !notification.read }"
              @click="notificationStore.markAsRead(notification.id)"
            >
              <p class="text-sm text-gray-900 dark:text-white">{{ notification.message }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ formatTime(notification.timestamp) }}
              </p>
            </div>
            <div v-if="notifications.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
              {{ t('dashboard.alerts.noAlerts') }}
            </div>
          </div>
        </div>
      </div>

      <!-- Theme Toggle -->
      <button
        @click="themeStore.toggleTheme"
        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
      >
        <Moon v-if="!themeStore.isDark" class="w-5 h-5 text-gray-600 dark:text-gray-300" />
        <Sun v-else class="w-5 h-5 text-gray-600 dark:text-gray-300" />
      </button>

      <!-- Profile Menu -->
      <div class="relative">
        <button
          @click="toggleProfileMenu"
          class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          <div class="w-8 h-8 bg-gradient-to-br from-primary to-accent rounded-full flex items-center justify-center">
            <span class="text-white text-sm font-medium">{{ userInitials }}</span>
          </div>
        </button>

        <div
          v-if="showProfileMenu"
          class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
        >
          <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ authStore.user.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ authStore.user.email }}</p>
            <span class="inline-block mt-1 px-2 py-0.5 bg-primary-100 dark:bg-primary-900/30 text-primary text-xs rounded">
              {{ t(`settings.users.roles.${authStore.userRole}`) }}
            </span>
          </div>
          <router-link
            :to="`/${locale}/profile`"
            @click="showProfileMenu = false"
            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center space-x-2"
          >
            <User class="w-4 h-4" />
            <span>{{ t('topbar.profile') }}</span>
          </router-link>
          <button
            @click="handleLogout"
            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center space-x-2"
          >
            <LogOut class="w-4 h-4" />
            <span>{{ t('topbar.logout') }}</span>
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { Search, Bell, Languages, Moon, Sun, User, LogOut, Menu } from 'lucide-vue-next'

defineEmits(['toggle-sidebar'])
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'
import { useNotificationStore } from '@/stores/notifications'
import { formatDistanceToNow } from 'date-fns'
import { enUS, fr } from 'date-fns/locale'

const { t, locale } = useI18n()
const authStore = useAuthStore()
const themeStore = useThemeStore()
const notificationStore = useNotificationStore()

const showLanguageMenu = ref(false)
const showNotifications = ref(false)
const showProfileMenu = ref(false)

const notifications = computed(() => notificationStore.notifications)
const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)

const userInitials = computed(() => {
  const name = authStore.user.name || 'User'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
})

const toggleLanguageMenu = () => {
  showLanguageMenu.value = !showLanguageMenu.value
  showNotifications.value = false
  showProfileMenu.value = false
}

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value
  showLanguageMenu.value = false
  showProfileMenu.value = false
}

const toggleProfileMenu = () => {
  showProfileMenu.value = !showProfileMenu.value
  showLanguageMenu.value = false
  showNotifications.value = false
}

const changeLanguage = (lang) => {
  locale.value = lang
  localStorage.setItem('locale', lang)
  showLanguageMenu.value = false
  
  // Redirect to home page with new language
  window.location.href = `/${lang}`
}

const formatTime = (timestamp) => {
  const localeMap = { en: enUS, fr }
  return formatDistanceToNow(new Date(timestamp), {
    addSuffix: true,
    locale: localeMap[locale.value]
  })
}

const handleLogout = () => {
  authStore.logout()
  showProfileMenu.value = false
  notificationStore.showToast(t('topbar.logoutSuccess'), 'success')
  
  // Redirect to login page
  const currentLocale = locale.value
  window.location.href = `/${currentLocale}/login`
}

// Initialize sample notifications on mount
onMounted(() => {
  if (notificationStore.notifications.length === 0) {
    notificationStore.addNotification({
      type: 'warning',
      message: 'Temperature anomaly detected in Panel PV-045'
    })
    notificationStore.addNotification({
      type: 'info',
      message: 'Maintenance completed for Panel PV-123'
    })
    notificationStore.addNotification({
      type: 'critical',
      message: 'Voltage drop detected in Panel PV-089'
    })
  }
})

// Close menus when clicking outside
if (typeof window !== 'undefined') {
  window.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
      showLanguageMenu.value = false
      showNotifications.value = false
      showProfileMenu.value = false
    }
  })
}
</script>
