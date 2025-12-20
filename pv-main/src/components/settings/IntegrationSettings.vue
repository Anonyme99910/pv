<template>
  <div class="integration-settings">
    <!-- SMTP Settings -->
    <div class="settings-section">
      <div class="section-header">
        <div class="flex items-center gap-3">
          <div class="icon-box bg-blue-500/20">
            <Mail class="w-5 h-5 text-blue-400" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-white">Email (SMTP) Settings</h3>
            <p class="text-sm text-gray-400">Configure email notifications for alerts</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="smtpStatus === 'connected'" class="status-badge success">
            <CheckCircle class="w-4 h-4" /> Connected
          </span>
          <span v-else-if="smtpStatus === 'error'" class="status-badge error">
            <XCircle class="w-4 h-4" /> Error
          </span>
        </div>
      </div>

      <div class="settings-form">
        <div class="form-grid">
          <div class="form-group">
            <label>SMTP Host</label>
            <input v-model="smtp.smtp_host" type="text" placeholder="smtp.gmail.com" />
          </div>
          <div class="form-group">
            <label>SMTP Port</label>
            <input v-model="smtp.smtp_port" type="number" placeholder="587" />
          </div>
          <div class="form-group">
            <label>Username</label>
            <input v-model="smtp.smtp_username" type="text" placeholder="your@email.com" />
          </div>
          <div class="form-group">
            <label>Password</label>
            <input v-model="smtp.smtp_password" type="password" placeholder="••••••••" />
          </div>
          <div class="form-group">
            <label>Encryption</label>
            <select v-model="smtp.smtp_encryption">
              <option value="tls">TLS</option>
              <option value="ssl">SSL</option>
              <option value="">None</option>
            </select>
          </div>
          <div class="form-group">
            <label>From Address</label>
            <input v-model="smtp.smtp_from_address" type="email" placeholder="noreply@soma.com" />
          </div>
          <div class="form-group">
            <label>From Name</label>
            <input v-model="smtp.smtp_from_name" type="text" placeholder="SOMA PV System" />
          </div>
          <div class="form-group">
            <label>Alert Recipients (comma separated)</label>
            <input v-model="smtp.alert_recipients" type="text" placeholder="admin@example.com, tech@example.com" />
          </div>
        </div>

        <div class="form-actions">
          <button @click="testSmtp" :disabled="testingSmtp" class="btn-secondary">
            <Loader2 v-if="testingSmtp" class="w-4 h-4 animate-spin" />
            <Zap v-else class="w-4 h-4" />
            Test Connection
          </button>
          <button @click="sendTestEmail" :disabled="sendingTest" class="btn-secondary">
            <Loader2 v-if="sendingTest" class="w-4 h-4 animate-spin" />
            <Send v-else class="w-4 h-4" />
            Send Test Email
          </button>
          <button @click="saveSmtp" :disabled="savingSmtp" class="btn-primary">
            <Loader2 v-if="savingSmtp" class="w-4 h-4 animate-spin" />
            <Save v-else class="w-4 h-4" />
            Save Settings
          </button>
        </div>
      </div>
    </div>

    <!-- AI Chat Settings -->
    <div class="settings-section">
      <div class="section-header">
        <div class="flex items-center gap-3">
          <div class="icon-box bg-purple-500/20">
            <Bot class="w-5 h-5 text-purple-400" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-white">AI Assistant Settings</h3>
            <p class="text-sm text-gray-400">Configure the SOMA AI chat assistant</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="aiStatus === 'connected'" class="status-badge success">
            <CheckCircle class="w-4 h-4" /> Connected
          </span>
          <span v-else-if="aiStatus === 'error'" class="status-badge error">
            <XCircle class="w-4 h-4" /> Error
          </span>
        </div>
      </div>

      <div class="settings-form">
        <div class="form-grid">
          <div class="form-group full-width">
            <label>API Key</label>
            <input v-model="ai.ai_chat_api_key" type="password" placeholder="sk-••••••••••••••••" />
            <span class="form-hint">Get your API key from the AI provider dashboard</span>
          </div>
          <div class="form-group">
            <label>Model</label>
            <select v-model="ai.ai_chat_model">
              <option value="deepseek-chat">Standard (Recommended)</option>
              <option value="deepseek-coder">Technical</option>
            </select>
          </div>
          <div class="form-group">
            <label>Enable AI Chat</label>
            <label class="toggle">
              <input type="checkbox" v-model="ai.ai_chat_enabled" />
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="form-actions">
          <button @click="testAi" :disabled="testingAi" class="btn-secondary">
            <Loader2 v-if="testingAi" class="w-4 h-4 animate-spin" />
            <Zap v-else class="w-4 h-4" />
            Test Connection
          </button>
          <button @click="saveAi" :disabled="savingAi" class="btn-primary">
            <Loader2 v-if="savingAi" class="w-4 h-4 animate-spin" />
            <Save v-else class="w-4 h-4" />
            Save Settings
          </button>
        </div>
      </div>
    </div>

    <!-- Weather API Settings -->
    <div class="settings-section">
      <div class="section-header">
        <div class="flex items-center gap-3">
          <div class="icon-box bg-cyan-500/20">
            <Cloud class="w-5 h-5 text-cyan-400" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-white">Weather API Settings</h3>
            <p class="text-sm text-gray-400">Configure weather data for solar impact analysis</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="weatherStatus === 'connected'" class="status-badge success">
            <CheckCircle class="w-4 h-4" /> Connected
          </span>
          <span v-else-if="weatherStatus === 'error'" class="status-badge error">
            <XCircle class="w-4 h-4" /> Error
          </span>
        </div>
      </div>

      <div class="settings-form">
        <div class="form-grid">
          <div class="form-group full-width">
            <label>WeatherAPI.com API Key</label>
            <input v-model="weather.weather_api_key" type="password" placeholder="••••••••••••••••" />
            <span class="form-hint">
              Get a free API key at <a href="https://www.weatherapi.com" target="_blank" class="text-amber-400 hover:underline">weatherapi.com</a>
            </span>
          </div>
          <div class="form-group">
            <label>Default Location</label>
            <input v-model="weather.weather_default_location" type="text" placeholder="auto:ip or city name" />
          </div>
          <div class="form-group">
            <label>Enable Weather Widget</label>
            <label class="toggle">
              <input type="checkbox" v-model="weather.weather_enabled" />
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="form-actions">
          <button @click="testWeather" :disabled="testingWeather" class="btn-secondary">
            <Loader2 v-if="testingWeather" class="w-4 h-4 animate-spin" />
            <Zap v-else class="w-4 h-4" />
            Test Connection
          </button>
          <button @click="saveWeather" :disabled="savingWeather" class="btn-primary">
            <Loader2 v-if="savingWeather" class="w-4 h-4 animate-spin" />
            <Save v-else class="w-4 h-4" />
            Save Settings
          </button>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <Transition name="toast">
      <div v-if="toast.show" :class="['toast', toast.type]">
        <CheckCircle v-if="toast.type === 'success'" class="w-5 h-5" />
        <AlertCircle v-else class="w-5 h-5" />
        <span>{{ toast.message }}</span>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { 
  Mail, Bot, Cloud, Save, Zap, Send, Loader2, 
  CheckCircle, XCircle, AlertCircle 
} from 'lucide-vue-next'
import { api } from '@/services/api'

