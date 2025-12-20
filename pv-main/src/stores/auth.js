import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('user')) || null)
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!user.value && !!localStorage.getItem('auth_token'))
  const userRole = computed(() => user.value?.role || 'viewer')
  
  const canEdit = computed(() => ['admin', 'technician'].includes(userRole.value))
  const canDelete = computed(() => userRole.value === 'admin')
  const canAddUser = computed(() => userRole.value === 'admin')

  async function login(email, password) {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.login(email, password)
      user.value = response.user
      return true
    } catch (e) {
      error.value = e.response?.data?.message || 'Login failed. Please check your credentials.'
      return false
    } finally {
      loading.value = false
    }
  }

  function setUser(userData) {
    user.value = userData
    localStorage.setItem('user', JSON.stringify(userData))
  }

  async function logout() {
    try {
      await api.logout()
    } catch (e) {
      console.error('Logout error:', e)
    } finally {
      user.value = null
      localStorage.removeItem('user')
      localStorage.removeItem('auth_token')
    }
  }

  async function fetchUser() {
    if (!localStorage.getItem('auth_token')) return null
    
    try {
      const userData = await api.getUser()
      user.value = userData
      localStorage.setItem('user', JSON.stringify(userData))
      return userData
    } catch (e) {
      user.value = null
      localStorage.removeItem('user')
      localStorage.removeItem('auth_token')
      return null
    }
  }

  function updateRole(role) {
    if (user.value) {
      user.value.role = role
      localStorage.setItem('user', JSON.stringify(user.value))
    }
  }

  return {
    user,
    loading,
    error,
    isAuthenticated,
    userRole,
    canEdit,
    canDelete,
    canAddUser,
    login,
    setUser,
    logout,
    fetchUser,
    updateRole
  }
})
