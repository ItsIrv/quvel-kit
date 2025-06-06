<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { useTenantTabsStore } from '../stores/useTenantTabsStore'
import { useTenantService } from '../composables/useServices'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import type { Tenant, TenantUpdateRequest } from '../types/tenant'

import Button from 'primevue/button'
import Toast from 'primevue/toast'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import FloatLabel from 'primevue/floatlabel'
import Checkbox from 'primevue/checkbox'

const tenantTabsStore = useTenantTabsStore()
const tenantService = useTenantService()
const router = useRouter()
const toast = useToast()

// Redirect if no active tab
const activeTab = computed(() => tenantTabsStore.activeTab)
if (!activeTab.value) {
    router.push('/tenants')
}

// Form data
const formData = ref<Partial<Tenant>>({})
const originalData = ref<Partial<Tenant>>({})
const saving = ref(false)
const isInitialized = ref(false)

// Options
const environmentOptions = [
    { label: 'Production', value: 'production' },
    { label: 'Staging', value: 'staging' },
    { label: 'Development', value: 'development' },
    { label: 'Local', value: 'local' },
]

const timezoneOptions = [
    { label: 'UTC', value: 'UTC' },
    { label: 'America/New_York', value: 'America/New_York' },
    { label: 'America/Chicago', value: 'America/Chicago' },
    { label: 'America/Denver', value: 'America/Denver' },
    { label: 'America/Los_Angeles', value: 'America/Los_Angeles' },
    { label: 'Europe/London', value: 'Europe/London' },
    { label: 'Europe/Paris', value: 'Europe/Paris' },
    { label: 'Asia/Tokyo', value: 'Asia/Tokyo' },
    { label: 'Australia/Sydney', value: 'Australia/Sydney' },
]

const localeOptions = [
    { label: 'English (US)', value: 'en' },
    { label: 'Spanish', value: 'es' },
    { label: 'French', value: 'fr' },
    { label: 'German', value: 'de' },
    { label: 'Italian', value: 'it' },
    { label: 'Portuguese', value: 'pt' },
    { label: 'Japanese', value: 'ja' },
    { label: 'Chinese', value: 'zh' },
]

const sessionDriverOptions = [
    { label: 'File', value: 'file' },
    { label: 'Database', value: 'database' },
    { label: 'Redis', value: 'redis' },
    { label: 'Memcached', value: 'memcached' },
    { label: 'Cookie', value: 'cookie' },
]

const mailDriverOptions = [
    { label: 'SMTP', value: 'smtp' },
    { label: 'Sendmail', value: 'sendmail' },
    { label: 'Mailgun', value: 'mailgun' },
    { label: 'SES', value: 'ses' },
    { label: 'Postmark', value: 'postmark' },
    { label: 'Log', value: 'log' },
]

const cacheDriverOptions = [
    { label: 'File', value: 'file' },
    { label: 'Database', value: 'database' },
    { label: 'Redis', value: 'redis' },
    { label: 'Memcached', value: 'memcached' },
    { label: 'Array (Testing)', value: 'array' },
]

const databaseDriverOptions = [
    { label: 'MySQL', value: 'mysql' },
    { label: 'PostgreSQL', value: 'pgsql' },
    { label: 'SQLite', value: 'sqlite' },
    { label: 'SQL Server', value: 'sqlsrv' },
]

// Initialize form data
const initializeFormData = () => {
    if (activeTab.value) {
        isInitialized.value = false

        // Handle the nested config structure from API
        let configData = {}
        let visibilityData = {}
        let tierData = null
        
        if (activeTab.value.tenant.config) {
            // Extract nested config.config
            configData = activeTab.value.tenant.config.config || {}
            visibilityData = activeTab.value.tenant.config.visibility || {}
            tierData = activeTab.value.tenant.config.tier || null
        }

        const tenantData = {
            ...activeTab.value.tenant,
            config: configData,
            visibility: visibilityData,
            tier: tierData
        }

        // Deep clone for comparison
        originalData.value = JSON.parse(JSON.stringify(tenantData))
        formData.value = JSON.parse(JSON.stringify(tenantData))

        // Mark tab as clean on fresh load
        tenantTabsStore.markTabClean(activeTab.value.id)

        nextTick(() => {
            isInitialized.value = true
        })
    }
}

// Watch for tab changes
watch(activeTab, () => {
    if (activeTab.value) {
        initializeFormData()
    }
}, { immediate: true })

// Check if form has changes
const hasChanges = computed(() => {
    if (!activeTab.value || !isInitialized.value) return false
    return JSON.stringify(originalData.value) !== JSON.stringify(formData.value)
})

