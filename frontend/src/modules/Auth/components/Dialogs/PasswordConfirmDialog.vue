<script lang="ts" setup>
/**
 * PasswordConfirmDialog.vue
 *
 * Reusable password confirmation dialog for sensitive operations.
 * Emits confirmed event with the password when successfully confirmed.
 */
import { ref, watch } from 'vue';
import TaskErrors from 'src/modules/Core/components/Common/TaskErrors.vue';
import { ErrorBag } from 'src/modules/Core/types/laravel.types';

interface Props {
  modelValue: boolean;
  title?: string;
  message?: string;
  confirmButtonLabel?: string;
  cancelButtonLabel?: string;
  loading?: boolean;
  errors?: ErrorBag;
  persistent?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  errors: () => new Map(),
  persistent: true,
});

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  'confirmed': [password: string];
  'cancelled': [];
}>();

const show = ref(props.modelValue);
const password = ref('');

watch(() => props.modelValue, (value) => {
  show.value = value;
  if (value) {
    password.value = '';
  }
});

watch(show, (value) => {
  emit('update:modelValue', value);
});

function handleConfirm() {
  if (!password.value) {
    return;
  }
  emit('confirmed', password.value);
}

function handleCancel() {
  password.value = '';
  show.value = false;
  emit('cancelled');
}
</script>

<template>
  <q-dialog
    v-model="show"
    :persistent="persistent"
  >
    <q-card style="min-width: 350px">
      <q-card-section>
        <div class="text-h6">
          {{ title || $t('dashboard.settings.twoFactor.confirmPassword.title') }}
        </div>
      </q-card-section>

      <q-card-section>
        <p
          v-if="message"
          class="q-mb-md"
        >
          {{ message }}
        </p>

        <q-form @submit.prevent="handleConfirm">
          <q-input
            v-model="password"
            :label="$t('auth.forms.common.password')"
            type="password"
            outlined
            autofocus
            @keyup.enter="handleConfirm"
          />

          <!-- Errors -->
          <TaskErrors
            v-if="errors && errors.size > 0"
            class="q-mt-sm"
            :task-errors="errors"
          />
        </q-form>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn
          flat
          :label="cancelButtonLabel || $t('common.buttons.cancel')"
          @click="handleCancel"
        />
        <q-btn
          flat
          color="primary"
          :label="confirmButtonLabel || $t('common.buttons.confirm')"
          @click="handleConfirm"
          :loading="loading"
          :disable="!password"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
