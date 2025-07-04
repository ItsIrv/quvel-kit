<script lang="ts" setup>
/**
 * SettingsPage.vue
 *
 * Dashboard settings page for managing user preferences and security settings.
 * Currently focuses on two-factor authentication management.
 */
import { ref } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { useScopedService } from 'src/modules/Core/composables/useScopedService';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { TwoFactorService } from 'src/modules/Auth/services/TwoFactorService';
import TaskErrors from 'src/modules/Core/components/Common/TaskErrors.vue';
import { copyToClipboard } from 'quasar';
import { showNotification } from 'src/modules/Core/utils/notifyUtil';

/**
 * Services and composables
 */
const { task, i18n } = useContainer();
const sessionStore = useSessionStore();
const twoFactorService = useScopedService(TwoFactorService);

/**
 * Component state
 */
const showEnableMFA = ref(false);
const showPasswordConfirm = ref(false);
const setupStep = ref(1);
const qrCode = ref('');
const secretKey = ref('');
const verificationCode = ref('');
const confirmPassword = ref('');
const recoveryCodes = ref<string[]>([]);
const currentRecoveryCodes = ref<string[]>([]);
const showDisableDialog = ref(false);
const showRecoveryDialog = ref(false);

/**
 * Task: Confirm password for sensitive operations
 */
const confirmPasswordTask = task.newTask({
  task: async () => {
    await twoFactorService.confirmPassword(confirmPassword.value);
    showPasswordConfirm.value = false;
    confirmPassword.value = '';
    void enableMFATask.run();
  },
  handleLaravelError: {
    translate: true,
  },
});

/**
 * Task: Enable two-factor authentication
 */
const enableMFATask = task.newTask({
  task: async () => {
    await twoFactorService.enable();

    const [qrResponse, secretResponse] = await Promise.all([
      twoFactorService.getQRCode(),
      twoFactorService.getSecretKey(),
    ]);

    qrCode.value = qrResponse.svg;
    secretKey.value = secretResponse.secretKey;
    showEnableMFA.value = true;
  },
  handleLaravelError: {
    translate: true,
  },
});

/**
 * Task: Cancel MFA setup
 */
const cancelMFATask = task.newTask({
  task: async () => {
    await twoFactorService.disable();
    resetMFAState();
  },
  handleLaravelError: {
    translate: true,
  },
});

/**
 * Task: Confirm MFA setup with verification code
 */
const confirmMFATask = task.newTask({
  task: async () => {
    await twoFactorService.confirm(verificationCode.value);

    const codes = await twoFactorService.getRecoveryCodes();
    recoveryCodes.value = codes;

    await sessionStore.fetchSession();
    setupStep.value = 3;
  },
  handleLaravelError: {
    translate: true,
  },
});

/**
 * Task: Disable two-factor authentication
 */
const disableMFATask = task.newTask({
  task: async () => {
    await twoFactorService.disable();
    await sessionStore.fetchSession();
    showDisableDialog.value = false;
  },
  handleLaravelError: {
    translate: true,
  },
  showNotification: {
    success: () => i18n.t('dashboard.settings.twoFactor.success.disabled'),
  },
});

/**
 * Task: View recovery codes
 */
const viewRecoveryCodesTask = task.newTask({
  task: async () => {
    const codes = await twoFactorService.getRecoveryCodes();
    currentRecoveryCodes.value = codes;
    showRecoveryDialog.value = true;
  },
  handleLaravelError: {
    translate: true,
  },
});

/**
 * Task: Regenerate recovery codes
 */
const regenerateCodesTask = task.newTask({
  task: async () => {
    const codes = await twoFactorService.regenerateRecoveryCodes();
    currentRecoveryCodes.value = codes;
  },
  handleLaravelError: {
    translate: true,
  },
  showNotification: {
    success: () => i18n.t('dashboard.settings.twoFactor.success.codesRegenerated'),
  },
});

/**
 * Start MFA enable flow - first show password confirmation
 */
