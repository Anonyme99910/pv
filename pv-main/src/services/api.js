// Real API service connecting to Laravel backend
import axios from 'axios'
import { ref } from 'vue'

// API Configuration - matches XAMPP localhost/pv setup
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost/pv/pv-backend/public/api'
const AI_SERVICE_URL = import.meta.env.VITE_AI_SERVICE_URL || 'http://localhost:8001'

// Create axios instance
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
  timeout: 10000, // 10 second timeout to prevent blocking
})

// Request interceptor to add auth token
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor for error handling
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error('API Error:', error.response?.status, error.response?.data)
    if (error.response?.status === 401) {
      // Only redirect if not already on login page
      if (!window.location.pathname.includes('/login')) {
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user')
        // Get current locale from URL or default to 'en'
        const pathParts = window.location.pathname.split('/')
        const locale = pathParts[4] || 'en'
        window.location.href = `/pv/pv-main/dist/${locale}/login`
      }
    }
    return Promise.reject(error)
  }
)

// API service
export const api = {
  // Authentication
  async login(email, password) {
    const response = await apiClient.post('/login', { email, password })
    const { user, token } = response.data
    localStorage.setItem('auth_token', token)
    localStorage.setItem('user', JSON.stringify(user))
    return response.data
  },

  async logout() {
    try {
      await apiClient.post('/logout')
    } finally {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
    }
  },

  async getUser() {
    const response = await apiClient.get('/user')
    return response.data
  },

  async updateProfile(data) {
    const response = await apiClient.put('/user/profile', data)
    return response.data
  },

  async changePassword(data) {
    const response = await apiClient.put('/user/password', data)
    return response.data
  },

  // Dashboard
  async getLiveData() {
    const response = await apiClient.get('/dashboard')
    return response.data
  },

  // Panels
  async getPanels(params = {}) {
    const response = await apiClient.get('/panels', { params })
    return response.data
  },

  async getPanelGrid() {
    const response = await apiClient.get('/panels/grid')
    return response.data
  },

  async getPanel(id) {
    const response = await apiClient.get(`/panels/${id}`)
    return response.data
  },

  async createPanel(data) {
    const response = await apiClient.post('/panels', data)
    return response.data
  },

  async updatePanel(id, data) {
    const response = await apiClient.put(`/panels/${id}`, data)
    return response.data
  },

  async deletePanel(id) {
    const response = await apiClient.delete(`/panels/${id}`)
    return response.data
  },

  async getPanelReadings(panelId, params = {}) {
    const response = await apiClient.get(`/panels/${panelId}/readings`, { params })
    return response.data
  },

  // Faults
  async getFaults(filter = 'all') {
    try {
      const params = filter !== 'all' ? { status: filter } : {}
      const response = await apiClient.get('/faults', { params })
      // Handle Laravel pagination response
      return response.data.data || response.data || []
    } catch (error) {
      console.error('getFaults error:', error)
      return []
    }
  },

  async getFaultDetails(faultId) {
    const response = await apiClient.get(`/faults/${faultId}`)
    return response.data
  },

  async createFault(data) {
    const response = await apiClient.post('/faults', data)
    return response.data
  },

  async resolveFault(faultId, notes = null) {
    const response = await apiClient.post(`/faults/${faultId}/resolve`, { resolution_notes: notes })
    return response.data
  },

  async updateFaultStatus(faultId, status) {
    const response = await apiClient.patch(`/faults/${faultId}/status`, { status })
    return response.data
  },

  // Maintenance
  async getMaintenance(params = {}) {
    try {
      const response = await apiClient.get('/maintenance', { params })
      return response.data.data || response.data || []
    } catch (error) {
      console.error('getMaintenance error:', error)
      return []
    }
  },

  async getMaintenanceCalendar(month, year) {
    const response = await apiClient.get('/maintenance/calendar', { params: { month, year } })
    return response.data
  },

  async addMaintenance(data) {
    const response = await apiClient.post('/maintenance', data)
    return response.data
  },

  async updateMaintenance(id, data) {
    const response = await apiClient.put(`/maintenance/${id}`, data)
    return response.data
  },

  async startMaintenance(id) {
    const response = await apiClient.post(`/maintenance/${id}/start`)
    return response.data
  },

  async completeMaintenance(id, data = {}) {
    const response = await apiClient.post(`/maintenance/${id}/complete`, data)
    return response.data
  },

  async cancelMaintenance(id) {
    const response = await apiClient.post(`/maintenance/${id}/cancel`)
    return response.data
  },

  // Analytics
  async getAnalytics(timeRange = 'month') {
    const response = await apiClient.get('/analytics', { params: { timeRange } })
    return response.data
  },

  async getPanelAnalysis(limit = 20) {
    const response = await apiClient.get('/analytics/panels', { params: { limit } })
    return response.data
  },

  // Users
  async getUsers(params = {}) {
    const response = await apiClient.get('/users', { params })
    return response.data
  },

  async getTechnicians() {
    const response = await apiClient.get('/users/technicians')
    return response.data
  },

  async createUser(data) {
    const response = await apiClient.post('/users', data)
    return response.data
  },

  async updateUser(id, data) {
    const response = await apiClient.put(`/users/${id}`, data)
    return response.data
  },

  async deleteUser(id) {
    const response = await apiClient.delete(`/users/${id}`)
    return response.data
  },

  // Alerts
  async getAlerts(params = {}) {
    const response = await apiClient.get('/alerts', { params })
    return response.data
  },

  async getUnreadAlertCount() {
    const response = await apiClient.get('/alerts/unread-count')
    return response.data.count
  },

  async markAlertAsRead(id) {
    const response = await apiClient.post(`/alerts/${id}/read`)
    return response.data
  },

  async markAllAlertsAsRead() {
    const response = await apiClient.post('/alerts/read-all')
    return response.data
  },

  async dismissAlert(id) {
    const response = await apiClient.post(`/alerts/${id}/dismiss`)
    return response.data
  },

  // Thresholds
  async getThresholds() {
    const response = await apiClient.get('/thresholds')
    return response.data
  },

  async saveThresholds(thresholds) {
    const response = await apiClient.put('/thresholds', thresholds)
    return response.data
  },

  // Sensor Data (for IoT integration)
  async sendSensorData(data) {
    const response = await apiClient.post('/sensors/data', data)
    return response.data
  },

  async sendBatchSensorData(readings) {
    const response = await apiClient.post('/sensors/batch', { readings })
    return response.data
  },

  // Weather API
  async getWeather(location = 'auto:ip') {
    const response = await apiClient.get('/weather/current', { params: { location } })
    return response.data
  },

  async getWeatherByCoordinates(lat, lon) {
    const response = await apiClient.get('/weather/coordinates', { params: { lat, lon } })
    return response.data
  },

  async getWeatherForecast(location = 'auto:ip', days = 3) {
    const response = await apiClient.get('/weather/forecast', { params: { location, days } })
    return response.data
  },

  async searchWeatherLocations(query) {
    const response = await apiClient.get('/weather/search', { params: { q: query } })
    return response.data
  },

  async getSolarForecast(location = 'auto:ip') {
    const response = await apiClient.get('/weather/solar-forecast', { params: { location } })
    return response.data
  },

  // AI Chat
  async sendChatMessage(message, context = 'general', contextId = null) {
    const response = await apiClient.post('/chat/message', { message, context, context_id: contextId })
    return response.data
  },

  async getChatHistory(limit = 50) {
    const response = await apiClient.get('/chat/history', { params: { limit } })
    return response.data
  },

  async clearChatHistory() {
    const response = await apiClient.delete('/chat/history')
    return response.data
  },

  async getChatSuggestions() {
    const response = await apiClient.get('/chat/suggestions')
    return response.data
  },

  // System Settings (Admin)
  async getAllSettings() {
    const response = await apiClient.get('/settings')
    return response.data
  },

  async getSmtpSettings() {
    const response = await apiClient.get('/settings/smtp')
    return response.data
  },

  async updateSmtpSettings(settings) {
    const response = await apiClient.put('/settings/smtp', settings)
    return response.data
  },

  async testSmtpConnection() {
    const response = await apiClient.post('/settings/smtp/test')
    return response.data
  },

  async sendTestEmail(email) {
    const response = await apiClient.post('/settings/smtp/send-test', { email })
    return response.data
  },

  async getAiSettings() {
    const response = await apiClient.get('/settings/ai')
    return response.data
  },

  async updateAiSettings(settings) {
    const response = await apiClient.put('/settings/ai', settings)
    return response.data
  },

  async testAiConnection() {
    const response = await apiClient.post('/settings/ai/test')
    return response.data
  },

  async getWeatherSettings() {
    const response = await apiClient.get('/settings/weather')
    return response.data
  },

  async updateWeatherSettings(settings) {
    const response = await apiClient.put('/settings/weather', settings)
    return response.data
  },

  async testWeatherConnection() {
    const response = await apiClient.post('/settings/weather/test')
    return response.data
  },

  // Email Templates
  async getEmailTemplates() {
    const response = await apiClient.get('/settings/email-templates')
    return response.data
  },

  async updateEmailTemplate(templateId, data) {
    const response = await apiClient.put(`/settings/email-templates/${templateId}`, data)
    return response.data
  },

  // Thermal Detection (AI Service)
  async detectThermalDefects(file, panelCode = null) {
    const formData = new FormData()
    formData.append('file', file)
    if (panelCode) {
      formData.append('panel_code', panelCode)
    }
    const response = await axios.post(`${AI_SERVICE_URL}/thermal/detect`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  },

  async getThermalStatus() {
    const response = await axios.get(`${AI_SERVICE_URL}/thermal/status`)
    return response.data
  },

  async getThermalFaultTypes() {
    const response = await axios.get(`${AI_SERVICE_URL}/thermal/fault-types`)
    return response.data
  },

  // AI Service Health
  async getAIServiceHealth() {
    const response = await axios.get(`${AI_SERVICE_URL}/health`)
    return response.data
  },
}

// Export axios instance for custom requests
export { apiClient }

// Auto-refresh composable
export function useAutoRefresh(fetchFunction, interval = 5000) {
  const data = ref(null)
  const loading = ref(false)
  const error = ref(null)
  let intervalId = null

  const fetch = async () => {
    loading.value = true
    error.value = null
    try {
      data.value = await fetchFunction()
    } catch (e) {
      error.value = e.response?.data?.message || e.message
      console.error('API Error:', e)
    } finally {
      loading.value = false
    }
  }

  const startAutoRefresh = () => {
    fetch()
    intervalId = setInterval(fetch, interval)
  }

  const stopAutoRefresh = () => {
    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
  }

  return {
    data,
    loading,
    error,
    fetch,
    startAutoRefresh,
    stopAutoRefresh
  }
}