// SMTP State
const smtp = ref({
  smtp_host: '',
  smtp_port: 587,
  smtp_username: '',
  smtp_password: '',
  smtp_encryption: 'tls',
  smtp_from_address: '',
  smtp_from_name: 'SOMA PV System',
  alert_recipients: '',
})
const smtpStatus = ref(null)
const testingSmtp = ref(false)
const sendingTest = ref(false)
const savingSmtp = ref(false)

// AI State
const ai = ref({
  ai_chat_api_key: '',
  ai_chat_model: 'deepseek-chat',
  ai_chat_enabled: true,
})
const aiStatus = ref(null)
const testingAi = ref(false)
const savingAi = ref(false)

// Weather State
const weather = ref({
  weather_api_key: '',
  weather_default_location: 'auto:ip',
  weather_enabled: true,
})
const weatherStatus = ref(null)
const testingWeather = ref(false)
const savingWeather = ref(false)

// Toast
const toast = ref({ show: false, message: '', type: 'success' })

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

// Load Settings
const loadSettings = async () => {
  try {
    const smtpRes = await api.getSmtpSettings()
    if (smtpRes.success && smtpRes.settings) {
      smtpRes.settings.forEach(s => {
        if (smtp.value.hasOwnProperty(s.key)) {
          smtp.value[s.key] = s.value === '********' ? '' : s.value
        }
      })
    }

    const aiRes = await api.getAiSettings()
    if (aiRes.success && aiRes.settings) {
      aiRes.settings.forEach(s => {
        if (ai.value.hasOwnProperty(s.key)) {
          ai.value[s.key] = s.value === '********' ? '' : s.value
        }
      })
    }

    const weatherRes = await api.getWeatherSettings()
    if (weatherRes.success && weatherRes.settings) {
      weatherRes.settings.forEach(s => {
        if (weather.value.hasOwnProperty(s.key)) {
          weather.value[s.key] = s.value === '********' ? '' : s.value
        }
      })
    }
  } catch (error) {
    console.error('Failed to load settings:', error)
  }
}

