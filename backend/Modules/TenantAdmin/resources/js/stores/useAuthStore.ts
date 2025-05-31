import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '../types/auth'
import { useAuthService } from '../composables/useServices'

export const useAuthStore = defineStore('tenantAdminAuth', () => {
    const authService = useAuthService()
    
    const user = ref<User | null>(null)
    const isLoading = ref(false)
    const isInitialized = ref(false)
    
    const isAuthenticated = computed(() => !!user.value)
    
    async function fetchUser() {
        isLoading.value = true
        try {
            const userData = await authService.getUser()
            user.value = userData
            return userData
        } catch (error) {
            user.value = null
            throw error
        } finally {
            isLoading.value = false
            isInitialized.value = true
        }
    }
    
    async function login(username: string, password: string) {
        isLoading.value = true
        try {
            await authService.login({ username, password })
            const userData = await authService.getUser()
            user.value = userData
            return userData
        } catch (error) {
            user.value = null
            throw error
        } finally {
            isLoading.value = false
        }
    }
    
    async function logout() {
        isLoading.value = true
        try {
            await authService.logout()
            user.value = null
            // Navigation will be handled by route guards
        } catch (error) {
            console.error('Logout error:', error)
        } finally {
            isLoading.value = false
        }
    }
    
    async function checkAuth() {
        if (!isInitialized.value) {
            try {
                await fetchUser()
            } catch (error) {
                // User is not authenticated
                user.value = null
            }
        }
        return isAuthenticated.value
    }
    
    return {
        user,
        isLoading,
        isAuthenticated,
        isInitialized,
        fetchUser,
        login,
        logout,
        checkAuth,
    }
})