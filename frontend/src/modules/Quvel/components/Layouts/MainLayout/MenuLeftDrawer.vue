<script lang="ts" setup>
import { computed } from 'vue';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { DashboardRoutes } from 'src/modules/Dashboard/router/constants';

/**
 * Props
 */
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
});

/**
 * Emits
 */
const emits = defineEmits(['update:modelValue']);

/**
 * Computed
 */
const inputValue = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value),
});

/**
 * Navigation Menu Items
 */
const navigationItems = computed(() => {
  const items = [];
  
  // Add dashboard link if user is authenticated
  if (sessionStore.isAuthenticated) {
    items.push({
      to: { name: DashboardRoutes.DASHBOARD },
      icon: 'eva-home-outline',
      labelKey: 'dashboard.title',
    });
  }
  
  return items;
});

/**
 * Services
 */
const { task, i18n } = useContainer();
const sessionStore = useSessionStore();

/**
 * Logout Task
 *
 * Handles user logout and updates session state.
 */
const logoutTask = task.newTask({
  showNotification: {
    success: () => i18n.t('auth.status.success.loggedOut'),
  },
  task: async () => {
    await sessionStore.logout();
    emits('update:modelValue', false); // Close the drawer after logout
  },
});
</script>

<template>
  <q-drawer
    v-model="inputValue"
    class="MenuLeftDrawer DialogGradient"
    side="left"
    overlay
    behavior="mobile"
    bordered
  >
    <div class="MenuLeftDrawer-Content">
      <!-- User Panel with profile info -->
      <div class="MenuLeftDrawer-UserPanel">
        <div class="MenuLeftDrawer-UserInfo">
          <img
            :src="sessionStore.user?.avatarUrl || 'https://api.dicebear.com/7.x/avataaars/svg?seed=44'"
            alt="User Avatar"
            class="MenuLeftDrawer-Avatar"
          />

          <div class="MenuLeftDrawer-UserDetails">
            <h5 class="MenuLeftDrawer-UserDetails-Name">{{ sessionStore.user?.name }}</h5>
            <p class="MenuLeftDrawer-UserDetails-Email">
              {{ sessionStore.user?.email }}
            </p>
          </div>
        </div>

        <!-- Online Status Badge -->
        <div class="MenuLeftDrawer-StatusBadge">
          <span class="MenuLeftDrawer-StatusBadge-Dot"></span>
          <span>{{ $t('quvel.common.online') }}</span>
        </div>
      </div>

      <!-- Navigation Links -->
      <div class="MenuLeftDrawer-Navigation">
        <div class="MenuLeftDrawer-Section">
          <h6 class="MenuLeftDrawer-Section-Title">{{ $t('quvel.common.account') }}</h6>

          <div class="MenuLeftDrawer-MenuItems">
            <!-- Navigation Menu Items Loop -->
            <router-link
              v-for="item in navigationItems"
              :key="item.to.name"
              :to="item.to"
              custom
              v-slot="{ navigate }"
            >
              <div
                class="MenuLeftDrawer-MenuItem"
                :class="{ 'MenuLeftDrawer-MenuItem--active': $route.name === item.to.name }"
                @click="navigate(); emits('update:modelValue', false)"
              >
                <q-icon
                  :name="item.icon"
                  size="sm"
                  class="MenuLeftDrawer-MenuItem-Icon"
                />
                <span class="MenuLeftDrawer-MenuItem-Text">{{ $t(item.labelKey) }}</span>
              </div>
            </router-link>

            <!-- Logout Link (separate) -->
            <div
              class="MenuLeftDrawer-MenuItem MenuLeftDrawer-MenuItem--danger"
              :class="{ 'MenuLeftDrawer-MenuItem--disabled': logoutTask.isActive.value }"
              @click="logoutTask.run()"
            >
              <q-icon
                name="eva-log-out-outline"
                size="sm"
                class="MenuLeftDrawer-MenuItem-Icon"
              />
              <span class="MenuLeftDrawer-MenuItem-Text">{{ $t('auth.forms.logout.button') }}</span>
              <q-spinner
                v-if="logoutTask.isActive.value"
                color="negative"
                size="1.5em"
                class="MenuLeftDrawer-MenuItem-Spinner"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Footer with version info -->
      <div class="MenuLeftDrawer-Footer">
        <div class="MenuLeftDrawer-Footer-Version">QuVel Kit v0.1.3-beta</div>
      </div>
    </div>
  </q-drawer>
</template>
