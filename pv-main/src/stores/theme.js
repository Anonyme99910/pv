import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  const isDark = ref(localStorage.getItem('theme') === 'dark')

  watch(isDark, (newValue) => {
    if (newValue) {
      document.documentElement.classList.add('dark')
      localStorage.setItem('theme', 'dark')
    } else {
      document.documentElement.classList.remove('dark')
      localStorage.setItem('theme', 'light')
    }
  }, { immediate: true })

  function toggleTheme() {
    isDark.value = !isDark.value
  }

  function setTheme(theme) {
    isDark.value = theme === 'dark'
  }

  return {
    isDark,
    toggleTheme,
    setTheme
  }
})
