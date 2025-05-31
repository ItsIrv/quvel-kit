<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useTenantService } from '../composables/useServices'
import type { Tenant } from '../types/tenant'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

const tenantService = useTenantService()
const toast = useToast()

// State
const tenants = ref<Tenant[]>([])
const loading = ref(false)
const totalRecords = ref(0)
const searchQuery = ref('')
const editModalVisible = ref(false)
const selectedTenant = ref<Tenant | null>(null)

// Pagination
const page = ref(1)
const rows = ref(10)

// Load tenants
const loadTenants = async () => {
    loading.value = true
    try {
        const response = await tenantService.list(page.value, rows.value)
        tenants.value = response.data
        totalRecords.value = response.total
    } catch (error: any) {
        console.error('Failed to load tenants:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.message || 'Failed to load tenants',
            life: 3000
        })
    } finally {
        loading.value = false
    }
}

// Search tenants
const searchTenants = async () => {
    if (!searchQuery.value.trim()) {
        await loadTenants()
        return
    }

    loading.value = true
    try {
        const response = await tenantService.search(searchQuery.value)
        tenants.value = response.data
        totalRecords.value = response.total
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to search tenants',
            life: 3000
        })
    } finally {
        loading.value = false
    }
}

// Edit tenant
const editTenant = (tenant: Tenant) => {
    selectedTenant.value = tenant
    editModalVisible.value = true
}

// Delete tenant
const deleteTenant = async (tenant: Tenant) => {
    if (!confirm(`Are you sure you want to delete ${tenant.name}?`)) {
        return
    }

    try {
        await tenantService.deleteTenant(tenant.id)
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Tenant deleted successfully',
            life: 3000
        })
        await loadTenants()
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to delete tenant',
            life: 3000
        })
    }
}

// Status tag severity
const getStatusSeverity = (status: string) => {
    switch (status) {
        case 'active':
            return 'success'
        case 'inactive':
            return 'warning'
        case 'suspended':
            return 'danger'
        default:
            return 'info'
    }
}

// Format date
const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString()
}

// Page change
const onPage = (event: any) => {
    page.value = event.page + 1
    rows.value = event.rows
    loadTenants()
}

// Initial load
onMounted(() => {
    console.log('TenantList mounted, loading tenants...')
    loadTenants()
})
</script>

<template>
    <div>
        <Toast />

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold">Tenants</h1>
            <Button
                label="Add Tenant"
                icon="pi pi-plus"
                @click="editModalVisible = true"
            />
        </div>

        <!-- Search -->
        <div class="flex gap-3 mb-4">
            <span class="p-input-icon-left flex-1">
                <i class="pi pi-search" />
                <InputText
                    v-model="searchQuery"
                    placeholder="Search tenants..."
                    class="w-full"
                    @keyup.enter="searchTenants"
                />
            </span>
            <Button
                label="Search"
                icon="pi pi-search"
                @click="searchTenants"
            />
        </div>

        <!-- Data Table -->
        <DataTable
            :value="tenants"
            :loading="loading"
            :paginator="true"
            :rows="rows"
            :totalRecords="totalRecords"
            :lazy="true"
            @page="onPage"
            responsiveLayout="scroll"
            class="p-datatable-sm"
        >
            <template #empty>
                <div class="text-center py-8">
                    <i class="pi pi-inbox text-4xl text-gray-400 mb-3"></i>
                    <p class="text-gray-600">No tenants found</p>
                </div>
            </template>
            <Column
                field="public_id"
                header="ID"
                :sortable="true"
                style="width: 15%"
            ></Column>
            <Column
                field="name"
                header="Name"
                :sortable="true"
                style="width: 25%"
            ></Column>
            <Column
                field="domain"
                header="Domain"
                :sortable="true"
                style="width: 30%"
            ></Column>
            <Column
                header="Status"
                style="width: 15%"
            >
                <template #body="slotProps">
                    <Tag
                        :value="slotProps.data.config?.status || 'active'"
                        :severity="getStatusSeverity(slotProps.data.config?.status || 'active')"
                    />
                </template>
            </Column>
            <Column
                field="created_at"
                header="Created"
                style="width: 10%"
            >
                <template #body="slotProps">
                    {{ formatDate(slotProps.data.created_at) }}
                </template>
            </Column>
            <Column
                header="Actions"
                style="width: 15%"
            >
                <template #body="slotProps">
                    <div class="flex gap-2">
                        <Button
                            icon="pi pi-pencil"
                            severity="info"
                            text
                            rounded
                            @click="editTenant(slotProps.data)"
                        />
                        <Button
                            icon="pi pi-trash"
                            severity="danger"
                            text
                            rounded
                            @click="deleteTenant(slotProps.data)"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Edit Modal -->
        <Dialog
            v-model:visible="editModalVisible"
            :header="selectedTenant ? 'Edit Tenant' : 'Add Tenant'"
            :style="{ width: '450px' }"
            :modal="true"
        >
            <p class="text-gray-600">Tenant form coming soon...</p>

            <template #footer>
                <Button
                    label="Cancel"
                    icon="pi pi-times"
                    text
                    @click="editModalVisible = false"
                />
                <Button
                    label="Save"
                    icon="pi pi-check"
                    @click="editModalVisible = false"
                />
            </template>
        </Dialog>
    </div>
</template>
