<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTenantService } from '../composables/useServices'
import type { Tenant, TenantListResponse } from '../types/tenant'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Paginator from 'primevue/paginator'
import ProgressSpinner from 'primevue/progressspinner'
import Button from 'primevue/button'

const tenantService = useTenantService()
const router = useRouter()

// State
const tenants = ref<Tenant[]>([])
const loading = ref(false)
const totalRecords = ref(0)
const currentPage = ref(1)
const perPage = ref(10)

// Load tenants
const loadTenants = async (page: number = 1) => {
    loading.value = true
    try {
        const response: TenantListResponse = await tenantService.list(page, perPage.value)
        tenants.value = response.data
        totalRecords.value = response.total
        currentPage.value = response.current_page
    } catch (error) {
        console.error('Failed to load tenants:', error)
        tenants.value = []
        totalRecords.value = 0
    } finally {
        loading.value = false
    }
}

// Handle pagination
const onPageChange = (event: any) => {
    const page = event.page + 1 // PrimeVue uses 0-based indexing
    loadTenants(page)
}

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Navigate to edit tenant
const editTenant = (tenantId: number) => {
    router.push(`/tenants/${tenantId}/edit`)
}

// Load data on mount
onMounted(() => {
    loadTenants()
})
</script>

<template>
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Tenants</h3>
            <p class="text-sm text-gray-600 mt-1">Manage your tenant configurations</p>
        </div>

        <!-- Content -->
        <div class="relative">
            <!-- Loading overlay -->
            <div v-if="loading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
                <ProgressSpinner style="width: 40px; height: 40px" strokeWidth="4" />
            </div>

            <!-- Table -->
            <DataTable 
                :value="tenants" 
                :loading="loading"
                striped-rows
                class="w-full"
                :pt="{
                    table: { class: 'min-w-full' },
                    thead: { class: 'bg-gray-50' },
                    headerRow: { class: 'border-b border-gray-200' },
                    headerCell: { class: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider' },
                    tbody: { class: 'bg-white divide-y divide-gray-200' },
                    bodyRow: { class: 'hover:bg-gray-50' },
                    bodyCell: { class: 'px-6 py-4 whitespace-nowrap text-sm text-gray-900' }
                }"
            >
                <Column field="id" header="ID" class="w-16">
                    <template #body="{ data }">
                        <span class="font-mono text-xs text-gray-500">{{ data.id }}</span>
                    </template>
                </Column>
                
                <Column field="name" header="Name" class="min-w-48">
                    <template #body="{ data }">
                        <div class="font-medium text-gray-900">{{ data.name }}</div>
                    </template>
                </Column>
                
                <Column field="domain" header="Domain" class="min-w-48">
                    <template #body="{ data }">
                        <span class="font-mono text-sm">{{ data.domain }}</span>
                    </template>
                </Column>
                
                <Column field="public_id" header="Public ID" class="min-w-32">
                    <template #body="{ data }">
                        <span class="font-mono text-xs text-gray-500">{{ data.public_id }}</span>
                    </template>
                </Column>
                
                <Column field="tier" header="Tier" class="w-24">
                    <template #body="{ data }">
                        <span v-if="data.tier" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ data.tier }}
                        </span>
                        <span v-else class="text-gray-400">-</span>
                    </template>
                </Column>
                
                <Column field="parent_id" header="Parent" class="w-20">
                    <template #body="{ data }">
                        <span v-if="data.parent_id" class="font-mono text-xs text-gray-500">{{ data.parent_id }}</span>
                        <span v-else class="text-gray-400">-</span>
                    </template>
                </Column>
                
                <Column field="created_at" header="Created" class="min-w-40">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-500">{{ formatDate(data.created_at) }}</span>
                    </template>
                </Column>
                
                <Column header="Actions" class="w-20">
                    <template #body="{ data }">
                        <Button
                            icon="pi pi-pencil"
                            severity="secondary"
                            text
                            size="small"
                            @click="editTenant(data.id)"
                            class="p-1"
                        />
                    </template>
                </Column>
            </DataTable>

            <!-- Pagination -->
            <div v-if="totalRecords > perPage" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <Paginator
                    :rows="perPage"
                    :total-records="totalRecords"
                    :first="(currentPage - 1) * perPage"
                    @page="onPageChange"
                    :pt="{
                        root: { class: 'flex items-center justify-between' },
                        pages: { class: 'flex items-center space-x-1' },
                        pageButton: { class: 'px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500' },
                        current: { class: 'px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-md' }
                    }"
                />
            </div>
        </div>
    </div>
</template>