<script lang="ts" setup>
/**
 * PasswordResetSuccessCard.vue
 *
 * Card displayed after successful password reset request.
 */

/**
 * Emits
 */
const emit = defineEmits(['close']);

/**
 * Close the success card
 */
function onClose() {
  emit('close');
}
</script>

<template>
  <q-card-section class="flex flex-col items-center">
    <q-icon
      name="eva-email-outline"
      color="green"
      size="6em"
      class="mb-4"
    />

    <div class="text-h6">
      {{ $t('auth.status.success.passwordResetSent') }}
    </div>

    <div class="text-base mb-4">
      {{ $t('auth.status.success.checkYourEmail') }}
    </div>

    <q-btn
      unelevated
      class="PrimaryButton hover:bg-primary-600"
      @click="onClose"
    >
      {{ $t('common.buttons.close') }}
    </q-btn>
  </q-card-section>
</template>
