// Mock API service with simulated data
import { ref } from 'vue'

// Simulate API delay
const delay = (ms = 300) => new Promise(resolve => setTimeout(resolve, ms))

// Generate random data helpers
const randomBetween = (min, max) => Math.random() * (max - min) + min
const randomInt = (min, max) => Math.floor(randomBetween(min, max))

// Mock data generators
export const mockAPI = {
  // Live dashboard data
  async getLiveData() {
    await delay()
    
    const now = new Date()
    const timeLabels = Array.from({ length: 24 }, (_, i) => {
      const hour = i
      return `${hour.toString().padStart(2, '0')}:00`
    })
    
    return {
      kpis: {
        totalPanels: 248,
        activePanels: 245,
        predictedFaults: randomInt(2, 8),
        avgEfficiency: randomBetween(85, 95).toFixed(1),
        nextMaintenance: new Date(now.getTime() + randomInt(1, 7) * 24 * 60 * 60 * 1000).toISOString()
      },
      powerOutput: {
        labels: timeLabels,
        data: timeLabels.map((_, i) => {
          // Simulate solar curve
          const hour = i
          if (hour < 6 || hour > 18) return 0
          const peak = 12
          const distance = Math.abs(hour - peak)
          return Math.max(0, 150 - distance * distance * 2 + randomBetween(-10, 10))
        })
      },
      trends: {
        labels: timeLabels,
        voltage: timeLabels.map(() => randomBetween(220, 240)),
        current: timeLabels.map(() => randomBetween(8, 12)),
        temperature: timeLabels.map(() => randomBetween(25, 45))
      },
      panelGrid: Array.from({ length: 248 }, (_, i) => ({
        id: `PV-${(i + 1).toString().padStart(3, '0')}`,
        status: Math.random() > 0.9 ? (Math.random() > 0.5 ? 'warning' : 'fault') : 'healthy',
        efficiency: randomBetween(80, 98),
        temperature: randomBetween(25, 50),
        voltage: randomBetween(220, 240)
      })),
      alerts: [
        {
          id: 1,
          type: 'warning',
          message: 'Temperature anomaly detected in Panel PV-045',
          timestamp: new Date(now.getTime() - randomInt(1, 60) * 60000).toISOString()
        },
        {
          id: 2,
          type: 'info',
          message: 'Maintenance completed for Panel PV-123',
          timestamp: new Date(now.getTime() - randomInt(60, 180) * 60000).toISOString()
        },
        {
          id: 3,
          type: 'critical',
          message: 'Voltage drop detected in Panel PV-089',
          timestamp: new Date(now.getTime() - randomInt(180, 360) * 60000).toISOString()
        }
      ]
    }
  },

  // Fault detection data
  async getFaults(filter = 'all') {
    await delay()
    
    const faultTypes = [
      'voltage_drop',
      'temperature_anomaly',
      'dust_accumulation',
      'current_fluctuation',
      'efficiency_degradation',
      'connection_issue'
    ]
    
    const statuses = ['active', 'resolved', 'critical', 'investigating']
    
    const allFaults = Array.from({ length: 45 }, (_, i) => {
      const detectedDate = new Date(Date.now() - randomInt(0, 30) * 24 * 60 * 60 * 1000)
      const status = statuses[randomInt(0, statuses.length)]
      
      return {
        id: i + 1,
        panelId: `PV-${randomInt(1, 248).toString().padStart(3, '0')}`,
        faultType: faultTypes[randomInt(0, faultTypes.length)],
        confidence: randomBetween(75, 99).toFixed(1),
        detectedOn: detectedDate.toISOString(),
        status: status,
        severity: status === 'critical' ? 'high' : (Math.random() > 0.5 ? 'medium' : 'low')
      }
    })
    
    if (filter === 'all') return allFaults
    return allFaults.filter(f => f.status === filter)
  },

  async getFaultDetails(faultId) {
    await delay()
    
    const timeLabels = Array.from({ length: 48 }, (_, i) => `${i}h`)
    
    return {
      id: faultId,
      panelId: `PV-${randomInt(1, 248).toString().padStart(3, '0')}`,
      faultType: 'temperature_anomaly',
      confidence: 94.5,
      detectedOn: new Date(Date.now() - randomInt(1, 10) * 24 * 60 * 60 * 1000).toISOString(),
      status: 'active',
      sensorData: {
        labels: timeLabels,
        voltage: timeLabels.map(() => randomBetween(215, 240)),
        temperature: timeLabels.map((_, i) => {
          // Simulate temperature spike
          if (i > 30 && i < 40) return randomBetween(55, 70)
          return randomBetween(25, 45)
        }),
        dust: timeLabels.map(() => randomBetween(5, 25))
      },
      aiAnalysis: {
        rootCause: 'Elevated temperature detected due to dust accumulation blocking heat dissipation. Panel efficiency has degraded by 12% over the past 48 hours.',
        contributingFactors: [
          'Dust accumulation level exceeds 20% threshold',
          'Ambient temperature spike during midday hours',
          'Reduced airflow around panel mounting area'
        ]
      },
      suggestedActions: [
        {
          priority: 'high',
          action: 'Schedule immediate cleaning of panel surface',
          estimatedTime: '30 minutes'
        },
        {
          priority: 'medium',
          action: 'Inspect mounting structure for airflow obstructions',
          estimatedTime: '15 minutes'
        },
        {
          priority: 'low',
          action: 'Review cleaning schedule frequency for this zone',
          estimatedTime: '5 minutes'
        }
      ]
    }
  },

  // Maintenance data
  async getMaintenance() {
    await delay()
    
    const taskTypes = ['cleaning', 'inspection', 'repair', 'replacement', 'calibration']
    const statuses = ['scheduled', 'inProgress', 'completed', 'cancelled']
    const technicians = ['Ahmed Ben Ali', 'Fatima Zahra', 'Mohamed Amine', 'Sarah Mansour', 'Karim Slimani']
    
    return Array.from({ length: 30 }, (_, i) => {
      const date = new Date(Date.now() + (i - 15) * 24 * 60 * 60 * 1000)
      
      return {
        id: i + 1,
        date: date.toISOString(),
        equipmentId: `PV-${randomInt(1, 248).toString().padStart(3, '0')}`,
        taskType: taskTypes[randomInt(0, taskTypes.length)],
        description: `Routine ${taskTypes[randomInt(0, taskTypes.length)]} maintenance`,
        technician: technicians[randomInt(0, technicians.length)],
        status: statuses[randomInt(0, statuses.length)],
        duration: randomInt(30, 180)
      }
    })
  },

  async addMaintenance(data) {
    await delay()
    return {
      success: true,
      id: Date.now(),
      ...data
    }
  },

  // Analytics data
  async getAnalytics(timeRange = 'month') {
    await delay()
    
    const getLabels = (range) => {
      switch (range) {
        case 'day':
          return Array.from({ length: 24 }, (_, i) => `${i}:00`)
        case 'week':
          return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
        case 'month':
          return Array.from({ length: 30 }, (_, i) => `Day ${i + 1}`)
        case 'year':
          return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        default:
          return []
      }
    }
    
    const labels = getLabels(timeRange)
    
    return {
      efficiencyTrend: {
        labels,
        before: labels.map(() => randomBetween(75, 85)),
        after: labels.map(() => randomBetween(88, 96))
      },
      dustAccumulation: {
        labels,
        data: labels.map((_, i) => Math.min(30, i * 0.5 + randomBetween(0, 3)))
      },
      tempVsOutput: {
        labels: Array.from({ length: 50 }, (_, i) => i),
        temperature: Array.from({ length: 50 }, () => randomBetween(20, 50)),
        output: Array.from({ length: 50 }, (_, i) => {
          const temp = 20 + i * 0.6
          // Output decreases with high temperature
          return Math.max(50, 150 - (temp - 25) * 2 + randomBetween(-10, 10))
        })
      },
      predictedRUL: {
        labels: Array.from({ length: 248 }, (_, i) => `PV-${(i + 1).toString().padStart(3, '0')}`),
        data: Array.from({ length: 248 }, () => randomInt(180, 1800))
      },
      insights: {
        avgImprovement: randomBetween(8, 15).toFixed(1),
        criticalPanels: randomInt(3, 12),
        maintenanceImpact: randomBetween(85, 98).toFixed(1)
      }
    }
  },

  // Settings data
  async getUsers() {
    await delay()
    
    const roles = ['admin', 'technician', 'viewer']
    const statuses = ['active', 'inactive']
    
    return [
      { id: 1, name: 'John Doe', email: 'john.doe@soma.com', role: 'admin', status: 'active' },
      { id: 2, name: 'Ahmed Ben Ali', email: 'ahmed.benali@soma.com', role: 'technician', status: 'active' },
      { id: 3, name: 'Fatima Zahra', email: 'fatima.zahra@soma.com', role: 'technician', status: 'active' },
      { id: 4, name: 'Sarah Mansour', email: 'sarah.mansour@soma.com', role: 'viewer', status: 'active' },
      { id: 5, name: 'Mohamed Amine', email: 'mohamed.amine@soma.com', role: 'technician', status: 'inactive' }
    ]
  },

  async getThresholds() {
    await delay()
    
    return {
      voltage: { min: 215, max: 245 },
      temperature: { max: 55 },
      efficiency: { min: 80 },
      dust: { max: 20 }
    }
  },

  async saveThresholds(thresholds) {
    await delay()
    return { success: true, thresholds }
  },

  async getApiConfig() {
    await delay()
    
    return {
      apiKey: 'sk_live_' + Math.random().toString(36).substring(2, 15),
      endpoint: 'https://api.soma-pv.com/v1'
    }
  }
}

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
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  const startAutoRefresh = () => {
    fetch() // Initial fetch
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
