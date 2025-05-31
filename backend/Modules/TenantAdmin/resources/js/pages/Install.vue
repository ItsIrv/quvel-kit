<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useInstallationService } from '../composables/useServices'
import type { InstallationRequest, ApiError } from '../types'
import { isValidationError, getValidationErrors, getUserFriendlyMessage, logError } from '../utils/errorHandler'

// Get the installation service
const installationService = useInstallationService()

// Form data
const form = reactive<InstallationRequest>({
  username: '',
  password: '',
  password_confirmation: '',
  installation_method: 'database'
})

// UI state
const loading = ref(false)
const checkingStatus = ref(true)
const errors = ref<Record<string, string[]>>({})
const message = ref('')
const messageType = ref<'success' | 'error'>('success')

// Check installation status on mount
onMounted(async () => {
  try {
    const isInstalled = await installationService.isInstalled()
    if (isInstalled) {
      // Redirect to login if already installed - don't show form
      window.location.href = '/admin/tenants/login'
      return // Don't set checkingStatus to false to prevent flicker
    }
  } catch (error) {
    console.error('Failed to check installation status:', error)
    // Continue to show installation form even if status check fails
  }
  
  // Only set checkingStatus to false if we're not redirecting
  checkingStatus.value = false
})

// Check if form is valid
const isFormValid = computed(() => {
  return form.username.length >= 3 &&
    form.password.length >= 8 &&
    form.password === form.password_confirmation &&
    !loading.value
})

// Submit installation
const submitInstallation = async () => {
  if (!isFormValid.value) return
  
  loading.value = true
  errors.value = {}
  message.value = ''

  try {
    const response = await installationService.install(form)
    
    if (response.success) {
      message.value = response.message || 'Installation successful! Redirecting...'
      messageType.value = 'success'
      
      // Redirect after a short delay
      setTimeout(() => {
        window.location.href = response.redirect_url || '/admin/tenants/login'
      }, 2000)
    }
  } catch (error) {
    const apiError = error as ApiError
    
    // Log error for debugging
    logError(apiError, 'Installation submission')
    
    if (isValidationError(apiError)) {
      // Validation errors
      errors.value = getValidationErrors(apiError)
      message.value = apiError.message || 'Please fix the errors below.'
    } else {
      // Other errors
      message.value = getUserFriendlyMessage(apiError)
    }
    messageType.value = 'error'
  } finally {
    loading.value = false
  }
}

// Clear specific field error
const clearError = (field: string) => {
  if (errors.value[field]) {
    delete errors.value[field]
  }
}
</script>

<template>
  <div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <!-- Loading state while checking installation status -->
    <div v-if="checkingStatus" class="max-w-md w-full text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-600">Checking installation status...</p>
    </div>

    <!-- Installation form (show after status check) -->
    <div v-else class="max-w-md w-full space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h1 class="text-3xl font-extrabold text-gray-900">
          TenantAdmin Installation
        </h1>
        <p class="mt-2 text-sm text-gray-600">
          Set up your tenant administration system
        </p>
      </div>

      <!-- Installation Form -->
      <div class="bg-white shadow rounded-lg p-6">
        <form @submit.prevent="submitInstallation" class="space-y-6">
          <!-- Success Message -->
          <div v-if="message && messageType === 'success'" class="rounded-md bg-green-50 p-4">
            <p class="text-sm font-medium text-green-800">{{ message }}</p>
          </div>

          <!-- Error Message -->
          <div v-if="message && messageType === 'error'" class="rounded-md bg-red-50 p-4">
            <p class="text-sm font-medium text-red-800">{{ message }}</p>
          </div>

          <!-- Installation Method -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Installation Method
            </label>
            <div class="grid grid-cols-2 gap-3">
              <label class="relative cursor-pointer">
                <input
                  v-model="form.installation_method"
                  type="radio"
                  value="database"
                  class="sr-only"
                  :disabled="loading"
                >
                <div class="border rounded-lg p-4 text-center"
                     :class="form.installation_method === 'database'
                       ? 'border-blue-500 bg-blue-50'
                       : 'border-gray-300 hover:border-gray-400'">
                  <div class="text-sm font-medium text-gray-900">Database</div>
                  <div class="text-xs text-gray-500 mt-1">Store in database</div>
                </div>
              </label>

              <label class="relative cursor-pointer">
                <input
                  v-model="form.installation_method"
                  type="radio"
                  value="env"
                  class="sr-only"
                  :disabled="loading"
                >
                <div class="border rounded-lg p-4 text-center"
                     :class="form.installation_method === 'env'
                       ? 'border-blue-500 bg-blue-50'
                       : 'border-gray-300 hover:border-gray-400'">
                  <div class="text-sm font-medium text-gray-900">Environment</div>
                  <div class="text-xs text-gray-500 mt-1">Store in .env file</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Username -->
          <div>
            <label for="username" class="block text-sm font-medium text-gray-700">
              Username
            </label>
            <input
              id="username"
              v-model="form.username"
              type="text"
              required
              :disabled="loading"
              @input="clearError('username')"
              class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="errors.username ? 'border-red-300' : 'border-gray-300'"
              placeholder="admin"
            >
            <p v-if="errors.username" class="mt-1 text-sm text-red-600">
              {{ errors.username[0] }}
            </p>
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
              Password
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              :disabled="loading"
              @input="clearError('password')"
              class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="errors.password ? 'border-red-300' : 'border-gray-300'"
              placeholder="••••••••"
            >
            <p v-if="errors.password" class="mt-1 text-sm text-red-600">
              {{ errors.password[0] }}
            </p>
          </div>

          <!-- Password Confirmation -->
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
              Confirm Password
            </label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              required
              :disabled="loading"
              @input="clearError('password_confirmation')"
              class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="errors.password_confirmation ? 'border-red-300' : 'border-gray-300'"
              placeholder="••••••••"
            >
            <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">
              {{ errors.password_confirmation[0] }}
            </p>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              :disabled="!isFormValid"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ loading ? 'Installing...' : 'Install TenantAdmin' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    <!-- End of installation form -->
  </div>
</template>