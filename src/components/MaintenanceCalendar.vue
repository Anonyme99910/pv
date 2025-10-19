<template>
  <div>
    <!-- Calendar Header -->
    <div class="flex items-center justify-between mb-4">
      <button
        @click="previousMonth"
        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
      >
        <ChevronLeft class="w-5 h-5" />
      </button>
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ currentMonthYear }}
      </h3>
      <button
        @click="nextMonth"
        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
      >
        <ChevronRight class="w-5 h-5" />
      </button>
    </div>

    <!-- Calendar Grid -->
    <div class="grid grid-cols-7 gap-2">
      <!-- Day Headers -->
      <div
        v-for="day in dayHeaders"
        :key="day"
        class="text-center text-sm font-medium text-gray-600 dark:text-gray-400 py-2"
      >
        {{ day }}
      </div>

      <!-- Calendar Days -->
      <div
        v-for="day in calendarDays"
        :key="day.date"
        class="aspect-square p-2 rounded-lg border transition-colors cursor-pointer"
        :class="dayClass(day)"
        @click="selectDay(day)"
      >
        <div class="text-sm font-medium mb-1">{{ day.day }}</div>
        <div v-if="day.taskCount > 0" class="flex items-center justify-center">
          <span class="text-xs px-2 py-0.5 bg-primary text-white rounded-full">
            {{ day.taskCount }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { 
  format, 
  startOfMonth, 
  endOfMonth, 
  eachDayOfInterval, 
  isSameMonth,
  isSameDay,
  addMonths,
  subMonths,
  startOfWeek,
  endOfWeek
} from 'date-fns'
import { enUS, fr } from 'date-fns/locale'

const props = defineProps({
  tasks: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['select-date'])

const { locale } = useI18n()
const currentDate = ref(new Date())

const currentMonthYear = computed(() => {
  const localeMap = { en: enUS, fr }
  return format(currentDate.value, 'MMMM yyyy', { locale: localeMap[locale.value] })
})

const dayHeaders = computed(() => {
  const localeMap = { en: enUS, fr }
  const start = startOfWeek(new Date(), { locale: localeMap[locale.value] })
  return Array.from({ length: 7 }, (_, i) => {
    const day = new Date(start)
    day.setDate(start.getDate() + i)
    return format(day, 'EEE', { locale: localeMap[locale.value] })
  })
})

const calendarDays = computed(() => {
  const localeMap = { en: enUS, fr }
  const monthStart = startOfMonth(currentDate.value)
  const monthEnd = endOfMonth(currentDate.value)
  const calendarStart = startOfWeek(monthStart, { locale: localeMap[locale.value] })
  const calendarEnd = endOfWeek(monthEnd, { locale: localeMap[locale.value] })
  
  const days = eachDayOfInterval({ start: calendarStart, end: calendarEnd })
  
  return days.map(day => {
    const dateStr = format(day, 'yyyy-MM-dd')
    const taskCount = (props.tasks || []).filter(task => {
      return format(new Date(task.date), 'yyyy-MM-dd') === dateStr
    }).length
    
    return {
      date: dateStr,
      day: format(day, 'd'),
      isCurrentMonth: isSameMonth(day, currentDate.value),
      isToday: isSameDay(day, new Date()),
      taskCount
    }
  })
})

const dayClass = (day) => {
  const classes = []
  
  if (!day.isCurrentMonth) {
    classes.push('text-gray-400 dark:text-gray-600 border-gray-200 dark:border-gray-700')
  } else {
    classes.push('border-gray-300 dark:border-gray-600 hover:border-primary hover:bg-primary-50 dark:hover:bg-primary-900/20')
  }
  
  if (day.isToday) {
    classes.push('bg-primary-100 dark:bg-primary-900/30 border-primary')
  }
  
  return classes.join(' ')
}

const selectDay = (day) => {
  if (day.isCurrentMonth) {
    emit('select-date', day.date)
  }
}

const previousMonth = () => {
  currentDate.value = subMonths(currentDate.value, 1)
}

const nextMonth = () => {
  currentDate.value = addMonths(currentDate.value, 1)
}
</script>
