<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary/10 via-background to-accent/10 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 px-4 sm:px-6 lg:px-8">
    <!-- Background Pattern -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-primary/10 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-accent/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-md">
      <!-- Logo & Title -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-primary to-accent rounded-2xl shadow-lg mb-4">
          <Sun class="w-12 h-12 text-white" />
        </div>
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">SOMA</h1>
        <p class="text-lg text-gray-600 dark:text-gray-400">{{ t('login.subtitle') }}</p>
      </div>

      <!-- Login Card -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 sm:p-10 border border-gray-200 dark:border-gray-700">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ t('login.title') }}</h2>
        
        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ t('login.email') }}
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Mail class="w-5 h-5 text-gray-400" />
              </div>
              <input
                v-model="credentials.email"
                type="email"
                required
                class="input-field pl-10"
                :placeholder="t('login.emailPlaceholder')"
              />
            </div>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ t('login.password') }}
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Lock class="w-5 h-5 text-gray-400" />
              </div>
              <input
                v-model="credentials.password"
                :type="showPassword ? 'text' : 'password'"
                required
                class="input-field pl-10 pr-10"
                :placeholder="t('login.passwordPlaceholder')"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <EyeOff v-if="showPassword" class="w-5 h-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" />
                <Eye v-else class="w-5 h-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" />
              </button>
            </div>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center">
            <label class="flex items-center">
              <input
                v-model="rememberMe"
                type="checkbox"
                class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
              />
              <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ t('login.rememberMe') }}</span>
            </label>
          </div>

          <!-- Login Button -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full btn-primary flex items-center justify-center space-x-2 py-3"
          >
            <Loader v-if="loading" class="w-5 h-5 animate-spin" />
            <span>{{ loading ? t('login.loggingIn') : t('login.signIn') }}</span>
          </button>
        </form>

        <!-- Divider -->
        <div class="relative my-6">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
          </div>
          <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white dark:bg-gray-800 text-gray-500">{{ t('login.orContinueWith') }}</span>
          </div>
        </div>

        <!-- Demo Accounts -->
        <div class="space-y-3">
          <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">{{ t('login.demoAccounts') }}</p>
          <button
            v-for="demo in demoAccounts"
            :key="demo.email"
            @click="loginWithDemo(demo)"
            type="button"
            class="w-full flex items-center justify-between px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="demo.bgColor">
                <component :is="demo.icon" class="w-5 h-5" :class="demo.iconColor" />
              </div>
              <div class="text-left">
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ t(`login.roles.${demo.role}`) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ demo.email }}</p>
              </div>
            </div>
            <ChevronRight class="w-5 h-5 text-gray-400" />
          </button>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-8 text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          © 2025 SOMA Platform. {{ t('login.allRightsReserved') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Sun, Mail, Lock, Eye, EyeOff, Loader, ChevronRight, Shield, Wrench, User } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

const credentials = ref({
  email: '',
  password: ''
})

const showPassword = ref(false)
const rememberMe = ref(false)
const loading = ref(false)

const demoAccounts = [
  {
    email: 'admin@soma.com',
    password: 'admin123',
    role: 'admin',
    icon: Shield,
    bgColor: 'bg-purple-100 dark:bg-purple-900/30',
    iconColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    email: 'tech@soma.com',
    password: 'tech123',
    role: 'technician',
    icon: Wrench,
    bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    email: 'viewer@soma.com',
    password: 'viewer123',
    role: 'viewer',
    icon: User,
    bgColor: 'bg-green-100 dark:bg-green-900/30',
    iconColor: 'text-green-600 dark:text-green-400'
  }
]

const handleLogin = async () => {
  loading.value = true
  
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    const success = authStore.login(credentials.value.email, credentials.value.password)
    
    if (success) {
      notificationStore.showToast(t('login.loginSuccess'), 'success')
      const locale = localStorage.getItem('locale') || 'en'
      router.push(`/${locale}`)
    } else {
      notificationStore.showToast(t('login.invalidCredentials'), 'error')
    }
  } catch (error) {
    notificationStore.showToast(t('login.loginError'), 'error')
  } finally {
    loading.value = false
  }
}

const loginWithDemo = (demo) => {
  credentials.value.email = demo.email
  credentials.value.password = demo.password
  handleLogin()
}
</script>
