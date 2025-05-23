<script lang="ts" setup>
import { ref } from 'vue';
import { useQuasar } from 'quasar';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import NotificationBell from 'src/modules/Notifications/components/NotificationBell.vue';
import UserDropdownMenu from 'src/modules/Auth/components/UserDropdownMenu.vue';

/**
 * Emits
 */
const emits = defineEmits(['login-click', 'open-left-drawer']);

/**
 * Services
 */
const sessionStore = useSessionStore();
const $q = useQuasar();

/**
 * Refs
 */
const isMenuOpen = ref(false);

/**
 * Opens the dropdown menu or left drawer based on platform.
 */
function onDropdownToggle(event: Event) {
  // On mobile, emit instead
  if ($q.platform.is.desktop) {
    // Prevent event propagation to avoid immediate closing
    event.stopPropagation();
    isMenuOpen.value = !isMenuOpen.value;
  } else {
    emits('open-left-drawer');
  }
}

/**
 * Handle menu close
 */
function onMenuClose() {
  isMenuOpen.value = false;
}
</script>

<template>
  <div
    v-if="sessionStore.isAuthenticated"
    class="AuthMenu"
  >
    <div class="AuthMenu-Inner">
      <NotificationBell class="AuthMenu-Bell" />

      <q-btn
        flat
        no-caps
        dense
        class="AuthMenu-DropdownToggle"
        @click="onDropdownToggle"
      >
        <span>{{ sessionStore.user?.name }}</span>

        <q-icon
          name="eva-chevron-down-outline"
          size="16px"
        />

        <UserDropdownMenu
          v-if="$q.platform.is.desktop"
          v-model="isMenuOpen"
          @close="onMenuClose"
        />
      </q-btn>
    </div>
  </div>

  <template v-else>
    <!-- Login Button -->
    <q-btn
      :ripple="false"
      :size="$q.screen.gt.sm ? 'md' : 'sm'"
      class="AuthMenu-LoginButton"
      unelevated
      @click="emits('login-click')"
    >
      {{ $t('auth.forms.login.button') }}
    </q-btn>
  </template>
</template>

<style lang="scss">
@reference '../../../../../css/tailwind.scss';

.AuthMenu {
  @apply tw:flex tw:items-center;

  &-Inner {
    @apply tw:relative tw:flex tw:items-center;
  }

  &-Bell {
    @apply tw:hidden tw:sm:flex tw:mr-3;
  }

  &-DropdownToggle {
    @apply tw:items-center tw:px-2 tw:rounded-lg tw:hover:bg-gray-100 tw:dark:hover:bg-gray-800 tw:transition-colors;

    span {
      @apply tw:text-sm tw:font-medium;
    }
  }

  &-MobileToggle {
    @apply tw:flex tw:md:hidden;
  }

  &-Avatar {
    @apply tw:w-10 tw:h-10 tw:rounded-full tw:border tw:border-stone-400 tw:dark:border-gray-600 tw:shadow-sm;
  }

  &-LoginButton {
    @extend .PrimaryButton;
  }
}

.GenericBorder {
  @apply tw:border-2 tw:border-stone-100 tw:dark:border-gray-700;
}

.Button {
  @extend .GenericBorder;
  @apply tw:bg-transparent tw:text-stone-700 tw:dark:text-gray-300 tw:px-4 tw:py-1 tw:rounded-lg;
}

.PrimaryButton {
  @extend .Button;
  background: linear-gradient(135deg,
      rgba(255, 105, 180, 0.15) 0%,
      rgba(255, 165, 0, 0.2) 30%,
      rgba(255, 69, 0, 0.25) 100%);

  .dark & {
    background: linear-gradient(135deg,
        rgba(139, 92, 246, 0.15) 0%,
        rgba(99, 102, 241, 0.2) 30%,
        rgba(59, 130, 246, 0.25) 100%);
  }
}
</style>
