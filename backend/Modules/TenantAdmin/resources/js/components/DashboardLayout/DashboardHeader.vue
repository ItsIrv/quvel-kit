<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../../stores/useAuthStore'
import { useRouter, useRoute } from 'vue-router'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import type { MenuItem } from 'primevue/menuitem'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

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
      await authStore.logout()
      await router.push('/login')
    }
  }
])
</script>

<template>
  <header class="bg-white border-b px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-6">
      <Button 
        icon="pi pi-bars" 
        severity="secondary" 
        text
        @click="emit('toggleSidebar')"
      />
      
      <!-- Navigation Links -->
      <nav class="flex items-center gap-4">
        <Button 
          :label="'Dashboard'"
          :severity="route.name === 'dashboard' ? 'primary' : 'secondary'"
          :text="route.name !== 'dashboard'"
          size="small"
          @click="router.push('/dashboard')"
        />
        <Button 
          :label="'Tenants'"
          :severity="route.name === 'tenants' ? 'primary' : 'secondary'"
          :text="route.name !== 'tenants'"
          size="small"
          @click="router.push('/tenants')"
        />
      </nav>
    </div>
    
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