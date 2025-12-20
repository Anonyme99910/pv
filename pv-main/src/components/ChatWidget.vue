<template>
  <div class="chat-widget">
    <!-- Toggle Button -->
    <button
      @click="isOpen = !isOpen"
      class="chat-toggle"
      :class="{ 'chat-open': isOpen }"
    >
      <MessageCircle v-if="!isOpen" class="w-6 h-6" />
      <X v-else class="w-6 h-6" />
    </button>

    <!-- Chat Window -->
    <Transition name="chat">
      <div v-if="isOpen" class="chat-window">
        <div class="chat-header">
          <div class="flex items-center gap-2">
            <Bot class="w-5 h-5 text-amber-500" />
            <span class="font-semibold">SOMA AI Assistant</span>
          </div>
          <button @click="clearHistory" class="text-gray-400 hover:text-white">
            <Trash2 class="w-4 h-4" />
          </button>
        </div>

        <!-- Messages -->
        <div ref="messagesContainer" class="chat-messages">
          <div v-if="messages.length === 0" class="empty-state">
            <Bot class="w-12 h-12 text-amber-500 mx-auto mb-3" />
            <p class="text-gray-400 text-sm">Hi! I'm your SOMA AI Assistant.</p>
            <p class="text-gray-500 text-xs mt-1">Ask me anything about your solar panels.</p>
            
            <!-- Suggestions -->
            <div v-if="suggestions.length > 0" class="suggestions mt-4">
              <button
                v-for="(suggestion, index) in suggestions"
                :key="index"
                @click="sendSuggestion(suggestion)"
                class="suggestion-btn"
              >
                {{ suggestion.text }}
              </button>
            </div>
          </div>

          <div
            v-for="(msg, index) in messages"
            :key="index"
            class="message"
            :class="msg.role"
          >
            <div class="message-avatar">
              <User v-if="msg.role === 'user'" class="w-4 h-4" />
              <Bot v-else class="w-4 h-4" />
            </div>
            <div class="message-content">
              <p v-html="formatMessage(msg.content)"></p>
              <span class="message-time">{{ formatTime(msg.timestamp) }}</span>
            </div>
          </div>

          <div v-if="isLoading" class="message assistant">
            <div class="message-avatar">
              <Bot class="w-4 h-4" />
            </div>
            <div class="message-content">
              <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Input -->
        <div class="chat-input">
          <input
            v-model="inputMessage"
            @keyup.enter="sendMessage"
            placeholder="Type your message..."
            :disabled="isLoading"
            class="chat-input-field"
          />
          <button
            @click="sendMessage"
            :disabled="!inputMessage.trim() || isLoading"
            class="chat-send-btn"
          >
            <Send class="w-4 h-4" />
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue'
import { MessageCircle, X, Send, Bot, User, Trash2 } from 'lucide-vue-next'
import { api } from '@/services/api'

const isOpen = ref(false)
const inputMessage = ref('')
const messages = ref([])
const suggestions = ref([])
const isLoading = ref(false)
const messagesContainer = ref(null)

const loadHistory = async () => {
  try {
    const response = await api.getChatHistory()
    if (response.success && response.messages) {
      messages.value = response.messages.map(msg => ({
        role: 'user',
        content: msg.message,
        timestamp: new Date(msg.created_at)
      })).concat(response.messages.filter(m => m.response).map(msg => ({
        role: 'assistant',
        content: msg.response,
        timestamp: new Date(msg.created_at)
      }))).sort((a, b) => a.timestamp - b.timestamp)
    }
  } catch (error) {
    console.error('Failed to load chat history:', error)
  }
}

const loadSuggestions = async () => {
  try {
    const response = await api.getChatSuggestions()
    if (response.success) {
      suggestions.value = response.suggestions
    }
  } catch (error) {
    console.error('Failed to load suggestions:', error)
  }
}

const sendMessage = async () => {
  if (!inputMessage.value.trim() || isLoading.value) return

  const userMessage = inputMessage.value.trim()
  inputMessage.value = ''

  messages.value.push({
    role: 'user',
    content: userMessage,
    timestamp: new Date()
  })

  await scrollToBottom()
  isLoading.value = true

  try {
    const response = await api.sendChatMessage(userMessage)
    
    if (response.success) {
      messages.value.push({
        role: 'assistant',
        content: response.response,
        timestamp: new Date()
      })
    } else {
      messages.value.push({
        role: 'assistant',
        content: response.message || 'Sorry, I encountered an error. Please try again.',
        timestamp: new Date()
      })
    }
  } catch (error) {
    messages.value.push({
      role: 'assistant',
      content: 'Sorry, I\'m having trouble connecting. Please try again later.',
      timestamp: new Date()
    })
  } finally {
    isLoading.value = false
    await scrollToBottom()
  }
}

