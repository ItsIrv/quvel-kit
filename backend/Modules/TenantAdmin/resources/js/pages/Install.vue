<script setup lang="ts">
import { ref } from 'vue'

// Local state
const isReady = ref(true)
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h1 class="text-3xl font-extrabold text-gray-900">
          TenantAdmin Installation
        </h1>
        <p class="mt-2 text-sm text-gray-600">
          Set up your tenant administration system
        </p>
      </div>

      <!-- Main Card -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="text-center py-8">
          <!-- Icon -->
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110 4m0-4v2m0-6V4"
              />
            </svg>
          </div>

          <!-- Content -->
          <h2 class="mt-4 text-lg font-medium text-gray-900">
            Ready to Install
          </h2>
          <p class="mt-2 text-sm text-gray-500">
            The installation process will guide you through setting up your tenant administration system.
          </p>

          <!-- Status indicator -->
          <div v-if="isReady" class="mt-6 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
            <span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
            System ready for installation
          </div>
        </div>
      </div>
    </div>
  </div>
</template>