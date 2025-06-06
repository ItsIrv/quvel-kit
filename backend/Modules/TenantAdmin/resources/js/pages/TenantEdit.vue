<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTenantService } from '../composables/useServices'
import type { Tenant } from '../types/tenant'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import FloatLabel from 'primevue/floatlabel'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import Message from 'primevue/message'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Checkbox from 'primevue/checkbox'
import InputSwitch from 'primevue/inputswitch'

const route = useRoute()
const router = useRouter()
const tenantService = useTenantService()

// State
const tenant = ref<Tenant | null>(null)
const loading = ref(false)
const saving = ref(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)

// Form data
const form = ref({
    name: '',
    domain: '',
    tier: '',
    parent_id: null as number | null,
    is_active: true
})

// Config form data - All pipes
const configForm = ref({
    // Core Config
    app_name: '',
    app_env: '',
    app_key: '',
    app_debug: false,
    app_url: '',
    app_timezone: '',
    app_locale: '',
    app_fallback_locale: '',
    frontend_url: '',
    internal_api_url: '',
    capacitor_scheme: '',
    pusher_app_key: '',
    pusher_app_secret: '',
    pusher_app_id: '',
    pusher_app_cluster: '',

    // Broadcasting Config
    broadcast_driver: '',
    pusher_scheme: '',
    pusher_host: '',
    pusher_port: '',
    reverb_app_id: '',
    reverb_app_key: '',
    reverb_app_secret: '',
    reverb_host: '',
    reverb_port: '',
    redis_broadcast_prefix: '',
    ably_key: '',

    // Cache Config
    cache_store: '',
    cache_prefix: '',

    // Database Config
    db_connection: '',
    db_host: '',
    db_port: '',
    db_database: '',
    db_username: '',
    db_password: ''
})

// Get tenant ID from route
const tenantId = computed(() => {
    const id = route.params.id
    return Array.isArray(id) ? id[0] : id
})

