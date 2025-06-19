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
  <q-card-section class="tw:flex tw:flex-col tw:items-center">
    <q-icon
      name="eva-email-outline"
      color="green"
      size="6em"
      class="tw:mb-4"
    />

    <div class="tw:text-h6">
      {{ $t('auth.status.success.passwordResetSent') }}
    </div>

    <div class="tw:text-base tw:mb-4">
      {{ $t('auth.status.success.checkYourEmail') }}
    </div>

    <q-btn
      unelevated
      class="PrimaryButton tw:hover:bg-primary-600"
      @click="onClose"
    >
      {{ $t('common.buttons.close') }}
    </q-btn>
  </q-card-section>
</template>