function startEnableMFA() {
  showPasswordConfirm.value = true;
}

/**
 * Confirm password and proceed with MFA setup
 */
function confirmPasswordAndEnable() {
  if (!confirmPassword.value) {
    confirmPasswordTask.errors.value.set(
      'password',
      i18n.t('auth.validation.password.required')
    );
    return;
  }

  void confirmPasswordTask.run();
}

/**
 * Cancel MFA setup
 */
function cancelEnableMFA() {
  void cancelMFATask.run();
}

/**
 * Confirm MFA setup
 */
function confirmEnableMFA() {
  if (!verificationCode.value || verificationCode.value.length !== 6) {
    confirmMFATask.errors.value.set(
      'code',
      i18n.t('dashboard.settings.twoFactor.errors.invalidCode')
    );
    return;
  }

  void confirmMFATask.run();
}

/**
 * Disable MFA
 */
function disableMFA() {
  void disableMFATask.run();
}

/**
 * View recovery codes
 */
function viewRecoveryCodes() {
  void viewRecoveryCodesTask.run();
}

/**
 * Regenerate recovery codes
 */
function regenerateRecoveryCodes() {
  void regenerateCodesTask.run();
}

/**
 * Finish MFA setup
 */
function finishSetup() {
  resetMFAState();
  // Show success notification
  showNotification('positive', i18n.t('dashboard.settings.twoFactor.success.enabled'));
}

/**
 * Reset MFA setup state
 */
function resetMFAState() {
  showEnableMFA.value = false;
  showPasswordConfirm.value = false;
  setupStep.value = 1;
  verificationCode.value = '';
  confirmPassword.value = '';
  qrCode.value = '';
  secretKey.value = '';
  recoveryCodes.value = [];
  enableMFATask.reset();
  confirmMFATask.reset();
  confirmPasswordTask.reset();
}

/**
 * Copy secret key to clipboard
 */
function copySecretKey() {
  void copyToClipboard(secretKey.value);

  showNotification('positive', i18n.t('common.copied'));
}

/**
 * Download recovery codes as text file
 */
