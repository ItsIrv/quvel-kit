<script lang="ts" setup>
import { ref } from 'vue';
import { useSessionStore } from 'src/stores/sessionStore';
import { useContainer } from 'src/composables/useContainer';

const container = useContainer();
const sessionStore = useSessionStore();
const menuOpen = ref(false);

const logoutTask = container.task.newFrozenTask({
  showNotification: {
    success: container.i18n.t('auth.success.loggedOut'),
  },
  task: async () => {
    await sessionStore.logout();

    menuOpen.value = false;
  },
});
</script>

<template>
  <div class="relative">
    <!-- User Avatar -->
    <q-btn
      flat
      round
      dense
      @click="menuOpen = !menuOpen"
    >
      <img
        src="https://i.pravatar.cc/100"
        alt="User Avatar"
        class="w-10 h-10 rounded-full border border-stone-400 dark:border-gray-600 shadow-sm"
      />
    </q-btn>

    <!-- Dropdown Menu -->
    <transition name="fade">
      <div
        v-if="menuOpen"
        class="UserDropdown"
      >
        <p class="text-sm text-gray-900 dark:text-white font-semibold">
          {{ sessionStore.user?.name }}
        </p>
        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ sessionStore.user?.email }}</p>
        <q-btn
          color="negative"
          class="w-full"
          flat
          dense
          :label="$t('auth.forms.logout.button')"
          :loading="logoutTask.state.value === 'active'"
          @click="logoutTask.run()"
        />
      </div>
    </transition>
  </div>
</template>

<style lang="scss" scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease-in-out;
}

.fade-enter,
.fade-leave-to {
  opacity: 0;
}
</style>
