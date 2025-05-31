<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import DashboardHeader from '../components/DashboardLayout/DashboardHeader.vue'
import DashboardMain from '../components/DashboardLayout/DashboardMain.vue'
import Drawer from 'primevue/drawer'
import Menu from 'primevue/menu'
import type { MenuItem } from 'primevue/menuitem'

const router = useRouter()

// State
const sidebarVisible = ref(true)

// Toggle sidebar
const toggleSidebar = () => {
    sidebarVisible.value = !sidebarVisible.value
}

// Menu items
const menuItems = ref<MenuItem[]>([
    {
        label: 'Dashboard',
        icon: 'pi pi-home',
        command: () => router.push({ name: 'dashboard' })
    },
    {
        label: 'Tenants',
        icon: 'pi pi-users',
        command: () => router.push({ name: 'tenants' })
    }
])
</script>

<template>
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <Drawer
            v-model:visible="sidebarVisible"
            :modal="false"
            :showCloseIcon="false"
            position="left"
            :pt="{
                root: { class: 'w-64 shadow-none border-r' },
                header: { class: 'px-4 py-3' },
                content: { class: 'p-0' }
            }"
        >
            <template #header>
                <h2 class="text-xl font-semibold">TenantAdmin</h2>
            </template>
            <template #default>
                <Menu
                    :model="menuItems"
                    class="w-full border-none"
                />
            </template>
        </Drawer>

        <!-- Main Content -->
        <DashboardMain :sidebar-visible="sidebarVisible">
            <template #header>
                <DashboardHeader
                    :sidebar-visible="sidebarVisible"
                    @toggle-sidebar="toggleSidebar"
                />
            </template>

            <router-view />
        </DashboardMain>
    </div>
</template>