// SMTP Actions
const testSmtp = async () => {
  testingSmtp.value = true
  try {
    const res = await api.testSmtpConnection()
    smtpStatus.value = res.success ? 'connected' : 'error'
    showToast(res.message, res.success ? 'success' : 'error')
  } catch (error) {
    smtpStatus.value = 'error'
    showToast('Connection test failed', 'error')
  } finally {
    testingSmtp.value = false
  }
}

const sendTestEmail = async () => {
  const email = prompt('Enter email address for test:')
  if (!email) return

  sendingTest.value = true
  try {
    const res = await api.sendTestEmail(email)
    showToast(res.message, res.success ? 'success' : 'error')
  } catch (error) {
    showToast('Failed to send test email', 'error')
  } finally {
    sendingTest.value = false
  }
}

const saveSmtp = async () => {
  savingSmtp.value = true
  try {
    const res = await api.updateSmtpSettings(smtp.value)
    showToast(res.message || 'SMTP settings saved', res.success ? 'success' : 'error')
  } catch (error) {
    showToast('Failed to save settings', 'error')
  } finally {
    savingSmtp.value = false
  }
}

// AI Actions
const testAi = async () => {
  testingAi.value = true
  try {
    const res = await api.testAiConnection()
    aiStatus.value = res.success ? 'connected' : 'error'
    showToast(res.message, res.success ? 'success' : 'error')
  } catch (error) {
    aiStatus.value = 'error'
    showToast('Connection test failed', 'error')
  } finally {
    testingAi.value = false
  }
}

const saveAi = async () => {
  savingAi.value = true
  try {
    const res = await api.updateAiSettings(ai.value)
    showToast(res.message || 'AI settings saved', res.success ? 'success' : 'error')
  } catch (error) {
    showToast('Failed to save settings', 'error')
  } finally {
    savingAi.value = false
  }
}

// Weather Actions
const testWeather = async () => {
  testingWeather.value = true
  try {
    const res = await api.testWeatherConnection()
    weatherStatus.value = res.success ? 'connected' : 'error'
    showToast(res.message, res.success ? 'success' : 'error')
  } catch (error) {
    weatherStatus.value = 'error'
    showToast('Connection test failed', 'error')
  } finally {
    testingWeather.value = false
  }
}

const saveWeather = async () => {
  savingWeather.value = true
  try {
    const res = await api.updateWeatherSettings(weather.value)
    showToast(res.message || 'Weather settings saved', res.success ? 'success' : 'error')
  } catch (error) {
    showToast('Failed to save settings', 'error')
  } finally {
    savingWeather.value = false
  }
}

onMounted(() => {
  loadSettings()
})
</script>

<style scoped>
.integration-settings {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.settings-section {
  background: #1f2937;
  border-radius: 12px;
  border: 1px solid #374151;
  overflow: hidden;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background: #111827;
  border-bottom: 1px solid #374151;
}

.icon-box {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.success {
  background: rgba(16, 185, 129, 0.2);
  color: #10b981;
}

.status-badge.error {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
}

.settings-form {
  padding: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group.full-width {
  grid-column: span 2;
}

.form-group label {
  font-size: 13px;
  font-weight: 500;
  color: #9ca3af;
}

.form-group input,
.form-group select {
  padding: 10px 14px;
  background: #374151;
  border: 1px solid #4b5563;
  border-radius: 8px;
  color: white;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #f59e0b;
}

.form-group input::placeholder {
  color: #6b7280;
}

.form-hint {
  font-size: 11px;
  color: #6b7280;
}

.toggle {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 26px;
  cursor: pointer;
}

.toggle input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: #374151;
  border-radius: 26px;
  transition: 0.3s;
}

.toggle-slider::before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 3px;
  bottom: 3px;
  background: white;
  border-radius: 50%;
  transition: 0.3s;
}

.toggle input:checked + .toggle-slider {
  background: #f59e0b;
}

.toggle input:checked + .toggle-slider::before {
  transform: translateX(22px);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #374151;
}

.btn-primary,
.btn-secondary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: #f59e0b;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #d97706;
}

.btn-secondary {
  background: #374151;
  color: #d1d5db;
  border: 1px solid #4b5563;
}

.btn-secondary:hover:not(:disabled) {
  background: #4b5563;
}

.btn-primary:disabled,
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  z-index: 1000;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.toast.success {
  background: #065f46;
  color: #d1fae5;
}

.toast.error {
  background: #991b1b;
  color: #fee2e2;
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .form-group.full-width {
    grid-column: span 1;
  }
}
</style>
