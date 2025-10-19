<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ t('settings.title') }}</h1>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <nav class="flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="activeTab = tab.value"
          class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
          :class="activeTab === tab.value
            ? 'border-primary text-primary'
            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
        >
          {{ t(tab.label) }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div>
      <!-- User Management Tab -->
      <div v-if="activeTab === 'users'" class="space-y-6">
        <div class="card overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ t('settings.users.title') }}
            </h3>
            <button
              v-if="canAddUser"
              @click="showAddUserModal = true"
              class="btn-primary flex items-center space-x-2"
            >
              <UserPlus class="w-5 h-5" />
              <span>{{ t('settings.users.addUser') }}</span>
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ t('settings.users.table.name') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ t('settings.users.table.email') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ t('settings.users.table.role') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ t('settings.users.table.status') }}
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ t('settings.users.table.actions') }}
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                  v-for="user in users"
                  :key="user.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                >
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                    {{ user.name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                    {{ user.email }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="badge badge-info">
                      {{ t(`settings.users.roles.${user.role}`) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="badge" :class="user.status === 'active' ? 'badge-success' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'">
                      {{ t(`settings.users.status.${user.status}`) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                    <button
                      v-if="canEdit"
                      class="text-primary hover:text-primary-600 font-medium"
                    >
                      {{ t('common.edit') }}
                    </button>
                    <button
                      v-if="canDelete"
                      class="text-red-600 hover:text-red-700 font-medium"
                    >
                      {{ t('common.delete') }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Thresholds Tab -->
      <div v-if="activeTab === 'thresholds'" class="space-y-6">
        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
            {{ t('settings.thresholds.title') }}
          </h3>
          <form @submit.prevent="saveThresholds" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  {{ t('settings.thresholds.voltage') }}
                </label>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <input
                      v-model.number="thresholds.voltage.min"
                      type="number"
                      step="0.1"
                      placeholder="Min"
                      class="input-field"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Min (V)</p>
                  </div>
                  <div>
                    <input
                      v-model.number="thresholds.voltage.max"
                      type="number"
                      step="0.1"
                      placeholder="Max"
                      class="input-field"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Max (V)</p>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  {{ t('settings.thresholds.temperature') }}
                </label>
                <input
                  v-model.number="thresholds.temperature.max"
                  type="number"
                  step="0.1"
                  placeholder="Max temperature"
                  class="input-field"
                />
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Max (°C)</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  {{ t('settings.thresholds.efficiency') }}
                </label>
                <input
                  v-model.number="thresholds.efficiency.min"
                  type="number"
                  step="0.1"
                  placeholder="Min efficiency"
                  class="input-field"
                />
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Min (%)</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  {{ t('settings.thresholds.dust') }}
                </label>
                <input
                  v-model.number="thresholds.dust.max"
                  type="number"
                  step="0.1"
                  placeholder="Max dust level"
                  class="input-field"
                />
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Max (%)</p>
              </div>
            </div>

            <div class="flex justify-end">
              <button type="submit" class="btn-primary">
                {{ t('settings.thresholds.save') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- API Configuration Tab -->
      <div v-if="activeTab === 'api'" class="space-y-6">
        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
            {{ t('settings.api.title') }}
          </h3>
          <form @submit.prevent="saveApiConfig" class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('settings.api.key') }}
              </label>
              <div class="flex items-center space-x-2">
                <input
                  v-model="apiConfig.apiKey"
                  :type="showApiKey ? 'text' : 'password'"
                  readonly
                  class="input-field flex-1"
                />
                <button
                  type="button"
                  @click="showApiKey = !showApiKey"
                  class="btn-secondary"
                >
                  <Eye v-if="!showApiKey" class="w-5 h-5" />
                  <EyeOff v-else class="w-5 h-5" />
                </button>
                <button
                  type="button"
                  @click="regenerateApiKey"
                  class="btn-accent"
                >
                  {{ t('settings.api.regenerate') }}
                </button>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('settings.api.endpoint') }}
              </label>
              <input
                v-model="apiConfig.endpoint"
                type="url"
                class="input-field"
              />
            </div>

            <div class="flex justify-end">
              <button type="submit" class="btn-primary">
                {{ t('settings.api.save') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Preferences Tab -->
      <div v-if="activeTab === 'preferences'" class="space-y-6">
        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
            {{ t('settings.preferences.title') }}
          </h3>
          <div class="space-y-6">
            <!-- Theme -->
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ t('settings.preferences.theme') }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Choose your preferred theme
                </p>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="themeStore.setTheme('light')"
                  class="px-4 py-2 rounded-lg font-medium transition-colors"
                  :class="!themeStore.isDark 
                    ? 'bg-primary text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                >
                  {{ t('settings.preferences.light') }}
                </button>
                <button
                  @click="themeStore.setTheme('dark')"
                  class="px-4 py-2 rounded-lg font-medium transition-colors"
                  :class="themeStore.isDark 
                    ? 'bg-primary text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                >
                  {{ t('settings.preferences.dark') }}
                </button>
              </div>
            </div>

            <!-- Language -->
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ t('settings.preferences.language') }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Select your preferred language
                </p>
              </div>
              <select v-model="locale" class="input-field w-48">
                <option value="en">English</option>
                <option value="fr">Français</option>
              </select>
            </div>

            <!-- Notifications -->
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ t('settings.preferences.notifications') }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Receive notifications for new faults
                </p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  v-model="preferences.notifications"
                  type="checkbox"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
              </label>
            </div>

            <!-- Auto Refresh -->
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ t('settings.preferences.autoRefresh') }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Automatically refresh dashboard data
                </p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  v-model="preferences.autoRefresh"
                  type="checkbox"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
              </label>
            </div>

            <!-- Refresh Interval -->
            <div v-if="preferences.autoRefresh" class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ t('settings.preferences.refreshInterval') }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  How often to refresh data
                </p>
              </div>
              <input
                v-model.number="preferences.refreshInterval"
                type="number"
                min="5"
                max="60"
                class="input-field w-32"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { UserPlus, Eye, EyeOff } from 'lucide-vue-next'
import { mockAPI } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'
import { useNotificationStore } from '@/stores/notifications'

const { t, locale } = useI18n()
const authStore = useAuthStore()
const themeStore = useThemeStore()
const notificationStore = useNotificationStore()

const canEdit = computed(() => authStore.canEdit)
const canDelete = computed(() => authStore.canDelete)
const canAddUser = computed(() => authStore.canAddUser)

const activeTab = ref('users')
const showAddUserModal = ref(false)
const showApiKey = ref(false)

const tabs = [
  { value: 'users', label: 'settings.tabs.users' },
  { value: 'thresholds', label: 'settings.tabs.thresholds' },
  { value: 'api', label: 'settings.tabs.api' },
  { value: 'preferences', label: 'settings.tabs.preferences' }
]

const users = ref([])
const thresholds = ref({
  voltage: { min: 215, max: 245 },
  temperature: { max: 55 },
  efficiency: { min: 80 },
  dust: { max: 20 }
})

const apiConfig = ref({
  apiKey: '',
  endpoint: 'https://api.soma-pv.com/v1'
})

const preferences = ref({
  notifications: true,
  autoRefresh: true,
  refreshInterval: 5
})

onMounted(async () => {
  users.value = await mockAPI.getUsers()
  const thresholdsData = await mockAPI.getThresholds()
  thresholds.value = thresholdsData
  const apiData = await mockAPI.getApiConfig()
  apiConfig.value = apiData
})

watch(locale, (newLocale) => {
  localStorage.setItem('locale', newLocale)
})

const saveThresholds = async () => {
  try {
    await mockAPI.saveThresholds(thresholds.value)
    notificationStore.showToast(t('common.success'), 'success')
  } catch (error) {
    notificationStore.showToast(t('common.error'), 'error')
  }
}

const saveApiConfig = () => {
  notificationStore.showToast('API configuration saved', 'success')
}

const regenerateApiKey = async () => {
  const newConfig = await mockAPI.getApiConfig()
  apiConfig.value.apiKey = newConfig.apiKey
  notificationStore.showToast('API key regenerated', 'success')
}
</script>
