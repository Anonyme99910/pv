<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ t('settings.title') }}</h1>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
      <nav class="flex space-x-4 sm:space-x-8 min-w-max">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="activeTab = tab.value"
          class="py-3 sm:py-4 px-1 border-b-2 font-medium text-xs sm:text-sm transition-colors whitespace-nowrap"
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

      <!-- SMTP Configuration Tab -->
      <div v-if="activeTab === 'smtp'" class="space-y-6">
        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
            SMTP Email Configuration
          </h3>
          <form @submit.prevent="saveSmtpSettings" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Host</label>
                <input v-model="smtpSettings.host" type="text" placeholder="smtp.gmail.com" class="input-field" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Port</label>
                <input v-model.number="smtpSettings.port" type="number" placeholder="587" class="input-field" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username</label>
                <input v-model="smtpSettings.username" type="text" placeholder="your@email.com" class="input-field" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
                <input v-model="smtpSettings.password" type="password" placeholder="••••••••" class="input-field" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Email</label>
                <input v-model="smtpSettings.from_email" type="email" placeholder="noreply@soma.com" class="input-field" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Name</label>
                <input v-model="smtpSettings.from_name" type="text" placeholder="SOMA PV System" class="input-field" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Encryption</label>
                <select v-model="smtpSettings.encryption" class="input-field">
                  <option value="tls">TLS</option>
                  <option value="ssl">SSL</option>
                  <option value="">None</option>
                </select>
              </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Notification Settings</h4>
              <div class="space-y-4">
                <label class="flex items-center space-x-3">
                  <input v-model="smtpSettings.notify_faults" type="checkbox" class="w-4 h-4 text-primary rounded" />
                  <span class="text-sm text-gray-700 dark:text-gray-300">Send email on fault detection</span>
                </label>
                <label class="flex items-center space-x-3">
                  <input v-model="smtpSettings.notify_maintenance" type="checkbox" class="w-4 h-4 text-primary rounded" />
                  <span class="text-sm text-gray-700 dark:text-gray-300">Send maintenance reminders</span>
                </label>
                <label class="flex items-center space-x-3">
                  <input v-model="smtpSettings.notify_critical" type="checkbox" class="w-4 h-4 text-primary rounded" />
                  <span class="text-sm text-gray-700 dark:text-gray-300">Send critical alerts immediately</span>
                </label>
              </div>
            </div>

            <div class="flex justify-between">
              <button type="button" @click="testSmtpConnection" class="btn-secondary" :disabled="testingSmtp">
                {{ testingSmtp ? 'Testing...' : 'Test Connection' }}
              </button>
              <button type="submit" class="btn-primary" :disabled="savingSmtp">
                {{ savingSmtp ? 'Saving...' : 'Save Settings' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Email Templates Section -->
        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Email Templates</h3>
          <div class="space-y-4">
            <div v-for="template in emailTemplates" :key="template.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                  <span class="text-lg">{{ template.icon }}</span>
                  <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">{{ template.name }}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ template.description }}</p>
                  </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="template.enabled" @change="toggleEmailTemplate(template)" class="sr-only peer" />
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                </label>
              </div>
              <div v-if="template.enabled" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subject</label>
                <input v-model="template.subject" type="text" class="input-field mb-3" />
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Template Body</label>
                <textarea v-model="template.body" rows="4" class="input-field" placeholder="Use {{variable}} for dynamic content"></textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Available variables: {{panel_code}}, {{fault_type}}, {{severity}}, {{detected_at}}, {{message}}</p>
                <div class="flex justify-end mt-3">
                  <button @click="saveEmailTemplate(template)" class="btn-primary text-sm">Save Template</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Integrations Tab (AI & Weather) -->
      <div v-if="activeTab === 'integrations'" class="space-y-6">
        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">AI Assistant (DeepSeek)</h3>
          <form @submit.prevent="saveAiSettings" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Key</label>
              <input v-model="aiSettings.api_key" type="password" placeholder="sk-..." class="input-field" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Model</label>
              <select v-model="aiSettings.model" class="input-field">
                <option value="deepseek-chat">DeepSeek Chat</option>
                <option value="deepseek-coder">DeepSeek Coder</option>
              </select>
            </div>
            <div class="flex justify-between">
              <button type="button" @click="testAiConnection" class="btn-secondary" :disabled="testingAi">
                {{ testingAi ? 'Testing...' : 'Test Connection' }}
              </button>
              <button type="submit" class="btn-primary">Save AI Settings</button>
            </div>
          </form>
        </div>

        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Weather API</h3>
          <form @submit.prevent="saveWeatherSettings" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">WeatherAPI.com Key</label>
              <input v-model="weatherSettings.api_key" type="password" placeholder="Your API key" class="input-field" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Default Location</label>
              <input v-model="weatherSettings.default_location" type="text" placeholder="Tunis, Tunisia" class="input-field" />
            </div>
            <div class="flex justify-end">
              <button type="submit" class="btn-primary">Save Weather Settings</button>
            </div>
          </form>
        </div>

        <!-- Email Templates -->
        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Email Templates</h3>
          <div class="space-y-4">
            <div v-for="template in emailTemplates" :key="template.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                  <span class="text-lg">{{ template.icon }}</span>
                  <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">{{ template.name }}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ template.description }}</p>
                  </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="template.enabled" @change="toggleEmailTemplate(template)" class="sr-only peer" />
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                </label>
              </div>
              <div v-if="template.enabled" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subject</label>
                <input v-model="template.subject" type="text" class="input-field mb-3" />
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Template Body</label>
                <textarea v-model="template.body" rows="4" class="input-field" placeholder="Use {{variable}} for dynamic content"></textarea>
                <div class="flex justify-end mt-3">
                  <button @click="saveEmailTemplate(template)" class="btn-primary text-sm">Save Template</button>
                </div>
              </div>
            </div>
          </div>
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
import { api } from '@/services/api'
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
  { value: 'smtp', label: 'settings.tabs.smtp' },
  { value: 'integrations', label: 'settings.tabs.integrations' },
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

// SMTP Settings
const smtpSettings = ref({
  host: '',
  port: 587,
  username: '',
  password: '',
  from_email: '',
  from_name: 'SOMA PV System',
  encryption: 'tls',
  notify_faults: true,
  notify_maintenance: true,
  notify_critical: true
})
const testingSmtp = ref(false)
const savingSmtp = ref(false)

// AI Settings
const aiSettings = ref({
  api_key: '',
  model: 'deepseek-chat'
})
const testingAi = ref(false)

// Weather Settings
const weatherSettings = ref({
  api_key: '',
  default_location: 'Tunis, Tunisia'
})

// Email Templates
const emailTemplates = ref([
  {
    id: 'fault_alert',
    name: 'Fault Alert',
    icon: '⚠️',
    description: 'Sent when a new fault is detected',
    enabled: true,
    subject: '[SOMA] Fault Detected - {{panel_code}}',
    body: 'A new fault has been detected:\n\nPanel: {{panel_code}}\nFault Type: {{fault_type}}\nSeverity: {{severity}}\nDetected: {{detected_at}}\n\nPlease investigate immediately.'
  },
  {
    id: 'maintenance_reminder',
    name: 'Maintenance Reminder',
    icon: '🔧',
    description: 'Sent before scheduled maintenance',
    enabled: true,
    subject: '[SOMA] Maintenance Reminder - {{panel_code}}',
    body: 'Maintenance is scheduled:\n\nPanel: {{panel_code}}\nTask: {{task_type}}\nScheduled: {{scheduled_date}}\n\nPlease ensure the task is completed on time.'
  },
  {
    id: 'critical_alert',
    name: 'Critical Alert',
    icon: '🚨',
    description: 'Sent for critical system issues',
    enabled: true,
    subject: '[SOMA] CRITICAL - {{alert_type}}',
    body: 'CRITICAL ALERT:\n\n{{message}}\n\nImmediate action required!'
  },
  {
    id: 'daily_report',
    name: 'Daily Report',
    icon: '📊',
    description: 'Daily system performance summary',
    enabled: false,
    subject: '[SOMA] Daily Report - {{date}}',
    body: 'Daily Performance Summary:\n\nTotal Panels: {{total_panels}}\nActive Faults: {{active_faults}}\nAvg Efficiency: {{avg_efficiency}}%\n\nView full report in the dashboard.'
  },
  {
    id: 'weekly_digest',
    name: 'Weekly Digest',
    icon: '📅',
    description: 'Weekly summary of system activity',
    enabled: false,
    subject: '[SOMA] Weekly Digest - Week {{week_number}}',
    body: 'Weekly System Digest:\n\nFaults Resolved: {{faults_resolved}}\nMaintenance Completed: {{maintenance_completed}}\nSystem Uptime: {{uptime}}%'
  }
])

onMounted(async () => {
  try {
    users.value = await api.getUsers()
    const thresholdsData = await api.getThresholds()
    if (thresholdsData) thresholds.value = thresholdsData
    
    // Load SMTP settings
    try {
      const smtp = await api.getSmtpSettings()
      if (smtp) Object.assign(smtpSettings.value, smtp)
    } catch (e) { console.log('SMTP settings not loaded') }
    
    // Load AI settings
    try {
      const ai = await api.getAiSettings()
      if (ai) Object.assign(aiSettings.value, ai)
    } catch (e) { console.log('AI settings not loaded') }
    
    // Load Weather settings
    try {
      const weather = await api.getWeatherSettings()
      if (weather) Object.assign(weatherSettings.value, weather)
    } catch (e) { console.log('Weather settings not loaded') }
  } catch (error) {
    console.error('Failed to load settings:', error)
  }
})

watch(locale, (newLocale) => {
  localStorage.setItem('locale', newLocale)
  // MPA: Reload page with new locale
  const currentPath = window.location.pathname
  const newPath = currentPath.replace(/\/(en|fr)\//, `/${newLocale}/`)
  if (newPath !== currentPath) {
    window.location.href = newPath
  }
})

const saveThresholds = async () => {
  try {
    await api.saveThresholds(thresholds.value)
    notificationStore.showToast(t('common.success'), 'success')
  } catch (error) {
    notificationStore.showToast(t('common.error'), 'error')
  }
}

const saveSmtpSettings = async () => {
  savingSmtp.value = true
  try {
    await api.updateSmtpSettings(smtpSettings.value)
    notificationStore.showToast('SMTP settings saved', 'success')
  } catch (error) {
    notificationStore.showToast('Failed to save SMTP settings', 'error')
  } finally {
    savingSmtp.value = false
  }
}

const testSmtpConnection = async () => {
  testingSmtp.value = true
  try {
    const result = await api.testSmtpConnection()
    notificationStore.showToast(result.message || 'Connection successful', result.success ? 'success' : 'error')
  } catch (error) {
    notificationStore.showToast('Connection failed', 'error')
  } finally {
    testingSmtp.value = false
  }
}

const saveAiSettings = async () => {
  try {
    await api.updateAiSettings(aiSettings.value)
    notificationStore.showToast('AI settings saved', 'success')
  } catch (error) {
    notificationStore.showToast('Failed to save AI settings', 'error')
  }
}

const testAiConnection = async () => {
  testingAi.value = true
  try {
    const result = await api.testAiConnection()
    notificationStore.showToast(result.message || 'Connection successful', result.success ? 'success' : 'error')
  } catch (error) {
    notificationStore.showToast('Connection failed', 'error')
  } finally {
    testingAi.value = false
  }
}

const saveWeatherSettings = async () => {
  try {
    await api.updateWeatherSettings(weatherSettings.value)
    notificationStore.showToast('Weather settings saved', 'success')
  } catch (error) {
    notificationStore.showToast('Failed to save weather settings', 'error')
  }
}

const toggleEmailTemplate = async (template) => {
  try {
    await api.updateEmailTemplate(template.id, { enabled: template.enabled })
    notificationStore.showToast(`${template.name} ${template.enabled ? 'enabled' : 'disabled'}`, 'success')
  } catch (error) {
    notificationStore.showToast('Failed to update template', 'error')
  }
}

const saveEmailTemplate = async (template) => {
  try {
    await api.updateEmailTemplate(template.id, template)
    notificationStore.showToast(`${template.name} template saved`, 'success')
  } catch (error) {
    notificationStore.showToast('Failed to save template', 'error')
  }
}

const saveApiConfig = () => {
  notificationStore.showToast('API configuration saved', 'success')
}

const regenerateApiKey = async () => {
  notificationStore.showToast('API key regenerated', 'success')
}
</script>
