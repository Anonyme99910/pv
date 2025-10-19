import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('user')) || null)

  const isAuthenticated = computed(() => !!user.value)
  const userRole = computed(() => user.value?.role || 'viewer')
  
  const canEdit = computed(() => ['admin', 'technician'].includes(userRole.value))
  const canDelete = computed(() => userRole.value === 'admin')
  const canAddUser = computed(() => userRole.value === 'admin')

  // Demo accounts
  const demoUsers = {
    'admin@soma.com': { name: 'Admin User', email: 'admin@soma.com', role: 'admin', password: 'admin123' },
    'tech@soma.com': { name: 'Tech User', email: 'tech@soma.com', role: 'technician', password: 'tech123' },
    'viewer@soma.com': { name: 'Viewer User', email: 'viewer@soma.com', role: 'viewer', password: 'viewer123' }
  }

  function login(email, password) {
    const demoUser = demoUsers[email]
    
    if (demoUser && demoUser.password === password) {
      const userData = {
        name: demoUser.name,
        email: demoUser.email,
        role: demoUser.role,
        avatar: null
      }
      user.value = userData
      localStorage.setItem('user', JSON.stringify(userData))
      return true
    }
    
    return false
  }

  function setUser(userData) {
    user.value = userData
    localStorage.setItem('user', JSON.stringify(userData))
  }

  function logout() {
    user.value = null
    localStorage.removeItem('user')
  }

  function updateRole(role) {
    if (user.value) {
      user.value.role = role
      localStorage.setItem('user', JSON.stringify(user.value))
    }
  }

  return {
    user,
    isAuthenticated,
    userRole,
    canEdit,
    canDelete,
    canAddUser,
    login,
    setUser,
    logout,
    updateRole
  }
})