// Auto-save to store
watch(hasChanges, (newValue) => {
    if (!activeTab.value || !isInitialized.value) return

    if (newValue) {
        tenantTabsStore.markTabDirty(activeTab.value.id)
    } else {
        tenantTabsStore.markTabClean(activeTab.value.id)
    }
})

// Helper functions
const getConfigValue = (key: string, defaultValue: any = '') => {
    return formData.value.config?.[key] ?? defaultValue
}

const setConfigValue = (key: string, value: any) => {
    if (!formData.value.config) {
        formData.value.config = {}
    }
    formData.value.config[key] = value
}

// Helper functions for socialite providers
const getSocialiteProviders = (): string[] => {
    const providers = getConfigValue('socialite_providers', [])
    return Array.isArray(providers) ? providers : []
}

const toggleSocialiteProvider = (provider: string, enabled: boolean) => {
    const currentProviders = getSocialiteProviders()
    let newProviders: string[]
    
    if (enabled) {
        newProviders = [...currentProviders, provider]
    } else {
        newProviders = currentProviders.filter(p => p !== provider)
    }
    
    setConfigValue('socialite_providers', newProviders)
}

// Validation
const isFormValid = computed(() => {
    if (!formData.value.config) return false

    // Required fields
    const required = ['app_name', 'app_url', 'frontend_url', 'internal_api_url']
    return required.every(field => !!formData.value.config[field])
})

// Get changes for API
const getChanges = (): TenantUpdateRequest => {
    const changes: TenantUpdateRequest = {}

    if (originalData.value.name !== formData.value.name) {
        changes.name = formData.value.name
    }

    if (originalData.value.domain !== formData.value.domain) {
        changes.domain = formData.value.domain
    }

    if (JSON.stringify(originalData.value.config) !== JSON.stringify(formData.value.config)) {
        changes.config = formData.value.config
    }

    return changes
}

// Save tenant
const saveTenant = async () => {
    if (!activeTab.value || !hasChanges.value || !isFormValid.value) return

    saving.value = true
    try {
        const changes = getChanges()
        const updatedTenant = await tenantService.update(activeTab.value.tenant.id, changes)

        // Update tab with fresh data
        tenantTabsStore.updateTenantData(activeTab.value.id, updatedTenant)

        // Update both form and original data
        const freshData = {
            ...updatedTenant,
            config: updatedTenant.config ? { ...updatedTenant.config } : {}
        }

        originalData.value = JSON.parse(JSON.stringify(freshData))
        formData.value = JSON.parse(JSON.stringify(freshData))

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
        formData.value = JSON.parse(JSON.stringify(originalData.value))
        tenantTabsStore.markTabClean(activeTab.value.id)
    }
}
</script>

