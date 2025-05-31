<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthService } from '../composables/useServices'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import Drawer from 'primevue/drawer'
import type { MenuItem } from 'primevue/menuitem'

const router = useRouter()
const authService = useAuthService()

// State
const sidebarVisible = ref(true)
const profileMenuRef = ref()

// Menu items
const sidebarItems = ref<MenuItem[]>([
  {
    label: 'Dashboard',
    icon: 'pi pi-home',
    command: () => router.push('/admin/tenants/dashboard')
  },
  {
    label: 'Tenants',
    icon: 'pi pi-users',
    command: () => router.push('/admin/tenants/tenants')
  }
])

const profileMenuItems = ref<MenuItem[]>([
  {
    label: 'Logout',
    icon: 'pi pi-sign-out',
    command: async () => {
      try {
        await authService.logout()
        window.location.href = '/admin/tenants/login'
      } catch (error) {
        console.error('Logout failed:', error)
      }
    }
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
      <Menu :model="sidebarItems" class="w-full border-none" />
    </Drawer>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col" :class="{ 'ml-64': sidebarVisible }">
      <!-- Header -->
      <header class="bg-white border-b px-6 py-4 flex items-center justify-between">
        <Button 
          icon="pi pi-bars" 
          severity="secondary" 
          text
          @click="sidebarVisible = !sidebarVisible"
        />
        
        <div class="flex items-center gap-3">
          <Button 
            icon="pi pi-user" 
            severity="secondary"
            text
            rounded
            @click="(e) => profileMenuRef.toggle(e)"
          />
          <Menu 
            ref="profileMenuRef" 
            :model="profileMenuItems" 
            popup
          />
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-6 bg-gray-50">
        <router-view />
      </main>
    </div>
  </div>
</template>