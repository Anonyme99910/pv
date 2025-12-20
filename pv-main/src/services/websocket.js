// WebSocket/Real-time service for Laravel Echo + Redis
import { ref, onMounted, onUnmounted } from 'vue'

const WS_URL = import.meta.env.VITE_WS_URL || 'ws://localhost:6001'
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'

// Event bus for real-time updates
const eventListeners = new Map()

// Connection state
export const isConnected = ref(false)
export const connectionError = ref(null)

let socket = null
let reconnectAttempts = 0
const maxReconnectAttempts = 5
const reconnectDelay = 3000

/**
 * Initialize WebSocket connection
 */
export function initWebSocket() {
  if (socket && socket.readyState === WebSocket.OPEN) {
    return
  }

  try {
    socket = new WebSocket(WS_URL)

    socket.onopen = () => {
      console.log('WebSocket connected')
      isConnected.value = true
      connectionError.value = null
      reconnectAttempts = 0

      // Subscribe to channels
      subscribeToChannels()
    }

    socket.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data)
        handleMessage(data)
      } catch (e) {
        console.error('Failed to parse WebSocket message:', e)
      }
    }

    socket.onclose = () => {
      console.log('WebSocket disconnected')
      isConnected.value = false
      attemptReconnect()
    }

    socket.onerror = (error) => {
      console.error('WebSocket error:', error)
      connectionError.value = 'Connection error'
    }
  } catch (error) {
    console.error('Failed to initialize WebSocket:', error)
    connectionError.value = error.message
  }
}

/**
 * Attempt to reconnect
 */
function attemptReconnect() {
  if (reconnectAttempts < maxReconnectAttempts) {
    reconnectAttempts++
    console.log(`Attempting to reconnect (${reconnectAttempts}/${maxReconnectAttempts})...`)
    setTimeout(initWebSocket, reconnectDelay)
  } else {
    connectionError.value = 'Max reconnection attempts reached'
  }
}

/**
 * Subscribe to channels
 */
function subscribeToChannels() {
  if (!socket || socket.readyState !== WebSocket.OPEN) return

  // Subscribe to sensor data channel
  socket.send(JSON.stringify({
    event: 'subscribe',
    channel: 'sensors'
  }))

  // Subscribe to faults channel
  socket.send(JSON.stringify({
    event: 'subscribe',
    channel: 'faults'
  }))

  // Subscribe to alerts channel
  socket.send(JSON.stringify({
    event: 'subscribe',
    channel: 'alerts'
  }))
}

/**
 * Handle incoming messages
 */
function handleMessage(data) {
  const { event, channel, payload } = data

  // Emit to listeners
  const key = `${channel}:${event}`
  const listeners = eventListeners.get(key) || []
  listeners.forEach(callback => callback(payload))

  // Also emit to general listeners
  const generalListeners = eventListeners.get(event) || []
  generalListeners.forEach(callback => callback(payload))
}

/**
 * Subscribe to an event
 */
export function on(event, callback) {
  if (!eventListeners.has(event)) {
    eventListeners.set(event, [])
  }
  eventListeners.get(event).push(callback)

  // Return unsubscribe function
  return () => {
    const listeners = eventListeners.get(event)
    const index = listeners.indexOf(callback)
    if (index > -1) {
      listeners.splice(index, 1)
    }
  }
}

/**
 * Unsubscribe from an event
 */
export function off(event, callback) {
  if (!eventListeners.has(event)) return
  
  const listeners = eventListeners.get(event)
  const index = listeners.indexOf(callback)
  if (index > -1) {
    listeners.splice(index, 1)
  }
}

/**
 * Close WebSocket connection
 */
export function closeWebSocket() {
  if (socket) {
    socket.close()
    socket = null
  }
}

/**
 * Composable for using real-time updates in components
 */
export function useRealtime() {
  const sensorData = ref(null)
  const latestFault = ref(null)
  const latestAlert = ref(null)
  const unsubscribers = []

  onMounted(() => {
    initWebSocket()

    // Listen for sensor data
    unsubscribers.push(on('sensor.data', (data) => {
      sensorData.value = data
    }))

    // Listen for faults
    unsubscribers.push(on('fault.detected', (data) => {
      latestFault.value = data
    }))

    // Listen for alerts
    unsubscribers.push(on('alert.created', (data) => {
      latestAlert.value = data
    }))
  })

  onUnmounted(() => {
    unsubscribers.forEach(unsub => unsub())
  })

  return {
    isConnected,
    connectionError,
    sensorData,
    latestFault,
    latestAlert,
  }
}

/**
 * Polling fallback for when WebSocket is not available
 */
export function usePolling(fetchFunction, interval = 5000) {
  const data = ref(null)
  const loading = ref(false)
  const error = ref(null)
  let intervalId = null

  const fetch = async () => {
    loading.value = true
    try {
      data.value = await fetchFunction()
      error.value = null
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  const start = () => {
    fetch()
    intervalId = setInterval(fetch, interval)
  }

  const stop = () => {
    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
  }

  onMounted(start)
  onUnmounted(stop)

  return { data, loading, error, fetch, start, stop }
}

export default {
  initWebSocket,
  closeWebSocket,
  on,
  off,
  useRealtime,
  usePolling,
  isConnected,
  connectionError,
}
