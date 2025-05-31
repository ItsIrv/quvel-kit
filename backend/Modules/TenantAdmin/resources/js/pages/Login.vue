<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useAuthService } from '../composables/useServices'
import type { LoginRequest, ApiError } from '../types'
import { isValidationError, getValidationErrors, getUserFriendlyMessage, logError } from '../utils/errorHandler'

// Get the auth service
const authService = useAuthService()

// Form data
const form = reactive<LoginRequest>({
    username: '',
    password: '',
    remember: false
})

// UI state
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})
const message = ref('')
const messageType = ref<'success' | 'error'>('success')

// Check if form is valid
const isFormValid = computed(() => {
    return form.username.length >= 3 &&
        form.password.length >= 1 &&
        !loading.value
})

// Submit login
const submitLogin = async () => {
    if (!isFormValid.value) return

    loading.value = true
    errors.value = {}
    message.value = ''

    try {
        const response = await authService.login(form)

        if (response.success) {
            message.value = response.message || 'Login successful! Redirecting...'
            messageType.value = 'success'

            // Redirect after a short delay
            setTimeout(() => {
                window.location.href = response.redirect_url || '/admin/tenants/dashboard'
            }, 1000)
        }
    } catch (error) {
        const apiError = error as ApiError

        // Log error for debugging
        logError(apiError, 'Login submission')

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
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-3xl font-extrabold text-gray-900">
                    TenantAdmin Login
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Sign in to your tenant administration panel
                </p>
            </div>

            <!-- Login Form -->
            <div class="bg-white shadow rounded-lg p-6">
                <form
                    @submit.prevent="submitLogin"
                    class="space-y-6"
                >
                    <!-- Success Message -->
                    <div
                        v-if="message && messageType === 'success'"
                        class="rounded-md bg-green-50 p-4"
                    >
                        <p class="text-sm font-medium text-green-800">{{ message }}</p>
                    </div>

                    <!-- Error Message -->
                    <div
                        v-if="message && messageType === 'error'"
                        class="rounded-md bg-red-50 p-4"
                    >
                        <p class="text-sm font-medium text-red-800">{{ message }}</p>
                    </div>

                    <!-- Username -->
                    <div>
                        <label
                            for="username"
                            class="block text-sm font-medium text-gray-700"
                        >
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
                            placeholder="Enter your username"
                        >
                        <p
                            v-if="errors.username"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.username[0] }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label
                            for="password"
                            class="block text-sm font-medium text-gray-700"
                        >
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
                            placeholder="Enter your password"
                        >
                        <p
                            v-if="errors.password"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ errors.password[0] }}
                        </p>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            :disabled="loading"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label
                            for="remember"
                            class="ml-2 block text-sm text-gray-900"
                        >
                            Remember me
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button
                            type="submit"
                            :disabled="!isFormValid"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg
                                v-if="loading"
                                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            {{ loading ? 'Signing in...' : 'Sign in' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Link to installation if needed -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Need to install TenantAdmin?
                    <a
                        href="/admin/tenants/install"
                        class="font-medium text-blue-600 hover:text-blue-500"
                    >
                        Go to installation
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>
