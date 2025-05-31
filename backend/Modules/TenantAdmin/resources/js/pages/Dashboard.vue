<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Card from 'primevue/card'
// import Chart from 'primevue/chart'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

// Mock data for dashboard
const stats = ref([
    {
        title: 'Total Tenants',
        value: '127',
        icon: 'pi pi-users',
        change: '+12%',
        changeType: 'positive'
    },
    {
        title: 'Active Tenants',
        value: '98',
        icon: 'pi pi-check-circle',
        change: '+5%',
        changeType: 'positive'
    },
    {
        title: 'Inactive Tenants',
        value: '29',
        icon: 'pi pi-times-circle',
        change: '-3%',
        changeType: 'negative'
    },
    {
        title: 'Total Revenue',
        value: '$45,230',
        icon: 'pi pi-dollar',
        change: '+18%',
        changeType: 'positive'
    }
])

const recentTenants = ref([
    { id: 1, name: 'Acme Corporation', domain: 'acme.example.com', status: 'active', createdAt: '2024-01-15' },
    { id: 2, name: 'TechStart Inc', domain: 'techstart.example.com', status: 'active', createdAt: '2024-01-14' },
    { id: 3, name: 'Global Services', domain: 'global.example.com', status: 'inactive', createdAt: '2024-01-13' },
    { id: 4, name: 'Innovation Labs', domain: 'labs.example.com', status: 'active', createdAt: '2024-01-12' },
    { id: 5, name: 'Digital Agency', domain: 'agency.example.com', status: 'pending', createdAt: '2024-01-11' }
])

// Chart data
const chartData = ref({
    labels: ['January', 'February', 'March', 'April', 'May', 'June'],
    datasets: [
        {
            label: 'New Tenants',
            backgroundColor: '#3B82F6',
            borderColor: '#3B82F6',
            data: [12, 19, 15, 25, 22, 30]
        },
        {
            label: 'Churned Tenants',
            backgroundColor: '#EF4444',
            borderColor: '#EF4444',
            data: [3, 5, 2, 8, 4, 6]
        }
    ]
})

const chartOptions = ref({
    maintainAspectRatio: false,
    aspectRatio: 0.6,
    plugins: {
        legend: {
            labels: {
                color: '#495057'
            }
        }
    },
    scales: {
        x: {
            ticks: {
                color: '#495057'
            },
            grid: {
                color: '#ebedef'
            }
        },
        y: {
            ticks: {
                color: '#495057'
            },
            grid: {
                color: '#ebedef'
            }
        }
    }
})

// Get status severity for tag
const getStatusSeverity = (status: string) => {
    switch (status) {
        case 'active':
            return 'success'
        case 'inactive':
            return 'danger'
        case 'pending':
            return 'warning'
        default:
            return 'info'
    }
}

// Format date
const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString()
}
</script>

<template>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="dashboard-header mb-5">
            <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
            <p class="text-gray-600 mt-1">Welcome to TenantAdmin Dashboard</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <Card
                v-for="stat in stats"
                :key="stat.title"
                class="stat-card"
            >
                <template #content>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm mb-1">{{ stat.title }}</p>
                            <h3 class="text-2xl font-bold text-gray-800">{{ stat.value }}</h3>
                            <div class="flex items-center mt-2">
                                <Tag
                                    :value="stat.change"
                                    :severity="stat.changeType === 'positive' ? 'success' : 'danger'"
                                    class="text-xs"
                                />
                                <span class="text-xs text-gray-500 ml-2">vs last month</span>
                            </div>
                        </div>
                        <div
                            class="text-4xl"
                            :class="[
                                stat.changeType === 'positive' ? 'text-green-500' : 'text-red-500'
                            ]"
                        >
                            <i :class="stat.icon"></i>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <!-- Tenant Growth Chart -->
            <Card>
                <template #title>
                    <div class="flex items-center justify-between">
                        <span>Tenant Growth</span>
                        <Button
                            icon="pi pi-ellipsis-v"
                            class="p-button-text p-button-sm p-button-rounded"
                        />
                    </div>
                </template>
                <template #content>

                </template>
            </Card>

            <!-- Activity Feed -->
            <Card>
                <template #title>
                    Recent Activity
                </template>
                <template #content>
                    <div class="activity-feed">
                        <div
                            class="activity-item"
                            v-for="i in 5"
                            :key="i"
                        >
                            <div class="activity-icon">
                                <i class="pi pi-user-plus text-blue-500"></i>
                            </div>
                            <div class="activity-content">
                                <p class="text-sm font-medium">New tenant registered</p>
                                <p class="text-xs text-gray-500">{{ i * 2 }} hours ago</p>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Recent Tenants Table -->
        <Card>
            <template #title>
                <div class="flex items-center justify-between">
                    <span>Recent Tenants</span>
                    <Button
                        label="View All"
                        icon="pi pi-arrow-right"
                        iconPos="right"
                        class="p-button-text p-button-sm"
                        @click="$router.push('/admin/tenants/tenants')"
                    />
                </div>
            </template>
            <template #content>
                <DataTable
                    :value="recentTenants"
                    :rows="5"
                    responsiveLayout="scroll"
                    class="p-datatable-sm"
                >
                    <Column
                        field="id"
                        header="ID"
                        style="width: 5%"
                    ></Column>
                    <Column
                        field="name"
                        header="Name"
                        style="width: 25%"
                    ></Column>
                    <Column
                        field="domain"
                        header="Domain"
                        style="width: 30%"
                    ></Column>
                    <Column
                        field="status"
                        header="Status"
                        style="width: 15%"
                    >
                        <template #body="slotProps">
                            <Tag
                                :value="slotProps.data.status"
                                :severity="getStatusSeverity(slotProps.data.status)"
                                class="text-xs uppercase"
                            />
                        </template>
                    </Column>
                    <Column
                        field="createdAt"
                        header="Created"
                        style="width: 15%"
                    >
                        <template #body="slotProps">
                            {{ formatDate(slotProps.data.createdAt) }}
                        </template>
                    </Column>
                    <Column
                        header="Actions"
                        style="width: 10%"
                    >
                        <template #body="slotProps">
                            <Button
                                icon="pi pi-eye"
                                class="p-button-text p-button-sm p-button-rounded"
                                v-tooltip="'View details'"
                            />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </div>
</template>

<style scoped>
.dashboard {
    animation: fadeIn 0.5s;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stat-card :deep(.p-card-content) {
    padding: 1.25rem;
}

.activity-feed {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-height: 280px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: start;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: 6px;
    transition: background-color 0.2s;
}

.activity-item:hover {
    background-color: #f8f9fa;
}

.activity-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #e3f2fd;
    border-radius: 50%;
}

.activity-content {
    flex: 1;
}

/* Custom scrollbar for activity feed */
.activity-feed::-webkit-scrollbar {
    width: 4px;
}

.activity-feed::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.activity-feed::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 2px;
}

.activity-feed::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
