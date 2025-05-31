<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthService } from '../composables/useServices'
import Button from 'primevue/button'
import Sidebar from 'primevue/sidebar'
import Menu from 'primevue/menu'
import Avatar from 'primevue/avatar'
import Badge from 'primevue/badge'
import type { MenuItem } from 'primevue/menuitem'

const router = useRouter()
const authService = useAuthService()

// State
const sidebarVisible = ref(true)
const mobileMenuVisible = ref(false)
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
    items: [
      {
        label: 'All Tenants',
        icon: 'pi pi-list',
        command: () => router.push('/admin/tenants/tenants')
      },
      {
        label: 'Add Tenant',
        icon: 'pi pi-plus',
        command: () => router.push('/admin/tenants/tenants/new')
      }
    ]
  },
  {
    label: 'Configuration',
    icon: 'pi pi-cog',
    command: () => router.push('/admin/tenants/config')
  },
  {
    label: 'Settings',
    icon: 'pi pi-sliders-h',
    command: () => router.push('/admin/tenants/settings')
  }
])

const profileMenuItems = ref<MenuItem[]>([
  {
    label: 'Profile',
    icon: 'pi pi-user',
    command: () => router.push('/admin/tenants/profile')
  },
  {
    separator: true
  },
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

// Toggle sidebar
const toggleSidebar = () => {
  sidebarVisible.value = !sidebarVisible.value
}

// Toggle profile menu
const toggleProfileMenu = (event: Event) => {
  profileMenuRef.value.toggle(event)
}

// Computed classes
const layoutClass = computed(() => ({
  'layout-sidebar-collapsed': !sidebarVisible.value,
  'layout-sidebar-expanded': sidebarVisible.value
}))
</script>

<template>
  <div class="layout-wrapper" :class="layoutClass">
    <!-- Topbar -->
    <div class="layout-topbar">
      <div class="layout-topbar-left">
        <Button 
          icon="pi pi-bars" 
          class="p-button-text p-button-plain layout-menu-button"
          @click="toggleSidebar"
        />
        <div class="layout-topbar-logo">
          <span class="text-2xl font-bold text-primary">TenantAdmin</span>
        </div>
      </div>
      
      <div class="layout-topbar-right">
        <!-- Notifications -->
        <Button 
          icon="pi pi-bell" 
          class="p-button-text p-button-plain"
          v-badge.danger="3"
        />
        
        <!-- Profile -->
        <Button 
          icon="pi pi-user" 
          class="p-button-text p-button-plain"
          @click="toggleProfileMenu"
        />
        <Menu 
          ref="profileMenuRef" 
          :model="profileMenuItems" 
          :popup="true"
          class="w-48"
        />
      </div>
    </div>

    <!-- Sidebar -->
    <div class="layout-sidebar" :class="{ 'active': sidebarVisible }">
      <div class="layout-menu">
        <Menu 
          :model="sidebarItems" 
          class="layout-menu-container"
        />
      </div>
    </div>

    <!-- Main Content -->
    <div class="layout-main">
      <router-view />
    </div>

    <!-- Mobile Sidebar -->
    <Sidebar 
      v-model:visible="mobileMenuVisible" 
      :modal="true"
      class="layout-mobile-sidebar"
    >
      <template #header>
        <div class="flex align-items-center gap-2">
          <span class="text-xl font-bold">TenantAdmin</span>
        </div>
      </template>
      <Menu 
        :model="sidebarItems" 
        class="w-full border-none"
      />
    </Sidebar>
  </div>
</template>

<style scoped>
.layout-wrapper {
  min-height: 100vh;
  display: flex;
  position: relative;
}

.layout-topbar {
  position: fixed;
  height: 70px;
  top: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2rem;
  background-color: #ffffff;
  border-bottom: 1px solid #dee2e6;
  z-index: 999;
}

.layout-topbar-left {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.layout-topbar-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.layout-sidebar {
  position: fixed;
  width: 250px;
  height: calc(100vh - 70px);
  top: 70px;
  left: 0;
  background-color: #f8f9fa;
  border-right: 1px solid #dee2e6;
  transition: transform 0.3s;
  z-index: 998;
  overflow-y: auto;
}

.layout-sidebar:not(.active) {
  transform: translateX(-100%);
}

.layout-main {
  flex: 1;
  margin-top: 70px;
  margin-left: 0;
  padding: 2rem;
  background-color: #f5f6fa;
  min-height: calc(100vh - 70px);
  transition: margin-left 0.3s;
}

.layout-sidebar-expanded .layout-main {
  margin-left: 250px;
}

.layout-menu-container {
  border: none;
  background: transparent;
}

.layout-menu-container :deep(.p-menu) {
  border: none;
  background: transparent;
}

.layout-menu-container :deep(.p-menuitem) {
  margin: 0.25rem 0;
}

.layout-menu-container :deep(.p-menuitem-link) {
  border-radius: 6px;
  transition: all 0.2s;
}

.layout-menu-container :deep(.p-menuitem-link:hover) {
  background-color: rgba(0, 0, 0, 0.04);
}

/* Mobile styles */
@media (max-width: 768px) {
  .layout-sidebar {
    transform: translateX(-100%);
  }
  
  .layout-main {
    margin-left: 0 !important;
  }
  
  .layout-topbar-logo {
    display: none;
  }
}

/* Animations */
.layout-menu-button {
  transition: transform 0.3s;
}

.layout-menu-button:hover {
  transform: scale(1.1);
}

/* Custom scrollbar for sidebar */
.layout-sidebar::-webkit-scrollbar {
  width: 6px;
}

.layout-sidebar::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.layout-sidebar::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

.layout-sidebar::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>