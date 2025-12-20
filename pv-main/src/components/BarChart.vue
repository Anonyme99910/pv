<template>
  <div>
    <canvas ref="chartRef"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  BarController,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  BarController,
  Title,
  Tooltip,
  Legend
)

const props = defineProps({
  data: Object,
  height: {
    type: Number,
    default: 300
  }
})

const chartRef = ref(null)
let chartInstance = null

const createChart = () => {
  if (!chartRef.value || !props.data) return

  const ctx = chartRef.value.getContext('2d')
  
  if (chartInstance) {
    chartInstance.destroy()
  }

  const isDark = document.documentElement.classList.contains('dark')
  
  chartInstance = new ChartJS(ctx, {
    type: 'bar',
    data: props.data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: isDark ? '#1F2937' : '#FFFFFF',
          titleColor: isDark ? '#F3F4F6' : '#111827',
          bodyColor: isDark ? '#E5E7EB' : '#374151',
          borderColor: isDark ? '#374151' : '#E5E7EB',
          borderWidth: 1
        }
      },
      scales: {
        x: {
          grid: {
            display: false
          },
          ticks: {
            color: isDark ? '#9CA3AF' : '#6B7280',
            maxRotation: 45,
            minRotation: 45
          }
        },
        y: {
          grid: {
            color: isDark ? '#374151' : '#F3F4F6',
            drawBorder: false
          },
          ticks: {
            color: isDark ? '#9CA3AF' : '#6B7280'
          }
        }
      }
    }
  })
}

// Watch for theme changes
const observer = new MutationObserver(() => {
  createChart()
})

onMounted(() => {
  createChart()
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
  })
})

watch(() => props.data, () => {
  createChart()
}, { deep: true })

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
  observer.disconnect()
})
</script>
