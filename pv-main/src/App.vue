<template>
  <router-view />
  <ToastContainer />
  <ChatWidget v-if="isAuthenticated" />
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import ToastContainer from '@/components/ToastContainer.vue'
import ChatWidget from '@/components/ChatWidget.vue'

const authStore = useAuthStore()
const isAuthenticated = computed(() => authStore.isAuthenticated)
</script>