function downloadRecoveryCodes() {
  const content = recoveryCodes.value.join('\n');
  const blob = new Blob([content], { type: 'text/plain' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = 'recovery-codes.txt';
  link.click();
  URL.revokeObjectURL(url);
}
</script>

<template>
  <q-page padding>
    <div class="row q-col-gutter-lg">
      <div class="col-12">
        <h1 class="text-h4 q-mt-none q-mb-md">{{ $t('dashboard.settings.title') }}</h1>
      </div>

      <!-- MFA Section -->
      <div class="col-12 col-md-8 col-lg-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">{{ $t('dashboard.settings.twoFactor.title') }}</div>
            <div class="text-caption text-grey-7 q-mt-sm">
              {{ $t('dashboard.settings.twoFactor.description') }}
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div v-if="!sessionStore.user?.two_factor_enabled">
              <!-- Enable MFA -->
              <div v-if="!showEnableMFA">
                <p class="text-body2 q-mb-md">
                  {{ $t('dashboard.settings.twoFactor.notEnabled') }}
                </p>
                <q-btn
                  color="primary"
                  :label="$t('dashboard.settings.twoFactor.enable')"
                  @click="startEnableMFA"
                  :loading="enableMFATask.isActive.value"
                />

                <!-- Errors -->
                <TaskErrors
                  class="q-mt-md"
                  :task-errors="enableMFATask.errors.value"
                />
              </div>

              <!-- MFA Setup Flow -->
              <div v-else>
                <q-stepper
                  v-model="setupStep"
                  vertical
                  color="primary"
                  animated
                >
                  <!-- Step 1: Show QR Code -->
                  <q-step
                    :name="1"
                    :title="$t('dashboard.settings.twoFactor.setup.scanQR')"
                    icon="eva-qr-code"
                    :done="setupStep > 1"
                  >
                    <div class="q-py-md">
                      <p class="text-body2 q-mb-md">
                        {{ $t('dashboard.settings.twoFactor.setup.scanInstructions') }}
                      </p>

                      <div
                        v-if="qrCode"
                        class="text-center q-my-md"
                      >
                        <div
                          v-html="qrCode"
                          class="inline-block"
                        ></div>
                      </div>

                      <q-expansion-item
                        :label="$t('dashboard.settings.twoFactor.setup.cantScan')"
                        dense
                        class="q-mt-md"
                      >
                        <div class="q-pa-md bg-grey-1">
                          <p class="text-caption q-mb-sm">
                            {{ $t('dashboard.settings.twoFactor.setup.manualEntry') }}
                          </p>
                          <code class="text-mono">{{ secretKey }}</code>
                          <q-btn
                            flat
                            dense
                            icon="eva-copy"
                            @click="copySecretKey"
                            class="q-ml-sm"
                          />
                        </div>
                      </q-expansion-item>
                    </div>

                    <q-stepper-navigation>
                      <q-btn
                        color="primary"
                        @click="setupStep = 2"
                        :label="$t('common.buttons.next')"
                      />
                      <q-btn
                        flat
                        @click="cancelEnableMFA"
                        :label="$t('common.buttons.cancel')"
                        class="q-ml-sm"
                      />
                    </q-stepper-navigation>
                  </q-step>

                  <!-- Step 2: Verify Code -->
                  <q-step
                    :name="2"
                    :title="$t('dashboard.settings.twoFactor.setup.verify')"
                    icon="eva-checkmark-circle"
                    :done="setupStep > 2"
                  >
                    <div class="q-py-md">
                      <p class="text-body2 q-mb-md">
                        {{ $t('dashboard.settings.twoFactor.setup.verifyInstructions') }}
                      </p>

                      <q-input
                        v-model="verificationCode"
                        :label="$t('dashboard.settings.twoFactor.setup.verificationCode')"
                        mask="### ###"
                        unmasked-value
                        outlined
                        autofocus
                        @keyup.enter="confirmEnableMFA"
                      />

                      <!-- Errors -->
                      <TaskErrors
                        class="q-mt-sm"
                        :task-errors="confirmMFATask.errors.value"
                      />
                    </div>

                    <q-stepper-navigation>
                      <q-btn
                        color="primary"
                        @click="confirmEnableMFA"
                        :label="$t('dashboard.settings.twoFactor.setup.verifyButton')"
                        :loading="confirmMFATask.isActive.value"
                      />
                      <q-btn
                        flat
                        @click="setupStep = 1"
                        :label="$t('common.buttons.back')"
                        class="q-ml-sm"
                      />
                    </q-stepper-navigation>
                  </q-step>

                  <!-- Step 3: Recovery Codes -->
                  <q-step
                    :name="3"
                    :title="$t('dashboard.settings.twoFactor.setup.recoveryCodes')"
                    icon="eva-shield"
                  >
                    <div class="q-py-md">
                      <q-banner
                        class="bg-warning text-white q-mb-md"
                        rounded
                      >
                        <template v-slot:avatar>
                          <q-icon name="eva-alert-triangle" />
                        </template>
                        {{ $t('dashboard.settings.twoFactor.setup.recoveryWarning') }}
                      </q-banner>

                      <div class="row q-col-gutter-sm">
                        <div
                          v-for="code in recoveryCodes"
                          :key="code"
                          class="col-6"
                        >
                          <q-chip
                            class="full-width text-mono"
                            color="grey-3"
                          >
                            {{ code }}
                          </q-chip>
                        </div>
                      </div>

                      <q-btn
                        flat
                        color="primary"
                        icon="eva-download"
                        :label="$t('dashboard.settings.twoFactor.setup.downloadCodes')"
                        @click="downloadRecoveryCodes"
                        class="q-mt-md"
                      />
                    </div>

                    <q-stepper-navigation>
                      <q-btn
                        color="primary"
                        @click="finishSetup"
                        :label="$t('common.buttons.finish')"
                      />
                    </q-stepper-navigation>
                  </q-step>
                </q-stepper>
              </div>
            </div>

            <!-- Disable MFA -->
            <div v-else>
              <div class="row items-center">
                <div class="col">
                  <p class="text-body2 q-mb-none">
                    {{ $t('dashboard.settings.twoFactor.enabled') }}
                  </p>
                </div>
                <div class="col-auto">
                  <q-btn
                    color="negative"
                    :label="$t('dashboard.settings.twoFactor.disable')"
                    @click="showDisableDialog = true"
                  />
                </div>
              </div>

              <q-separator class="q-my-md" />

              <div>
                <p class="text-body2 text-grey-7 q-mb-sm">
                  {{ $t('dashboard.settings.twoFactor.recoveryCodes') }}
                </p>
                <q-btn
                  outline
                  color="primary"
                  size="sm"
                  :label="$t('dashboard.settings.twoFactor.viewRecoveryCodes')"
                  @click="viewRecoveryCodes"
                  :loading="viewRecoveryCodesTask.isActive.value"
                />
              </div>

              <!-- Errors -->
              <TaskErrors
                class="q-mt-md"
                :task-errors="disableMFATask.errors.value"
              />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Disable MFA Dialog -->
    <q-dialog v-model="showDisableDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">{{ $t('dashboard.settings.twoFactor.disableDialog.title') }}</div>
        </q-card-section>

        <q-card-section>
          <p>{{ $t('dashboard.settings.twoFactor.disableDialog.message') }}</p>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            :label="$t('common.buttons.cancel')"
            v-close-popup
          />
          <q-btn
            flat
            color="negative"
            :label="$t('dashboard.settings.twoFactor.disableDialog.confirm')"
            @click="disableMFA"
            :loading="disableMFATask.isActive.value"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Recovery Codes Dialog -->
    <q-dialog v-model="showRecoveryDialog">
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">{{ $t('dashboard.settings.twoFactor.recoveryDialog.title') }}</div>
        </q-card-section>

        <q-card-section>
          <div class="row q-col-gutter-sm">
            <div
              v-for="code in currentRecoveryCodes"
              :key="code"
              class="col-6"
            >
              <q-chip
                class="full-width text-mono"
                color="grey-3"
              >
                {{ code }}
              </q-chip>
            </div>
          </div>

          <!-- Errors -->
          <TaskErrors
            class="q-mt-md"
            :task-errors="regenerateCodesTask.errors.value"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            color="primary"
            icon="eva-refresh"
            :label="$t('dashboard.settings.twoFactor.regenerateCodes')"
            @click="regenerateRecoveryCodes"
            :loading="regenerateCodesTask.isActive.value"
          />
          <q-btn
            flat
            :label="$t('common.buttons.close')"
            v-close-popup
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Password Confirmation Dialog -->
    <q-dialog
      v-model="showPasswordConfirm"
      persistent
    >
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">{{ $t('dashboard.settings.twoFactor.confirmPassword.title') }}</div>
        </q-card-section>

        <q-card-section>
          <p class="q-mb-md">{{ $t('dashboard.settings.twoFactor.confirmPassword.message') }}</p>

          <q-form @submit.prevent="confirmPasswordAndEnable">
            <q-input
              v-model="confirmPassword"
              :label="$t('auth.forms.common.password')"
              type="password"
              outlined
              autofocus
            />

            <!-- Errors -->
            <TaskErrors
              class="q-mt-sm"
              :task-errors="confirmPasswordTask.errors.value"
            />
          </q-form>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            :label="$t('common.buttons.cancel')"
            @click="resetMFAState"
          />
          <q-btn
            flat
            color="primary"
            :label="$t('common.buttons.confirm')"
            @click="confirmPasswordAndEnable"
            :loading="confirmPasswordTask.isActive.value"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
