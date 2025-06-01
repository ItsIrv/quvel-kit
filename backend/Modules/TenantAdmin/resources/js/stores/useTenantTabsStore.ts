import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Tenant } from '../types/tenant'

export interface TenantTab {
    id: string
    tenant: Tenant
    isDirty: boolean
    unsavedChanges?: Record<string, any>
    activeConfigSection?: string
}

export const useTenantTabsStore = defineStore('tenantTabs', () => {
    const tabs = ref<TenantTab[]>([])
    const activeTabId = ref<string | null>(null)
    
    const activeTab = computed(() => 
        tabs.value.find(tab => tab.id === activeTabId.value) || null
    )
    
    const hasDirtyTabs = computed(() => 
        tabs.value.some(tab => tab.isDirty)
    )
    
    function generateTabId(tenant: Tenant): string {
        return `tenant-${tenant.id}`
    }
    
    function openTenant(tenant: Tenant): TenantTab {
        const tabId = generateTabId(tenant)
        
        // Check if tab already exists
        const existingTab = tabs.value.find(tab => tab.id === tabId)
        if (existingTab) {
            activeTabId.value = tabId
            return existingTab
        }
        
        // Create new tab - always starts clean
        const newTab: TenantTab = {
            id: tabId,
            tenant: { ...tenant }, // Clone to prevent reference issues
            isDirty: false,
            unsavedChanges: {},
            activeConfigSection: 'core'
        }
        
        tabs.value.push(newTab)
        activeTabId.value = tabId
        
        return newTab
    }
    
    function closeTab(tabId: string, force: boolean = false): boolean {
        const tabIndex = tabs.value.findIndex(tab => tab.id === tabId)
        if (tabIndex === -1) return false
        
        const tab = tabs.value[tabIndex]
        
        // If tab has unsaved changes and not forced, return false to show dialog
        if (tab.isDirty && !force) {
            return false
        }
        
        // Remove tab
        tabs.value.splice(tabIndex, 1)
        
        // Update active tab if needed
        if (activeTabId.value === tabId) {
            if (tabs.value.length > 0) {
                // Activate previous tab or first tab
                const newActiveIndex = Math.max(0, tabIndex - 1)
                activeTabId.value = tabs.value[newActiveIndex]?.id || null
            } else {
                activeTabId.value = null
            }
        }
        
        return true
    }
    
    function forceCloseTab(tabId: string): boolean {
        return closeTab(tabId, true)
    }
    
    function markTabDirty(tabId: string, changes: Record<string, any> = {}) {
        const tab = tabs.value.find(t => t.id === tabId)
        if (tab) {
            console.log('Marking tab dirty:', tabId, 'changes:', changes)
            tab.isDirty = true
            tab.unsavedChanges = { ...tab.unsavedChanges, ...changes }
        }
    }
    
    function markTabClean(tabId: string) {
        const tab = tabs.value.find(t => t.id === tabId)
        if (tab) {
            tab.isDirty = false
            tab.unsavedChanges = {}
        }
    }
    
    function setActiveConfigSection(tabId: string, section: string) {
        const tab = tabs.value.find(t => t.id === tabId)
        if (tab) {
            tab.activeConfigSection = section
        }
    }
    
    function updateTenantData(tabId: string, updatedTenant: Tenant) {
        const tab = tabs.value.find(t => t.id === tabId)
        if (tab) {
            tab.tenant = updatedTenant
            markTabClean(tabId)
        }
    }
    
    function closeAllTabs(): boolean {
        const dirtyTabs = tabs.value.filter(tab => tab.isDirty)
        
        if (dirtyTabs.length > 0) {
            const shouldClose = confirm(
                `${dirtyTabs.length} tab(s) have unsaved changes. Close all anyway?`
            )
            if (!shouldClose) return false
        }
        
        tabs.value = []
        activeTabId.value = null
        return true
    }
    
    // Auto-save functionality (save to localStorage)
    function saveTabToLocalStorage(tabId: string) {
        const tab = tabs.value.find(t => t.id === tabId)
        if (tab && tab.isDirty) {
            const key = `tenant_draft_${tab.tenant.id}`
            localStorage.setItem(key, JSON.stringify(tab.unsavedChanges))
        }
    }
    
    function loadTabFromLocalStorage(tenant: Tenant): Record<string, any> | null {
        const key = `tenant_draft_${tenant.id}`
        const saved = localStorage.getItem(key)
        if (saved) {
            try {
                return JSON.parse(saved)
            } catch {
                localStorage.removeItem(key)
            }
        }
        return null
    }
    
    function clearTabFromLocalStorage(tenant: Tenant) {
        const key = `tenant_draft_${tenant.id}`
        localStorage.removeItem(key)
    }
    
    return {
        tabs,
        activeTabId,
        activeTab,
        hasDirtyTabs,
        openTenant,
        closeTab,
        forceCloseTab,
        markTabDirty,
        markTabClean,
        setActiveConfigSection,
        updateTenantData,
        closeAllTabs,
        saveTabToLocalStorage,
        loadTabFromLocalStorage,
        clearTabFromLocalStorage,
    }
})