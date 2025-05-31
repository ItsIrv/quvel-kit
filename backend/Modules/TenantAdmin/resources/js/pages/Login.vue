<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useAuthStore } from '../stores/useAuthStore'
import { useRouter, useRoute } from 'vue-router'
import type { LoginRequest, ApiError } from '../types'
import { isValidationError, getValidationErrors, getUserFriendlyMessage, logError } from '../utils/errorHandler'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Checkbox from 'primevue/checkbox'
import Message from 'primevue/message'
import Card from 'primevue/card'

// Get the auth store and router
const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

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

// Submit login
const submitLogin = async () => {
    loading.value = true
    errors.value = {}
    message.value = ''

    try {
        await authStore.login(form.username, form.password)
        
        // Redirect to the intended page or dashboard
        const redirectTo = route.query.redirect as string || '/dashboard'
        await router.push(redirectTo)
    } catch (error) {
        const apiError = error as ApiError
        
        if (isValidationError(apiError)) {
            errors.value = getValidationErrors(apiError)
            message.value = apiError.message || 'Please fix the errors below.'
        } else {
            message.value = getUserFriendlyMessage(apiError)
        }
        messageType.value = 'error'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <Card class="w-full max-w-md">
            <template #title>
                <h1 class="text-2xl font-semibold text-center">TenantAdmin Login</h1>
            </template>
            <template #content>
                <form @submit.prevent="submitLogin" class="space-y-6">
                    <Message 
                        v-if="message && messageType === 'error'" 
                        severity="error"
                        :closable="false"
                    >
                        {{ message }}
                    </Message>

                    <div>
                        <label for="username" class="block text-sm font-medium mb-2">Username</label>
                        <InputText 
                            id="username"
                            v-model="form.username"
                            class="w-full"
                            :class="{ 'p-invalid': errors.username }"
                            :disabled="loading"
                            placeholder="Enter username"
                        />
                        <small v-if="errors.username" class="text-red-500">{{ errors.username[0] }}</small>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium mb-2">Password</label>
                        <Password 
                            id="password"
                            v-model="form.password"
                            class="w-full"
                            :class="{ 'p-invalid': errors.password }"
                            :disabled="loading"
                            :feedback="false"
                            toggleMask
                            placeholder="Enter password"
                        />
                        <small v-if="errors.password" class="text-red-500">{{ errors.password[0] }}</small>
                    </div>

                    <div class="flex items-center">
                        <Checkbox 
                            v-model="form.remember"
                            inputId="remember"
                            :binary="true"
                            :disabled="loading"
                        />
                        <label for="remember" class="ml-2">Remember me</label>
                    </div>

                    <Button 
                        type="submit"
                        label="Sign in"
                        icon="pi pi-sign-in"
                        class="w-full"
                        :loading="loading"
                    />
                </form>
            </template>
        </Card>
    </div>
</template>