// Load tenant data
const loadTenant = async () => {
    if (!tenantId.value) return

    loading.value = true
    error.value = null

    try {
        tenant.value = await tenantService.getById(Number(tenantId.value))

        // Populate form
        if (tenant.value) {
            form.value = {
                name: tenant.value.name,
                domain: tenant.value.domain,
                tier: tenant.value.tier || '',
                parent_id: tenant.value.parent_id,
                is_active: tenant.value.is_active ?? true
            }

            // Populate config form
            const config = tenant.value.config || {}
            configForm.value = {
                // Core Config
                app_name: config.app_name || '',
                app_env: config.app_env || '',
                app_key: config.app_key || '',
                app_debug: config.app_debug || false,
                app_url: config.app_url || '',
                app_timezone: config.app_timezone || '',
                app_locale: config.app_locale || '',
                app_fallback_locale: config.app_fallback_locale || '',
                frontend_url: config.frontend_url || '',
                internal_api_url: config.internal_api_url || '',
                capacitor_scheme: config.capacitor_scheme || '',
                pusher_app_key: config.pusher_app_key || '',
                pusher_app_secret: config.pusher_app_secret || '',
                pusher_app_id: config.pusher_app_id || '',
                pusher_app_cluster: config.pusher_app_cluster || '',

                // Broadcasting Config
                broadcast_driver: config.broadcast_driver || '',
                pusher_scheme: config.pusher_scheme || '',
                pusher_host: config.pusher_host || '',
                pusher_port: config.pusher_port || '',
                reverb_app_id: config.reverb_app_id || '',
                reverb_app_key: config.reverb_app_key || '',
                reverb_app_secret: config.reverb_app_secret || '',
                reverb_host: config.reverb_host || '',
                reverb_port: config.reverb_port || '',
                redis_broadcast_prefix: config.redis_broadcast_prefix || '',
                ably_key: config.ably_key || '',

                // Cache Config
                cache_store: config.cache_store || '',
                cache_prefix: config.cache_prefix || '',

                // Database Config
                db_connection: config.db_connection || '',
                db_host: config.db_host || '',
                db_port: config.db_port || '',
                db_database: config.db_database || '',
                db_username: config.db_username || '',
                db_password: config.db_password || ''
            }
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to load tenant'
        console.error('Failed to load tenant:', err)
    } finally {
        loading.value = false
    }
}

// Save tenant
const saveTenant = async () => {
    if (!tenantId.value || !tenant.value) return

    saving.value = true
    error.value = null
    successMessage.value = null

    try {
        const updateData: any = {}

        // Only include changed fields
        if (form.value.name !== tenant.value.name) {
            updateData.name = form.value.name
        }
        if (form.value.domain !== tenant.value.domain) {
            updateData.domain = form.value.domain
        }
        if (form.value.tier !== (tenant.value.tier || '')) {
            updateData.tier = form.value.tier || null
        }
        if (form.value.is_active !== tenant.value.is_active) {
            updateData.is_active = form.value.is_active
        }

        // Check for config changes
        const originalConfig = tenant.value?.config || {}
        const configChanges: any = {}

        Object.keys(configForm.value).forEach(key => {
            const formValue = (configForm.value as any)[key]
            const originalValue = originalConfig[key]

            if (formValue !== originalValue) {
                configChanges[key] = formValue
            }
        })

        if (Object.keys(configChanges).length > 0) {
            updateData.config = { ...originalConfig, ...configChanges }
        }

        if (Object.keys(updateData).length > 0) {
            const response = await tenantService.update(Number(tenantId.value), updateData)
            tenant.value = response
            successMessage.value = 'Tenant updated successfully'
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to save tenant'
        console.error('Failed to save tenant:', err)
    } finally {
        saving.value = false
    }
}

// Go back to dashboard
const goBack = () => {
    router.push('/dashboard')
}

// Load data on mount
onMounted(() => {
    loadTenant()
})
</script>

<template>
    <div class="min-h-full bg-gray-50">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Edit Tenant</h1>
                <p class="text-sm text-gray-600 mt-1">
                    {{ tenant ? `Editing: ${tenant.name}` : 'Loading tenant...' }}
                </p>
            </div>
            <Button
                label="Back to Dashboard"
                icon="pi pi-arrow-left"
                severity="secondary"
                @click="goBack"
            />
        </div>

        <!-- Content -->
        <div class="py-6">
            <!-- Loading state -->
            <div
                v-if="loading"
                class="flex justify-center items-center py-12"
            >
                <ProgressSpinner
                    style="width: 50px; height: 50px"
                    strokeWidth="4"
                />
            </div>

            <!-- Error state -->
            <Message
                v-if="error && !loading"
                severity="error"
                :closable="false"
                class="mb-6"
            >
                {{ error }}
            </Message>

            <!-- Success message -->
            <Message
                v-if="successMessage"
                severity="success"
                :closable="false"
                class="mb-6"
            >
                {{ successMessage }}
            </Message>

            <!-- Edit form -->
            <div
                v-if="tenant && !loading"
                class="space-y-6"
            >
                <!-- Full-width Basic Info Section -->
                <Card>
                    <template #title>
                        <h2 class="text-lg font-semibold mb-6">
                            Tenant Information
                        </h2>
                    </template>

                    <template #content>
                        <form
                            @submit.prevent="saveTenant"
                            class="space-y-6"
                        >
                            <!-- Basic Info Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                                <FloatLabel>
                                    <InputText
                                        id="name"
                                        v-model="form.name"
                                        class="w-full"
                                        :disabled="saving"
                                    />
                                    <label for="name">Name</label>
                                </FloatLabel>

                                <FloatLabel>
                                    <InputText
                                        id="domain"
                                        v-model="form.domain"
                                        class="w-full"
                                        :disabled="saving"
                                    />
                                    <label for="domain">Domain</label>
                                </FloatLabel>

                                <FloatLabel>
                                    <InputText
                                        id="tier"
                                        v-model="form.tier"
                                        class="w-full"
                                        :disabled="saving"
                                    />
                                    <label for="tier">Tier</label>
                                </FloatLabel>

                                <FloatLabel>
                                    <InputText
                                        id="parent_id"
                                        :model-value="form.parent_id?.toString() || ''"
                                        @update:model-value="form.parent_id = $event ? Number($event) : null"
                                        class="w-full"
                                        :disabled="saving"
                                        type="number"
                                    />
                                    <label for="parent_id">Parent ID</label>
                                </FloatLabel>

                                <div class="flex items-center gap-3">
                                    <InputSwitch
                                        id="is_active"
                                        v-model="form.is_active"
                                        :disabled="saving"
                                    />
                                    <label
                                        for="is_active"
                                        class="text-sm font-medium text-gray-700"
                                    >
                                        {{ form.is_active ? 'Active' : 'Inactive' }}
                                    </label>
                                </div>
                            </div>

                            <!-- Read-only info -->
                            <div class="border-t pt-6">
                                <h4 class="text-sm font-medium text-gray-700 mb-4">Read-only Information</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-500">ID</label>
                                        <div class="text-sm text-gray-900 font-mono">{{ tenant.id }}</div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-500">Public ID</label>
                                        <div class="text-sm text-gray-900 font-mono">{{ tenant.public_id }}</div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-500">Created</label>
                                        <div class="text-sm text-gray-900">
                                            {{ new Date(tenant.created_at).toLocaleString() }}
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-500">Updated</label>
                                        <div class="text-sm text-gray-900">
                                            {{ new Date(tenant.updated_at).toLocaleString() }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end gap-3 pt-6 border-t">
                                <Button
                                    label="Cancel"
                                    severity="secondary"
                                    @click="goBack"
                                    :disabled="saving"
                                />
                                <Button
                                    label="Save Changes"
                                    type="submit"
                                    :loading="saving"
                                    :disabled="saving"
                                />
                            </div>
                        </form>
                    </template>
                </Card>

                <!-- Configuration Tabs -->
                <Card>
                    <template #content>
                        <TabView>
                            <TabPanel
                                header="Core Config"
                                value="core"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-4">
                                    <!-- App Settings -->
                                    <FloatLabel>
                                        <InputText
                                            id="app_name"
                                            v-model="configForm.app_name"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="app_name">App Name</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="app_env"
                                            v-model="configForm.app_env"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="app_env">App Environment</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="app_key"
                                            v-model="configForm.app_key"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="app_key">App Key</label>
                                    </FloatLabel>

                                    <div class="flex items-center gap-2">
                                        <Checkbox
                                            id="app_debug"
                                            v-model="configForm.app_debug"
                                            :binary="true"
                                            :disabled="saving"
                                        />
                                        <label
                                            for="app_debug"
                                            class="text-sm font-medium text-gray-700"
                                        >App Debug</label>
                                    </div>

                                    <FloatLabel>
                                        <InputText
                                            id="app_url"
                                            v-model="configForm.app_url"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="app_url">App URL</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="app_timezone"
                                            v-model="configForm.app_timezone"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="app_timezone">App Timezone</label>
                                    </FloatLabel>

                                    <!-- Localization -->
                                    <FloatLabel>
                                        <InputText
                                            id="app_locale"
                                            v-model="configForm.app_locale"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="app_locale">App Locale</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="app_fallback_locale"
                                            v-model="configForm.app_fallback_locale"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="app_fallback_locale">Fallback Locale</label>
                                    </FloatLabel>

                                    <!-- Frontend URLs -->
                                    <FloatLabel>
                                        <InputText
                                            id="frontend_url"
                                            v-model="configForm.frontend_url"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="frontend_url">Frontend URL</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="internal_api_url"
                                            v-model="configForm.internal_api_url"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="internal_api_url">Internal API URL</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="capacitor_scheme"
                                            v-model="configForm.capacitor_scheme"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="capacitor_scheme">Capacitor Scheme</label>
                                    </FloatLabel>

                                    <!-- Pusher/Broadcasting -->
                                    <FloatLabel>
                                        <InputText
                                            id="pusher_app_key"
                                            v-model="configForm.pusher_app_key"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="pusher_app_key">Pusher App Key</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="pusher_app_secret"
                                            v-model="configForm.pusher_app_secret"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="pusher_app_secret">Pusher App Secret</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="pusher_app_id"
                                            v-model="configForm.pusher_app_id"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="pusher_app_id">Pusher App ID</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="pusher_app_cluster"
                                            v-model="configForm.pusher_app_cluster"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="pusher_app_cluster">Pusher Cluster</label>
                                    </FloatLabel>
                                </div>
                            </TabPanel>

                            <TabPanel
                                header="Broadcasting Config"
                                value="broadcasting"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-4">
                                    <!-- Broadcasting Driver -->
                                    <FloatLabel>
                                        <InputText
                                            id="broadcast_driver"
                                            v-model="configForm.broadcast_driver"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="broadcast_driver">Broadcast Driver</label>
                                    </FloatLabel>

                                    <!-- Pusher Extended Settings -->
                                    <FloatLabel>
                                        <InputText
                                            id="pusher_scheme"
                                            v-model="configForm.pusher_scheme"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="pusher_scheme">Pusher Scheme</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="pusher_host"
                                            v-model="configForm.pusher_host"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="pusher_host">Pusher Host</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="pusher_port"
                                            v-model="configForm.pusher_port"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="pusher_port">Pusher Port</label>
                                    </FloatLabel>

                                    <!-- Reverb Settings -->
                                    <FloatLabel>
                                        <InputText
                                            id="reverb_app_id"
                                            v-model="configForm.reverb_app_id"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="reverb_app_id">Reverb App ID</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="reverb_app_key"
                                            v-model="configForm.reverb_app_key"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="reverb_app_key">Reverb App Key</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="reverb_app_secret"
                                            v-model="configForm.reverb_app_secret"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="reverb_app_secret">Reverb App Secret</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="reverb_host"
                                            v-model="configForm.reverb_host"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="reverb_host">Reverb Host</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="reverb_port"
                                            v-model="configForm.reverb_port"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="reverb_port">Reverb Port</label>
                                    </FloatLabel>

                                    <!-- Redis & Ably -->
                                    <FloatLabel>
                                        <InputText
                                            id="redis_broadcast_prefix"
                                            v-model="configForm.redis_broadcast_prefix"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="redis_broadcast_prefix">Redis Broadcast Prefix</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="ably_key"
                                            v-model="configForm.ably_key"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="ably_key">Ably Key</label>
                                    </FloatLabel>
                                </div>
                            </TabPanel>

                            <TabPanel
                                header="Cache Config"
                                value="cache"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-4">
                                    <FloatLabel>
                                        <InputText
                                            id="cache_store"
                                            v-model="configForm.cache_store"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="cache_store">Cache Store</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="cache_prefix"
                                            v-model="configForm.cache_prefix"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="cache_prefix">Cache Prefix</label>
                                    </FloatLabel>
                                </div>
                            </TabPanel>

                            <TabPanel
                                header="Database Config"
                                value="database"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-4">
                                    <FloatLabel>
                                        <InputText
                                            id="db_connection"
                                            v-model="configForm.db_connection"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="db_connection">DB Connection</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="db_host"
                                            v-model="configForm.db_host"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="db_host">DB Host</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="db_port"
                                            v-model="configForm.db_port"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="db_port">DB Port</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="db_database"
                                            v-model="configForm.db_database"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="db_database">DB Database</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="db_username"
                                            v-model="configForm.db_username"
                                            class="w-full"
                                            :disabled="saving"
                                        />
                                        <label for="db_username">DB Username</label>
                                    </FloatLabel>

                                    <FloatLabel>
                                        <InputText
                                            id="db_password"
                                            v-model="configForm.db_password"
                                            class="w-full"
                                            :disabled="saving"
                                            type="password"
                                        />
                                        <label for="db_password">DB Password</label>
                                    </FloatLabel>
                                </div>
                            </TabPanel>
                        </TabView>
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>
