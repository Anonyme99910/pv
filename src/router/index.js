import { createRouter, createWebHistory } from 'vue-router'
import MainLayout from '@/layouts/MainLayout.vue'
import i18n from '@/i18n'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/:locale/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/:locale?',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/Dashboard.vue')
      },
      {
        path: 'fault-detection',
        name: 'FaultDetection',
        component: () => import('@/views/FaultDetection.vue')
      },
      {
        path: 'maintenance',
        name: 'Maintenance',
        component: () => import('@/views/Maintenance.vue')
      },
      {
        path: 'analytics',
        name: 'Analytics',
        component: () => import('@/views/Analytics.vue')
      },
      {
        path: 'settings',
        name: 'Settings',
        component: () => import('@/views/Settings.vue')
      },
      {
        path: 'profile',
        name: 'Profile',
        component: () => import('@/views/Profile.vue')
      }
    ]
  },
  {
    path: '/',
    redirect: () => {
      const locale = localStorage.getItem('locale') || 'en'
      return `/${locale}`
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const locale = to.params.locale || 'en'
  
  // Validate locale
  if (!['en', 'fr'].includes(locale)) {
    return next('/en')
  }
  
  // Set i18n locale
  i18n.global.locale.value = locale
  localStorage.setItem('locale', locale)
  
  // Check authentication
  const authStore = useAuthStore()
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest)
  
  if (requiresAuth && !authStore.isAuthenticated) {
    // Redirect to login if not authenticated
    return next(`/${locale}/login`)
  }
  
  if (requiresGuest && authStore.isAuthenticated) {
    // Redirect to dashboard if already logged in
    return next(`/${locale}`)
  }
  
  next()
})

export default router
