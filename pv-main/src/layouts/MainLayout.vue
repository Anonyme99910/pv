<template>
  <div class="layout-container">
    <!-- Mobile Overlay -->
    <div 
      v-if="sidebarOpen" 
      class="fixed inset-0 bg-black/50 z-40 lg:hidden"
      @click="sidebarOpen = false"
    ></div>
    
    <!-- Mobile Sidebar - Fixed position, slides in -->
    <aside 
      v-if="sidebarOpen"
      class="mobile-sidebar lg:hidden"
    >
      <Sidebar :is-open="true" @close="sidebarOpen = false" />
    </aside>
    
    <!-- Desktop Sidebar - Static in flex layout -->
    <div class="hidden lg:block lg:shrink-0">
      <Sidebar :is-open="true" @close="sidebarOpen = false" />
    </div>
    
    <!-- Main Content - Takes full width on mobile -->
    <div class="main-wrapper">
      <!-- Top Bar -->
      <TopBar @toggle-sidebar="sidebarOpen = !sidebarOpen" />
      
      <!-- Page Content -->
      <main class="content-area">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Sidebar from '@/components/Sidebar.vue'
import TopBar from '@/components/TopBar.vue'

const sidebarOpen = ref(false)
</script>

<style scoped>
.layout-container {
  display: flex;
  height: 100vh;
  height: 100dvh; /* Dynamic viewport height for mobile */
  width: 100%;
  overflow: hidden;
  background: var(--bg-background, #f3f4f6);
}

.mobile-sidebar {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  width: 256px;
  z-index: 50;
  background: white;
}

.main-wrapper {
  flex: 1 1 0%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-width: 0;
  width: 100%;
  position: relative;
  z-index: 10;
}

.content-area {
  flex: 1 1 0%;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 8px;
  min-width: 0;
  width: 100%;
}

@media (min-width: 640px) {
  .content-area {
    padding: 16px;
  }
}

@media (min-width: 768px) {
  .content-area {
    padding: 24px;
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
