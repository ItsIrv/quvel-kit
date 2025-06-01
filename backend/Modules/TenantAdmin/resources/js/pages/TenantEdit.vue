<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useTenantTabsStore } from '../stores/useTenantTabsStore'
import { useTenantService } from '../composables/useServices'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import type { Tenant } from '../types/tenant'

import Button from 'primevue/button'
import Toast from 'primevue/toast'

const tenantTabsStore = useTenantTabsStore()
const tenantService = useTenantService()
const router = useRouter()
const toast = useToast()

// Redirect if no active tab
const activeTab = computed(() => tenantTabsStore.activeTab)
if (!activeTab.value) {
    router.push('/tenants')
}

const saving = ref(false)

// Simple approach - just use the tab's dirty state directly
const hasChanges = computed(() => activeTab.value?.isDirty || false)

// Save tenant (placeholder for now)
const saveTenant = async () => {
    if (!activeTab.value) return
    
    saving.value = true
    try {
        // For now, just simulate save
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        // Mark tab as clean
        tenantTabsStore.markTabClean(activeTab.value.id)
        
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Tenant updated successfully',
            life: 3000
        })
    } catch (error: any) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message || 'Failed to update tenant',
            life: 3000
        })
    } finally {
        saving.value = false
    }
}

// Reset form
const resetForm = () => {
    if (activeTab.value) {
        tenantTabsStore.markTabClean(activeTab.value.id)
    }
}
</script>

<template>
    <div v-if="activeTab" class="h-full flex flex-col">
        <Toast />
        
        <!-- Header -->
        <div class="flex items-center justify-between p-6 bg-white border-b">
            <div class="flex items-center gap-4">
                <Button
                    icon="pi pi-arrow-left"
                    severity="secondary"
                    text
                    @click="router.push('/tenants')"
                />
                <div>
                    <h1 class="text-2xl font-semibold">{{ activeTab.tenant.name }}</h1>
                    <p class="text-gray-600 text-sm">{{ activeTab.tenant.domain }}</p>
                </div>
                <div v-if="hasChanges" class="flex items-center gap-2 text-orange-600">
                    <i class="pi pi-circle-fill text-xs"></i>
                    <span class="text-sm">Unsaved changes</span>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <Button
                    label="Reset"
                    severity="secondary"
                    outlined
                    :disabled="!hasChanges"
                    @click="resetForm"
                />
                <Button
                    label="Save Changes"
                    :loading="saving"
                    :disabled="!hasChanges"
                    @click="saveTenant"
                />
            </div>
        </div>
        
        <!-- Content Placeholder -->
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <i class="pi pi-wrench text-4xl text-gray-400 mb-3"></i>
                <p class="text-gray-600 mb-2">Tenant configuration editor coming soon</p>
                <p class="text-sm text-gray-500">For now, you can see the header with save/reset functionality</p>
            </div>
        </div>
    </div>
    
    <div v-else class="flex items-center justify-center h-64">
        <div class="text-center">
            <i class="pi pi-inbox text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-600">No tenant selected</p>
            <Button
                label="Go to Tenants"
                @click="router.push('/tenants')"
            />
        </div>
    </div>
</template>