<template>
    <div
        v-if="activeTab"
        class="h-full flex flex-col"
    >
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
                <div
                    v-if="hasChanges"
                    class="flex items-center gap-2 text-orange-600"
                >
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
                    :disabled="!hasChanges || !isFormValid"
                    @click="saveTenant"
                />
            </div>
        </div>

        <!-- Content -->
        <div class="flex-1 p-6 overflow-auto bg-gray-50">
            <Card class="max-w-4xl mx-auto">
                <template #content>
                    <TabView>
                        <!-- Core Configuration Tab -->
                        <TabPanel header="Core Configuration">
                            <div class="space-y-8">
                                <!-- Basic Settings -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Basic Settings</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FloatLabel>
                                            <InputText
                                                id="app_name"
                                                :model-value="getConfigValue('app_name')"
                                                @update:model-value="setConfigValue('app_name', $event)"
                                                class="w-full"
                                                :class="{ 'p-invalid': !getConfigValue('app_name') }"
                                            />
                                            <label for="app_name">App Name <span class="text-red-500">*</span></label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <Dropdown
                                                id="app_env"
                                                :model-value="getConfigValue('app_env', 'production')"
                                                @update:model-value="setConfigValue('app_env', $event)"
                                                :options="environmentOptions"
                                                option-label="label"
                                                option-value="value"
                                                class="w-full"
                                            />
                                            <label for="app_env">Environment</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="app_key"
                                                :model-value="getConfigValue('app_key')"
                                                @update:model-value="setConfigValue('app_key', $event)"
                                                type="password"
                                                class="w-full"
                                            />
                                            <label for="app_key">App Key</label>
                                        </FloatLabel>

                                        <div class="flex items-center">
                                            <Checkbox
                                                id="app_debug"
                                                :model-value="getConfigValue('app_debug', false)"
                                                @update:model-value="setConfigValue('app_debug', $event)"
                                                :binary="true"
                                            />
                                            <label
                                                for="app_debug"
                                                class="ml-2"
                                            >Debug Mode</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- URL Configuration -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">URL Configuration</h3>
                                    <div class="grid grid-cols-1 gap-6">
                                        <FloatLabel>
                                            <InputText
                                                id="app_url"
                                                :model-value="getConfigValue('app_url')"
                                                @update:model-value="setConfigValue('app_url', $event)"
                                                class="w-full"
                                                :class="{ 'p-invalid': !getConfigValue('app_url') }"
                                            />
                                            <label for="app_url">App URL <span class="text-red-500">*</span></label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="frontend_url"
                                                :model-value="getConfigValue('frontend_url')"
                                                @update:model-value="setConfigValue('frontend_url', $event)"
                                                class="w-full"
                                                :class="{ 'p-invalid': !getConfigValue('frontend_url') }"
                                            />
                                            <label for="frontend_url">Frontend URL <span
                                                    class="text-red-500">*</span></label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="internal_api_url"
                                                :model-value="getConfigValue('internal_api_url')"
                                                @update:model-value="setConfigValue('internal_api_url', $event)"
                                                class="w-full"
                                                :class="{ 'p-invalid': !getConfigValue('internal_api_url') }"
                                            />
                                            <label for="internal_api_url">Internal API URL <span
                                                    class="text-red-500">*</span></label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="capacitor_scheme"
                                                :model-value="getConfigValue('capacitor_scheme')"
                                                @update:model-value="setConfigValue('capacitor_scheme', $event)"
                                                class="w-full"
                                            />
                                            <label for="capacitor_scheme">Capacitor Scheme</label>
                                        </FloatLabel>
                                    </div>
                                </div>

                                <!-- Localization -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Localization</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FloatLabel>
                                            <Dropdown
                                                id="app_timezone"
                                                :model-value="getConfigValue('app_timezone', 'UTC')"
                                                @update:model-value="setConfigValue('app_timezone', $event)"
                                                :options="timezoneOptions"
                                                option-label="label"
                                                option-value="value"
                                                class="w-full"
                                            />
                                            <label for="app_timezone">Timezone</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <Dropdown
                                                id="app_locale"
                                                :model-value="getConfigValue('app_locale', 'en')"
                                                @update:model-value="setConfigValue('app_locale', $event)"
                                                :options="localeOptions"
                                                option-label="label"
                                                option-value="value"
                                                class="w-full"
                                            />
                                            <label for="app_locale">Locale</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <Dropdown
                                                id="app_fallback_locale"
                                                :model-value="getConfigValue('app_fallback_locale', 'en')"
                                                @update:model-value="setConfigValue('app_fallback_locale', $event)"
                                                :options="localeOptions"
                                                option-label="label"
                                                option-value="value"
                                                class="w-full"
                                            />
                                            <label for="app_fallback_locale">Fallback Locale</label>
                                        </FloatLabel>
                                    </div>
                                </div>

                                <!-- Pusher Configuration -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Pusher / Broadcasting</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FloatLabel>
                                            <InputText
                                                id="pusher_app_id"
                                                :model-value="getConfigValue('pusher_app_id')"
                                                @update:model-value="setConfigValue('pusher_app_id', $event)"
                                                class="w-full"
                                            />
                                            <label for="pusher_app_id">Pusher App ID</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="pusher_app_key"
                                                :model-value="getConfigValue('pusher_app_key')"
                                                @update:model-value="setConfigValue('pusher_app_key', $event)"
                                                class="w-full"
                                            />
                                            <label for="pusher_app_key">Pusher App Key</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="pusher_app_secret"
                                                :model-value="getConfigValue('pusher_app_secret')"
                                                @update:model-value="setConfigValue('pusher_app_secret', $event)"
                                                type="password"
                                                class="w-full"
                                            />
                                            <label for="pusher_app_secret">Pusher App Secret</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="pusher_app_cluster"
                                                :model-value="getConfigValue('pusher_app_cluster')"
                                                @update:model-value="setConfigValue('pusher_app_cluster', $event)"
                                                class="w-full"
                                            />
                                            <label for="pusher_app_cluster">Pusher Cluster</label>
                                        </FloatLabel>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Security Tab -->
                        <TabPanel header="Security">
                            <div class="space-y-8">
                                <!-- ReCAPTCHA Settings -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">ReCAPTCHA</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FloatLabel>
                                            <InputText
                                                id="recaptcha_site_key"
                                                :model-value="getConfigValue('recaptcha_site_key')"
                                                @update:model-value="setConfigValue('recaptcha_site_key', $event)"
                                                class="w-full"
                                            />
                                            <label for="recaptcha_site_key">ReCAPTCHA Site Key</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="recaptcha_secret_key"
                                                :model-value="getConfigValue('recaptcha_secret_key')"
                                                @update:model-value="setConfigValue('recaptcha_secret_key', $event)"
                                                type="password"
                                                class="w-full"
                                            />
                                            <label for="recaptcha_secret_key">ReCAPTCHA Secret Key</label>
                                        </FloatLabel>
                                    </div>
                                </div>

                                <!-- OAuth Providers -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">OAuth Providers</h3>
                                    <div class="space-y-4">
                                        <p class="text-sm text-gray-600">Configure which OAuth providers are enabled for this tenant.</p>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                            <div v-for="provider in ['google', 'facebook', 'github', 'twitter']" :key="provider" class="flex items-center">
                                                <Checkbox
                                                    :id="`oauth_${provider}`"
                                                    :model-value="getSocialiteProviders().includes(provider)"
                                                    @update:model-value="toggleSocialiteProvider(provider, $event)"
                                                    :binary="true"
                                                />
                                                <label
                                                    :for="`oauth_${provider}`"
                                                    class="ml-2 capitalize"
                                                >{{ provider }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Session & Mail Tab -->
                        <TabPanel header="Session & Mail">
                            <div class="space-y-8">
                                <!-- Session Configuration -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Session</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FloatLabel>
                                            <InputText
                                                id="session_cookie"
                                                :model-value="getConfigValue('session_cookie')"
                                                @update:model-value="setConfigValue('session_cookie', $event)"
                                                class="w-full"
                                            />
                                            <label for="session_cookie">Session Cookie Name</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="session_domain"
                                                :model-value="getConfigValue('session_domain')"
                                                @update:model-value="setConfigValue('session_domain', $event)"
                                                class="w-full"
                                            />
                                            <label for="session_domain">Session Domain</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <Dropdown
                                                id="session_driver"
                                                :model-value="getConfigValue('session_driver', 'file')"
                                                @update:model-value="setConfigValue('session_driver', $event)"
                                                :options="sessionDriverOptions"
                                                option-label="label"
                                                option-value="value"
                                                class="w-full"
                                            />
                                            <label for="session_driver">Session Driver</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="session_lifetime"
                                                :model-value="getConfigValue('session_lifetime', '120')"
                                                @update:model-value="setConfigValue('session_lifetime', $event)"
                                                type="number"
                                                class="w-full"
                                            />
                                            <label for="session_lifetime">Session Lifetime (minutes)</label>
                                        </FloatLabel>
                                    </div>
                                </div>

                                <!-- Mail Configuration -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Mail Settings</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FloatLabel>
                                            <InputText
                                                id="mail_from_name"
                                                :model-value="getConfigValue('mail_from_name')"
                                                @update:model-value="setConfigValue('mail_from_name', $event)"
                                                class="w-full"
                                            />
                                            <label for="mail_from_name">From Name</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="mail_from_address"
                                                :model-value="getConfigValue('mail_from_address')"
                                                @update:model-value="setConfigValue('mail_from_address', $event)"
                                                type="email"
                                                class="w-full"
                                            />
                                            <label for="mail_from_address">From Address</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <Dropdown
                                                id="mail_mailer"
                                                :model-value="getConfigValue('mail_mailer', 'smtp')"
                                                @update:model-value="setConfigValue('mail_mailer', $event)"
                                                :options="mailDriverOptions"
                                                option-label="label"
                                                option-value="value"
                                                class="w-full"
                                            />
                                            <label for="mail_mailer">Mail Driver</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="mail_host"
                                                :model-value="getConfigValue('mail_host')"
                                                @update:model-value="setConfigValue('mail_host', $event)"
                                                class="w-full"
                                            />
                                            <label for="mail_host">Mail Host</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="mail_port"
                                                :model-value="getConfigValue('mail_port', '587')"
                                                @update:model-value="setConfigValue('mail_port', $event)"
                                                type="number"
                                                class="w-full"
                                            />
                                            <label for="mail_port">Mail Port</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="mail_username"
                                                :model-value="getConfigValue('mail_username')"
                                                @update:model-value="setConfigValue('mail_username', $event)"
                                                class="w-full"
                                            />
                                            <label for="mail_username">Mail Username</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="mail_password"
                                                :model-value="getConfigValue('mail_password')"
                                                @update:model-value="setConfigValue('mail_password', $event)"
                                                type="password"
                                                class="w-full"
                                            />
                                            <label for="mail_password">Mail Password</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="mail_encryption"
                                                :model-value="getConfigValue('mail_encryption', 'tls')"
                                                @update:model-value="setConfigValue('mail_encryption', $event)"
                                                class="w-full"
                                            />
                                            <label for="mail_encryption">Mail Encryption</label>
                                        </FloatLabel>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Cache & Storage Tab -->
                        <TabPanel header="Cache & Storage">
                            <div class="space-y-8">
                                <!-- Cache Configuration -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Cache Settings</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FloatLabel>
                                            <Dropdown
                                                id="cache_store"
                                                :model-value="getConfigValue('cache_store', 'file')"
                                                @update:model-value="setConfigValue('cache_store', $event)"
                                                :options="cacheDriverOptions"
                                                option-label="label"
                                                option-value="value"
                                                class="w-full"
                                            />
                                            <label for="cache_store">Cache Driver</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="cache_prefix"
                                                :model-value="getConfigValue('cache_prefix')"
                                                @update:model-value="setConfigValue('cache_prefix', $event)"
                                                class="w-full"
                                            />
                                            <label for="cache_prefix">Cache Prefix</label>
                                        </FloatLabel>
                                    </div>
                                </div>

                                <!-- Redis Configuration (if applicable) -->
                                <div v-if="getConfigValue('cache_store') === 'redis' || getConfigValue('session_driver') === 'redis'">
                                    <h3 class="text-lg font-semibold mb-4">Redis Configuration</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FloatLabel>
                                            <InputText
                                                id="redis_host"
                                                :model-value="getConfigValue('redis_host', '127.0.0.1')"
                                                @update:model-value="setConfigValue('redis_host', $event)"
                                                class="w-full"
                                            />
                                            <label for="redis_host">Redis Host</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="redis_port"
                                                :model-value="getConfigValue('redis_port', '6379')"
                                                @update:model-value="setConfigValue('redis_port', $event)"
                                                type="number"
                                                class="w-full"
                                            />
                                            <label for="redis_port">Redis Port</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="redis_password"
                                                :model-value="getConfigValue('redis_password')"
                                                @update:model-value="setConfigValue('redis_password', $event)"
                                                type="password"
                                                class="w-full"
                                            />
                                            <label for="redis_password">Redis Password</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="redis_database"
                                                :model-value="getConfigValue('redis_database', '0')"
                                                @update:model-value="setConfigValue('redis_database', $event)"
                                                type="number"
                                                class="w-full"
                                            />
                                            <label for="redis_database">Redis Database</label>
                                        </FloatLabel>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- Database Tab (Enterprise Only) -->
                        <TabPanel v-if="formData.tier === 'enterprise'" header="Database">
                            <div class="space-y-8">
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Database Configuration</h3>
                                    <p class="text-sm text-gray-600 mb-4">Enterprise tenants can have dedicated database connections.</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <FloatLabel>
                                            <Dropdown
                                                id="database_driver"
                                                :model-value="getConfigValue('database_driver', 'mysql')"
                                                @update:model-value="setConfigValue('database_driver', $event)"
                                                :options="databaseDriverOptions"
                                                option-label="label"
                                                option-value="value"
                                                class="w-full"
                                            />
                                            <label for="database_driver">Database Driver</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="database_host"
                                                :model-value="getConfigValue('database_host', '127.0.0.1')"
                                                @update:model-value="setConfigValue('database_host', $event)"
                                                class="w-full"
                                            />
                                            <label for="database_host">Database Host</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="database_port"
                                                :model-value="getConfigValue('database_port', '3306')"
                                                @update:model-value="setConfigValue('database_port', $event)"
                                                type="number"
                                                class="w-full"
                                            />
                                            <label for="database_port">Database Port</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="database_name"
                                                :model-value="getConfigValue('database_name')"
                                                @update:model-value="setConfigValue('database_name', $event)"
                                                class="w-full"
                                            />
                                            <label for="database_name">Database Name</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="database_username"
                                                :model-value="getConfigValue('database_username')"
                                                @update:model-value="setConfigValue('database_username', $event)"
                                                class="w-full"
                                            />
                                            <label for="database_username">Database Username</label>
                                        </FloatLabel>

                                        <FloatLabel>
                                            <InputText
                                                id="database_password"
                                                :model-value="getConfigValue('database_password')"
                                                @update:model-value="setConfigValue('database_password', $event)"
                                                type="password"
                                                class="w-full"
                                            />
                                            <label for="database_password">Database Password</label>
                                        </FloatLabel>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabView>
                </template>
            </Card>
        </div>
    </div>

    <div
        v-else
        class="flex items-center justify-center h-64"
    >
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