const sendSuggestion = (suggestion) => {
  inputMessage.value = suggestion.text
  sendMessage()
}

const clearHistory = async () => {
  try {
    await api.clearChatHistory()
    messages.value = []
  } catch (error) {
    console.error('Failed to clear history:', error)
  }
}

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const formatMessage = (content) => {
  return content
    .replace(/\n/g, '<br>')
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
}

const formatTime = (date) => {
  return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

watch(isOpen, (newVal) => {
  if (newVal && messages.value.length === 0) {
    loadHistory()
    loadSuggestions()
  }
})

onMounted(() => {
  loadSuggestions()
})
</script>

<style scoped>
.chat-widget {
  position: fixed;
  bottom: 12px;
  right: 12px;
  z-index: 40;
  pointer-events: none;
}

@media (min-width: 640px) {
  .chat-widget {
    bottom: 24px;
    right: 24px;
  }
}

.chat-widget > * {
  pointer-events: auto;
}

.chat-toggle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 20px rgba(245, 158, 11, 0.4);
  transition: all 0.3s ease;
}

@media (min-width: 640px) {
  .chat-toggle {
    width: 56px;
    height: 56px;
  }
}

.chat-toggle:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 25px rgba(245, 158, 11, 0.5);
}

.chat-toggle.chat-open {
  background: #374151;
}

.chat-window {
  position: absolute;
  bottom: 56px;
  right: 0;
  width: 320px;
  max-width: calc(100vw - 24px);
  height: 400px;
  max-height: calc(100vh - 100px);
  background: #1f2937;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

@media (min-width: 640px) {
  .chat-window {
    bottom: 70px;
    width: 380px;
    max-width: calc(100vw - 48px);
    height: 500px;
    max-height: calc(100vh - 120px);
    border-radius: 16px;
  }
}

.chat-header {
  padding: 16px;
  background: #111827;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #374151;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
}

.suggestions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.suggestion-btn {
  padding: 8px 12px;
  background: #374151;
  border: 1px solid #4b5563;
  border-radius: 8px;
  color: #d1d5db;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.suggestion-btn:hover {
  background: #4b5563;
  border-color: #f59e0b;
}

.message {
  display: flex;
  gap: 8px;
  max-width: 85%;
}

.message.user {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.message.assistant {
  align-self: flex-start;
}

.message-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.message.user .message-avatar {
  background: #3b82f6;
  color: white;
}

.message.assistant .message-avatar {
  background: #f59e0b;
  color: white;
}

.message-content {
  padding: 10px 14px;
  border-radius: 12px;
  font-size: 14px;
  line-height: 1.5;
}

.message.user .message-content {
  background: #3b82f6;
  color: white;
  border-bottom-right-radius: 4px;
}

.message.assistant .message-content {
  background: #374151;
  color: #e5e7eb;
  border-bottom-left-radius: 4px;
}

.message-time {
  display: block;
  font-size: 10px;
  opacity: 0.6;
  margin-top: 4px;
}

.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 4px 0;
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  background: #9ca3af;
  border-radius: 50%;
  animation: typing 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes typing {
  0%, 60%, 100% {
    transform: translateY(0);
  }
  30% {
    transform: translateY(-4px);
  }
}

.chat-input {
  padding: 12px;
  background: #111827;
  display: flex;
  gap: 8px;
  border-top: 1px solid #374151;
}

.chat-input-field {
  flex: 1;
  padding: 10px 14px;
  background: #374151;
  border: 1px solid #4b5563;
  border-radius: 8px;
  color: white;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}

.chat-input-field:focus {
  border-color: #f59e0b;
}

.chat-input-field::placeholder {
  color: #9ca3af;
}

.chat-send-btn {
  width: 40px;
  height: 40px;
  background: #f59e0b;
  border: none;
  border-radius: 8px;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.chat-send-btn:hover:not(:disabled) {
  background: #d97706;
}

.chat-send-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Transitions */
.chat-enter-active,
.chat-leave-active {
  transition: all 0.3s ease;
}

.chat-enter-from,
.chat-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}
</style>
