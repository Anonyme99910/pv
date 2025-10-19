<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ t('maintenance.title') }}</h1>
      </div>
      <button
        v-if="canEdit"
        @click="showAddForm = true"
        class="btn-primary flex items-center space-x-2"
      >
        <Plus class="w-5 h-5" />
        <span>{{ t('maintenance.form.title') }}</span>
      </button>
    </div>

    <!-- Progress Tracker -->
    <div class="card p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        {{ t('maintenance.progress.title') }}
      </h3>
      <div class="flex items-center space-x-8">
        <div class="relative w-32 h-32">
          <svg class="w-32 h-32 transform -rotate-90">
            <circle
              cx="64"
              cy="64"
              r="56"
              stroke="currentColor"
              stroke-width="8"
              fill="none"
              class="text-gray-200 dark:text-gray-700"
            />
            <circle
              cx="64"
              cy="64"
              r="56"
              stroke="currentColor"
              stroke-width="8"
              fill="none"
              class="text-primary"
              :stroke-dasharray="circumference"
              :stroke-dashoffset="progressOffset"
              stroke-linecap="round"
            />
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <span class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ completionPercentage }}%
            </span>
          </div>
        </div>
        <div class="flex-1 grid grid-cols-3 gap-4">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('maintenance.progress.completed') }}</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ completedTasks }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('maintenance.status.inProgress') }}</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ inProgressTasks }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('maintenance.progress.total') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalTasks }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Calendar View -->
    <div class="card p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        {{ t('maintenance.calendar') }}
      </h3>
      <MaintenanceCalendar :tasks="tasks" @select-date="selectDate" />
    </div>

    <!-- Maintenance Table -->
    <div class="card overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ t('maintenance.table.title') }}
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ t('maintenance.table.date') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Equipment ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ t('maintenance.table.taskType') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ t('maintenance.table.technician') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ t('maintenance.table.status') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ t('maintenance.table.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="task in filteredTasks"
              :key="task.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                {{ formatDate(task.date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                {{ task.equipmentId }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                {{ t(`maintenance.taskTypes.${task.taskType}`) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                {{ task.technician }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="badge" :class="statusBadgeClass(task.status)">
                  {{ t(`maintenance.status.${task.status}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                <button
                  v-if="canEdit"
                  @click="editTask(task)"
                  class="text-primary hover:text-primary-600 font-medium"
                >
                  {{ t('common.edit') }}
                </button>
                <button
                  v-if="canDelete"
                  @click="deleteTask(task.id)"
                  class="text-red-600 hover:text-red-700 font-medium"
                >
                  {{ t('common.delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Maintenance Form Modal -->
    <div
      v-if="showAddForm"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999] p-4 overflow-y-auto"
      @click="closeForm"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-2xl w-full p-6 my-8"
        @click.stop
      >
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
          {{ editingTask ? t('common.edit') : t('maintenance.form.title') }}
        </h2>
        <form @submit.prevent="submitForm" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('maintenance.form.date') }}
              </label>
              <input
                v-model="formData.date"
                type="date"
                required
                class="input-field"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('maintenance.form.equipmentId') }}
              </label>
              <input
                v-model="formData.equipmentId"
                type="text"
                required
                placeholder="PV-001"
                class="input-field"
              />
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ t('maintenance.form.description') }}
            </label>
            <textarea
              v-model="formData.description"
              rows="3"
              required
              class="input-field"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('maintenance.form.taskType') }}
              </label>
              <select v-model="formData.taskType" required class="input-field">
                <option value="cleaning">{{ t('maintenance.taskTypes.cleaning') }}</option>
                <option value="inspection">{{ t('maintenance.taskTypes.inspection') }}</option>
                <option value="repair">{{ t('maintenance.taskTypes.repair') }}</option>
                <option value="replacement">{{ t('maintenance.taskTypes.replacement') }}</option>
                <option value="calibration">{{ t('maintenance.taskTypes.calibration') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('maintenance.form.assignedTo') }}
              </label>
              <input
                v-model="formData.technician"
                type="text"
                required
                class="input-field"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ t('maintenance.form.status') }}
            </label>
            <select v-model="formData.status" required class="input-field">
              <option value="scheduled">{{ t('maintenance.status.scheduled') }}</option>
              <option value="inProgress">{{ t('maintenance.status.inProgress') }}</option>
              <option value="completed">{{ t('maintenance.status.completed') }}</option>
              <option value="cancelled">{{ t('maintenance.status.cancelled') }}</option>
            </select>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="closeForm"
              class="btn-secondary"
            >
              {{ t('maintenance.form.cancel') }}
            </button>
            <button
              type="submit"
              class="btn-primary"
            >
              {{ editingTask ? t('common.save') : t('maintenance.form.submit') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { Plus } from 'lucide-vue-next'
import { format } from 'date-fns'
import { enUS, fr } from 'date-fns/locale'
import { mockAPI, useAutoRefresh } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import MaintenanceCalendar from '@/components/MaintenanceCalendar.vue'

const { t, locale } = useI18n()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

const canEdit = computed(() => authStore.canEdit)
const canDelete = computed(() => authStore.canDelete)

const showAddForm = ref(false)
const editingTask = ref(null)
const selectedDate = ref(null)

const formData = ref({
  date: '',
  equipmentId: '',
  description: '',
  taskType: 'cleaning',
  technician: '',
  status: 'scheduled'
})

const { data: tasks, fetch } = useAutoRefresh(
  mockAPI.getMaintenance,
  15000
)

onMounted(() => {
  fetch() // Load data once, no auto-refresh
})

const filteredTasks = computed(() => {
  let result = tasks.value || []
  if (selectedDate.value) {
    result = result.filter(task => {
      const taskDate = format(new Date(task.date), 'yyyy-MM-dd')
      return taskDate === selectedDate.value
    })
  }
  return result.sort((a, b) => new Date(a.date) - new Date(b.date))
})

const totalTasks = computed(() => (tasks.value || []).length)
const completedTasks = computed(() => (tasks.value || []).filter(t => t.status === 'completed').length)
const inProgressTasks = computed(() => (tasks.value || []).filter(t => t.status === 'inProgress').length)
const completionPercentage = computed(() => {
  if (totalTasks.value === 0) return 0
  return Math.round((completedTasks.value / totalTasks.value) * 100)
})

const circumference = 2 * Math.PI * 56
const progressOffset = computed(() => {
  return circumference - (completionPercentage.value / 100) * circumference
})

const formatDate = (dateString) => {
  const localeMap = { en: enUS, fr }
  return format(new Date(dateString), 'MMM dd, yyyy', { locale: localeMap[locale.value] })
}

const statusBadgeClass = (status) => {
  const classes = {
    scheduled: 'badge-info',
    inProgress: 'badge-warning',
    completed: 'badge-success',
    cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  return classes[status] || 'badge-info'
}

const selectDate = (date) => {
  selectedDate.value = date
}

const editTask = (task) => {
  editingTask.value = task
  formData.value = {
    date: format(new Date(task.date), 'yyyy-MM-dd'),
    equipmentId: task.equipmentId,
    description: task.description,
    taskType: task.taskType,
    technician: task.technician,
    status: task.status
  }
  showAddForm.value = true
}

const deleteTask = (taskId) => {
  if (confirm(t('common.confirm'))) {
    notificationStore.showToast('Task deleted successfully', 'success')
  }
}

const submitForm = async () => {
  try {
    await mockAPI.addMaintenance(formData.value)
    notificationStore.showToast(
      editingTask.value ? 'Task updated successfully' : 'Task added successfully',
      'success'
    )
    closeForm()
    fetch() // Reload data once
  } catch (error) {
    notificationStore.showToast('Failed to save task', 'error')
  }
}

const closeForm = () => {
  showAddForm.value = false
  editingTask.value = null
  formData.value = {
    date: '',
    equipmentId: '',
    description: '',
    taskType: 'cleaning',
    technician: '',
    status: 'scheduled'
  }
}
</script>
