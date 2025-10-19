<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ t('profile.title') }}</h1>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ t('profile.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
      <!-- Left Column - Profile Info -->
      <div class="lg:col-span-1 space-y-4 sm:space-y-6">
        <!-- Profile Card -->
        <div class="card p-6">
          <div class="flex flex-col items-center">
            <!-- Avatar -->
            <div class="relative">
              <div class="w-24 h-24 sm:w-32 sm:h-32 bg-gradient-to-br from-primary to-accent rounded-full flex items-center justify-center">
                <span class="text-4xl sm:text-5xl font-bold text-white">{{ userInitials }}</span>
              </div>
              <button class="absolute bottom-0 right-0 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors">
                <Camera class="w-4 h-4" />
              </button>
            </div>

            <!-- User Info -->
            <div class="mt-4 text-center">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ authStore.user.name }}</h2>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ authStore.user.email }}</p>
              <span class="inline-block mt-2 px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary text-sm rounded-full">
                {{ t(`settings.users.roles.${authStore.userRole}`) }}
              </span>
            </div>

            <!-- Stats -->
            <div class="w-full mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
              <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.tasksCompleted }}</p>
                  <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ t('profile.stats.tasks') }}</p>
                </div>
                <div>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.faultsResolved }}</p>
                  <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ t('profile.stats.faults') }}</p>
                </div>
                <div>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.daysActive }}</p>
                  <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ t('profile.stats.days') }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ t('profile.quickActions') }}</h3>
          <div class="space-y-2">
            <button class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-left">
              <Bell class="w-5 h-5 text-gray-600 dark:text-gray-400" />
              <span class="text-sm text-gray-900 dark:text-white">{{ t('profile.actions.notifications') }}</span>
            </button>
            <button class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-left">
              <Shield class="w-5 h-5 text-gray-600 dark:text-gray-400" />
              <span class="text-sm text-gray-900 dark:text-white">{{ t('profile.actions.security') }}</span>
            </button>
            <button class="w-full flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-left">
              <Download class="w-5 h-5 text-gray-600 dark:text-gray-400" />
              <span class="text-sm text-gray-900 dark:text-white">{{ t('profile.actions.exportData') }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Right Column - Profile Details -->
      <div class="lg:col-span-2 space-y-4 sm:space-y-6">
        <!-- Personal Information -->
        <div class="card p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('profile.personalInfo') }}</h3>
            <button 
              @click="editMode = !editMode"
              class="btn-secondary flex items-center space-x-2"
            >
              <Edit2 class="w-4 h-4" />
              <span>{{ editMode ? t('common.cancel') : t('common.edit') }}</span>
            </button>
          </div>

          <form @submit.prevent="saveProfile" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  {{ t('profile.form.firstName') }}
                </label>
                <input
                  v-model="profileForm.firstName"
                  type="text"
                  :disabled="!editMode"
                  class="input-field"
                  :class="{ 'bg-gray-50 dark:bg-gray-700/50': !editMode }"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  {{ t('profile.form.lastName') }}
                </label>
                <input
                  v-model="profileForm.lastName"
                  type="text"
                  :disabled="!editMode"
                  class="input-field"
                  :class="{ 'bg-gray-50 dark:bg-gray-700/50': !editMode }"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('profile.form.email') }}
              </label>
              <input
                v-model="profileForm.email"
                type="email"
                :disabled="!editMode"
                class="input-field"
                :class="{ 'bg-gray-50 dark:bg-gray-700/50': !editMode }"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('profile.form.phone') }}
              </label>
              <input
                v-model="profileForm.phone"
                type="tel"
                :disabled="!editMode"
                class="input-field"
                :class="{ 'bg-gray-50 dark:bg-gray-700/50': !editMode }"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('profile.form.department') }}
              </label>
              <input
                v-model="profileForm.department"
                type="text"
                :disabled="!editMode"
                class="input-field"
                :class="{ 'bg-gray-50 dark:bg-gray-700/50': !editMode }"
              />
            </div>

            <div v-if="editMode" class="flex items-center justify-end space-x-3 pt-4">
              <button type="button" @click="editMode = false" class="btn-secondary">
                {{ t('common.cancel') }}
              </button>
              <button type="submit" class="btn-primary">
                {{ t('common.save') }}
              </button>
            </div>
          </form>
        </div>

        <!-- Change Password -->
        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">{{ t('profile.changePassword') }}</h3>
          
          <form @submit.prevent="changePassword" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('profile.form.currentPassword') }}
              </label>
              <input
                v-model="passwordForm.current"
                type="password"
                class="input-field"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('profile.form.newPassword') }}
              </label>
              <input
                v-model="passwordForm.new"
                type="password"
                class="input-field"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('profile.form.confirmPassword') }}
              </label>
              <input
                v-model="passwordForm.confirm"
                type="password"
                class="input-field"
              />
            </div>

            <div class="flex items-center justify-end pt-4">
              <button type="submit" class="btn-primary">
                {{ t('profile.updatePassword') }}
              </button>
            </div>
          </form>
        </div>

        <!-- Activity Log -->
        <div class="card p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">{{ t('profile.recentActivity') }}</h3>
          
          <div class="space-y-4">
            <div 
              v-for="activity in recentActivities" 
              :key="activity.id"
              class="flex items-start space-x-4 pb-4 border-b border-gray-200 dark:border-gray-700 last:border-0 last:pb-0"
            >
              <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="activity.iconBg">
                <component :is="activity.icon" class="w-5 h-5" :class="activity.iconColor" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ activity.title }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ activity.description }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ activity.time }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { 
  Camera, 
  Bell, 
  Shield, 
  Download, 
  Edit2,
  CheckCircle,
  AlertTriangle,
  Settings,
  FileText
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'

const { t } = useI18n()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

const editMode = ref(false)

const userInitials = computed(() => {
  const name = authStore.user.name || 'User'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const stats = ref({
  tasksCompleted: 24,
  faultsResolved: 18,
  daysActive: 45
})

const profileForm = ref({
  firstName: 'John',
  lastName: 'Doe',
  email: authStore.user.email,
  phone: '+1 234 567 8900',
  department: 'Maintenance'
})

const passwordForm = ref({
  current: '',
  new: '',
  confirm: ''
})

const recentActivities = ref([
  {
    id: 1,
    title: 'Resolved Fault',
    description: 'Panel PV-045 efficiency degradation fixed',
    time: '2 hours ago',
    icon: CheckCircle,
    iconBg: 'bg-green-100 dark:bg-green-900/30',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    id: 2,
    title: 'Maintenance Task',
    description: 'Completed cleaning task for Zone A',
    time: '5 hours ago',
    icon: Settings,
    iconBg: 'bg-blue-100 dark:bg-blue-900/30',
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    id: 3,
    title: 'Report Generated',
    description: 'Monthly analytics report exported',
    time: '1 day ago',
    icon: FileText,
    iconBg: 'bg-purple-100 dark:bg-purple-900/30',
    iconColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    id: 4,
    title: 'Alert Acknowledged',
    description: 'Temperature anomaly in Panel PV-089',
    time: '2 days ago',
    icon: AlertTriangle,
    iconBg: 'bg-yellow-100 dark:bg-yellow-900/30',
    iconColor: 'text-yellow-600 dark:text-yellow-400'
  }
])

const saveProfile = () => {
  notificationStore.showToast(t('profile.profileUpdated'), 'success')
  editMode.value = false
}

const changePassword = () => {
  if (passwordForm.value.new !== passwordForm.value.confirm) {
    notificationStore.showToast(t('profile.passwordMismatch'), 'error')
    return
  }
  
  notificationStore.showToast(t('profile.passwordUpdated'), 'success')
  passwordForm.value = { current: '', new: '', confirm: '' }
}
</script>
