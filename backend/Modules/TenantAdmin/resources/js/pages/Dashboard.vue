<script setup lang="ts">
import TenantTable from '../components/TenantTable.vue'
</script>

<template>
    <div class="min-h-full bg-gray-50">
        <!-- Header -->
        <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>

        <!-- Widget Grid -->
        <div class="py-6">
            <div class="grid grid-cols-1 gap-6">
                <!-- Full-width Tenant Table Widget -->
                <div class="col-span-1">
                    <TenantTable />
                </div>
            </div>
        </div>
    </div>
</template>
