<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../../stores/useAuthStore'
import { useTenantTabsStore } from '../../stores/useTenantTabsStore'
import { useRouter, useRoute } from 'vue-router'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import Dialog from 'primevue/dialog'
import type { MenuItem } from 'primevue/menuitem'
import type { TenantTab } from '../../stores/useTenantTabsStore'

const authStore = useAuthStore()
const tenantTabsStore = useTenantTabsStore()
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
const tabsMenuRef = ref()
const closeTabModalVisible = ref(false)
const tabToClose = ref<TenantTab | null>(null)
const windowWidth = ref(window.innerWidth)

// Max visible tabs based on screen size
const maxVisibleTabs = computed(() => {
    if (windowWidth.value < 640) return 1  // sm
    if (windowWidth.value < 768) return 2  // md
    return 3  // lg+
})

// Split tabs into visible and hidden
const visibleTabs = computed(() => {
    return tenantTabsStore.tabs.slice(0, maxVisibleTabs.value)
})

const hiddenTabs = computed(() => {
    return tenantTabsStore.tabs.slice(maxVisibleTabs.value)
})

// Create menu items for hidden tabs
const hiddenTabMenuItems = computed((): MenuItem[] => {
    return hiddenTabs.value.map(tab => ({
        label: tab.tenant.name,
        icon: tab.isDirty ? 'pi pi-circle-fill text-orange-500 text-xs' : undefined,
        command: () => switchToTab(tab.id),
        class: tab.id === tenantTabsStore.activeTabId ? 'bg-primary-50' : ''
    }))
})

// Window resize handler
const handleResize = () => {
    windowWidth.value = window.innerWidth
}

onMounted(() => {
    window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
})

// Close tenant tab
const closeTenantTab = (tabId: string, event: Event) => {
    event.stopPropagation()

    const closed = tenantTabsStore.closeTab(tabId)
    if (!closed) {
        // Tab has unsaved changes, show confirmation dialog
        const tab = tenantTabsStore.tabs.find(t => t.id === tabId)
        if (tab) {
            tabToClose.value = tab
            closeTabModalVisible.value = true
        }
    }
}

// Confirm tab close
const confirmCloseTab = () => {
    if (tabToClose.value) {
        tenantTabsStore.forceCloseTab(tabToClose.value.id)
        closeTabModalVisible.value = false
        tabToClose.value = null
    }
}

// Switch to tenant tab
const switchToTab = (tabId: string) => {
    tenantTabsStore.activeTabId = tabId
    router.push('/tenants/edit')
}

// Profile menu items
const profileMenuItems = ref<MenuItem[]>([
    {
        label: 'Close All Tabs',
        icon: 'pi pi-times-circle',
        command: () => {
            tenantTabsStore.closeAllTabs()
        },
        visible: () => tenantTabsStore.tabs.length > 0
    },
    {
        separator: true,
        visible: () => tenantTabsStore.tabs.length > 0
    },
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

            <!-- Tenant Tabs -->
            <div
                v-if="tenantTabsStore.tabs.length > 0"
                class="flex items-center gap-1 ml-6"
            >
                <!-- Visible tabs based on screen size -->
                <div
                    v-for="(tab, index) in visibleTabs"
                    :key="tab.id"
                    class="relative group"
                >
                    <button
                        :class="[
                            'flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                            'border border-gray-200 bg-white hover:bg-gray-50',
                            tab.id === tenantTabsStore.activeTabId
                                ? 'border-primary-300 bg-primary-50 text-primary-700'
                                : 'text-gray-700'
                        ]"
                        @click="switchToTab(tab.id)"
                    >
                        <!-- Dirty indicator -->
                        <div
                            v-if="tab.isDirty"
                            class="w-1.5 h-1.5 rounded-full bg-orange-500"
                        ></div>

                        <!-- Tenant name -->
                        <span class="max-w-32 truncate">{{ tab.tenant.name }}</span>

                        <!-- Close button -->
                        <div
                            class="opacity-0 group-hover:opacity-100 transition-opacity p-0.5 hover:bg-gray-200 rounded"
                            @click="closeTenantTab(tab.id, $event)"
                        >
                            <i class="pi pi-times text-xs"></i>
                        </div>
                    </button>
                </div>

                <!-- Overflow dropdown -->
                <div
                    v-if="hiddenTabs.length > 0"
                    class="relative"
                >
                    <Button
                        :label="`+${hiddenTabs.length}`"
                        severity="secondary"
                        size="small"
                        @click="(e) => tabsMenuRef.toggle(e)"
                    />
                    <Menu
                        ref="tabsMenuRef"
                        :model="hiddenTabMenuItems"
                        popup
                    />
                </div>
            </div>
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

    <!-- Close Tab Confirmation Dialog -->
    <Dialog
        v-model:visible="closeTabModalVisible"
        header="Close Tab"
        :style="{ width: '450px' }"
        :modal="true"
    >
        <div class="flex items-center gap-3 mb-4">
            <i class="pi pi-exclamation-triangle text-orange-500 text-2xl"></i>
            <div>
                <p class="font-semibold mb-1">Are you sure you want to close this tab?</p>
                <p class="text-gray-600 text-sm">
                    <strong>{{ tabToClose?.tenant.name }}</strong> has unsaved changes that will be lost.
                    You can save your changes first or close anyway.
                </p>
            </div>
        </div>

        <template #footer>
            <Button
                label="Cancel"
                icon="pi pi-times"
                severity="secondary"
                @click="closeTabModalVisible = false"
            />
            <Button
                label="Close Anyway"
                icon="pi pi-trash"
                severity="danger"
                @click="confirmCloseTab"
            />
        </template>
    </Dialog>
</template>
