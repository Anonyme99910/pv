<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ t('aiChat.title') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('aiChat.subtitle') }}</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs sm:text-sm whitespace-nowrap" 
              :class="isConnected ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
          <span class="w-2 h-2 rounded-full" :class="isConnected ? 'bg-green-500' : 'bg-red-500'"></span>
          {{ isConnected ? t('aiChat.connected') : t('aiChat.disconnected') }}
        </span>
      </div>
    </div>

    <!-- Chat Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      <!-- Messages Area -->
      <div ref="messagesContainer" class="h-[500px] overflow-y-auto p-6 space-y-4">
        <!-- Welcome Message -->
        <div v-if="messages.length === 0" class="text-center py-12">
          <div class="w-16 h-16 bg-gradient-to-br from-amber-400 to-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <Bot class="w-8 h-8 text-white" />
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ t('aiChat.welcomeTitle') }}</h3>
          <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">{{ t('aiChat.welcomeMessage') }}</p>
          
          <!-- Suggestions -->
          <div class="flex flex-wrap justify-center gap-2">
            <button 
              v-for="suggestion in suggestions" 
              :key="suggestion"
              @click="sendMessage(suggestion)"
              class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm hover:bg-amber-100 dark:hover:bg-amber-900/30 hover:text-amber-700 dark:hover:text-amber-400 transition-colors"
            >
              {{ suggestion }}
            </button>
          </div>
        </div>

        <!-- Messages -->
        <div v-for="(message, index) in messages" :key="index" class="flex gap-3" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
          <!-- AI Avatar -->
          <div v-if="message.role === 'assistant'" class="flex-shrink-0">
            <div class="w-8 h-8 bg-gradient-to-br from-amber-400 to-amber-600 rounded-lg flex items-center justify-center">
              <Bot class="w-5 h-5 text-white" />
            </div>
          </div>
          
          <!-- Message Bubble -->
          <div class="max-w-[70%] rounded-2xl px-4 py-3" 
               :class="message.role === 'user' 
                 ? 'bg-primary text-white rounded-br-md' 
                 : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-bl-md'">
            <p class="whitespace-pre-wrap">{{ message.content }}</p>
            <span class="text-xs opacity-70 mt-1 block">{{ formatTime(message.timestamp) }}</span>
          </div>

          <!-- User Avatar -->
          <div v-if="message.role === 'user'" class="flex-shrink-0">
            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
              <User class="w-5 h-5 text-white" />
            </div>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex gap-3">
          <div class="w-8 h-8 bg-gradient-to-br from-amber-400 to-amber-600 rounded-lg flex items-center justify-center">
            <Bot class="w-5 h-5 text-white" />
          </div>
          <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-bl-md px-4 py-3">
            <div class="flex gap-1">
              <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
              <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
              <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Input Area -->
      <div class="border-t border-gray-200 dark:border-gray-700 p-4">
        <form @submit.prevent="handleSubmit" class="flex gap-3">
          <input
            v-model="inputMessage"
            type="text"
            :placeholder="t('aiChat.placeholder')"
            class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary"
            :disabled="isLoading"
          />
          <button
            type="submit"
            :disabled="!inputMessage.trim() || isLoading"
            class="px-6 py-3 bg-primary text-white rounded-xl font-medium hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
          >
            <Send class="w-5 h-5" />
            {{ t('aiChat.send') }}
          </button>
        </form>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <button @click="askAboutFaults" class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-amber-500 transition-colors text-left">
        <AlertTriangle class="w-6 h-6 text-amber-500 mb-2" />
        <h3 class="font-medium text-gray-900 dark:text-white">{{ t('aiChat.actions.analyzeFaults') }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('aiChat.actions.analyzeFaultsDesc') }}</p>
      </button>
      <button @click="askAboutMaintenance" class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-blue-500 transition-colors text-left">
        <Wrench class="w-6 h-6 text-blue-500 mb-2" />
        <h3 class="font-medium text-gray-900 dark:text-white">{{ t('aiChat.actions.maintenance') }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('aiChat.actions.maintenanceDesc') }}</p>
      </button>
      <button @click="askAboutPerformance" class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-green-500 transition-colors text-left">
        <TrendingUp class="w-6 h-6 text-green-500 mb-2" />
        <h3 class="font-medium text-gray-900 dark:text-white">{{ t('aiChat.actions.performance') }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('aiChat.actions.performanceDesc') }}</p>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { Bot, User, Send, AlertTriangle, Wrench, TrendingUp } from 'lucide-vue-next'
import { api } from '@/services/api'

const { t } = useI18n()

const messages = ref([])
const inputMessage = ref('')
const isLoading = ref(false)
const isConnected = ref(true)
const messagesContainer = ref(null)

const suggestions = [
  'What panels need attention?',
  'Show me today\'s faults',
  'Maintenance recommendations',
  'System performance summary'
]

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const formatTime = (timestamp) => {
  return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const sendMessage = async (content) => {
  if (!content.trim()) return

  // Add user message
  messages.value.push({
    role: 'user',
    content: content.trim(),
    timestamp: new Date()
  })
  
  inputMessage.value = ''
  isLoading.value = true
  scrollToBottom()

  try {
    const response = await api.sendChatMessage(content)
    // Handle both nested and flat response structures
    const aiResponse = response.response || response.data?.response || response.message || response.data?.message
    if (aiResponse) {
      messages.value.push({
        role: 'assistant',
        content: aiResponse,
        timestamp: new Date()
      })
      isConnected.value = true
    } else if (response.success === false) {
      messages.value.push({
        role: 'assistant',
        content: response.message || 'AI service error. Please check configuration.',
        timestamp: new Date()
      })
      isConnected.value = false
    }
  } catch (error) {
    console.error('Chat error:', error)
    const errorMsg = error.response?.data?.message || 'Sorry, I encountered an error. Please check if the AI service is configured in Settings.'
    messages.value.push({
      role: 'assistant',
      content: errorMsg,
      timestamp: new Date()
    })
    isConnected.value = false
  } finally {
    isLoading.value = false
    scrollToBottom()
  }
}

const handleSubmit = () => {
  sendMessage(inputMessage.value)
}

const askAboutFaults = () => {
  sendMessage('Analyze the current faults in the system and provide recommendations')
}

const askAboutMaintenance = () => {
  sendMessage('What maintenance tasks should be prioritized this week?')
}

const askAboutPerformance = () => {
  sendMessage('Give me a summary of the overall system performance')
}

onMounted(async () => {
  try {
    const response = await api.getChatHistory()
    if (response.data.messages) {
      messages.value = response.data.messages.map(m => ({
        role: m.role || (m.is_user ? 'user' : 'assistant'),
        content: m.content || m.message,
        timestamp: new Date(m.created_at || m.timestamp)
      }))
      scrollToBottom()
    }
  } catch (error) {
    console.error('Failed to load chat history:', error)
  }
})
</script>
