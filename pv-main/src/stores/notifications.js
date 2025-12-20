import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNotificationStore = defineStore('notifications', () => {
  const notifications = ref([])
  const toasts = ref([])

  function addNotification(notification) {
    const newNotification = {
      id: Date.now(),
      timestamp: new Date(),
      read: false,
      ...notification
    }
    notifications.value.unshift(newNotification)
    
    // Keep only last 50 notifications
    if (notifications.value.length > 50) {
      notifications.value = notifications.value.slice(0, 50)
    }
  }

  function markAsRead(id) {
    const notification = notifications.value.find(n => n.id === id)
    if (notification) {
      notification.read = true
    }
  }

  function markAllAsRead() {
    notifications.value.forEach(n => n.read = true)
  }

  function clearAll() {
    notifications.value = []
  }

  function showToast(message, type = 'info', duration = 3000) {
    const toast = {
      id: Date.now(),
      message,
      type, // success, error, warning, info
      duration
    }
    toasts.value.push(toast)
    
    setTimeout(() => {
      removeToast(toast.id)
    }, duration)
  }

  function removeToast(id) {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  return {
    notifications,
    toasts,
    addNotification,
    markAsRead,
    markAllAsRead,
    clearAll,
    showToast,
    removeToast
  }
})
