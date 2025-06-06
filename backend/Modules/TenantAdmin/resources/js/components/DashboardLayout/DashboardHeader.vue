<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/useAuthStore'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import type { MenuItem } from 'primevue/menuitem'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Props
interface Props {
    sidebarVisible: boolean
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
    toggleSidebar: []
}>()

// Initialize auth on mount
onMounted(async () => {
    if (!authStore.isInitialized) {
        await authStore.checkAuth()
    }
})

// User menu
const userMenuRef = ref()

const userMenuItems = ref<MenuItem[]>([
    {
        label: 'Logout',
        icon: 'pi pi-sign-out',
        command: async () => {
            await authStore.logout()
            window.location.reload()
        }
    }
])
</script>

<template>
    <header class="bg-white border-b px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <!-- <Button
                icon="pi pi-bars"
                severity="secondary"
                text
                @click="emit('toggleSidebar')"
            /> -->

            <!-- Navigation -->
            <nav class="flex items-center gap-4">
                <span class="text-lg font-semibold text-gray-800">Quvel Tenant Admin</span>
            </nav>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <span
                    v-if="authStore.user"
                    class="text-sm text-gray-600"
                >
                    {{ authStore.user.username }}
                </span>
                <Button
                    icon="pi pi-user"
                    severity="secondary"
                    text
                    rounded
                    @click="(e) => userMenuRef.toggle(e)"
                />
                <Menu
                    ref="userMenuRef"
                    :model="userMenuItems"
                    popup
                />
            </div>
        </div>
    </header>
</template>
