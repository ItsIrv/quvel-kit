<script setup lang="ts">
import { ref } from 'vue'
import { useAuthService } from '../../composables/useServices'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import type { MenuItem } from 'primevue/menuitem'

const authService = useAuthService()

// Props
interface Props {
  sidebarVisible: boolean
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  toggleSidebar: []
}>()

// State
const profileMenuRef = ref()

// Profile menu items
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
  <header class="bg-white border-b px-6 py-4 flex items-center justify-between">
    <Button 
      icon="pi pi-bars" 
      severity="secondary" 
      text
      @click="emit('toggleSidebar')"
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
</template>