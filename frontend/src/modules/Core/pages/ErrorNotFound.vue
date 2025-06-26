<template>
  <div class="ErrorNotFound">
    <div class="ErrorNotFound-Container">
      <div class="ErrorNotFound-Content">
        <!-- Gradient Background Effect -->
        <div class="ErrorNotFound-Glow"></div>

        <!-- 404 Icon with Glow Effect -->
        <div class="ErrorNotFound-IconContainer SmallGlow">
          <q-icon
            name="eva-search-outline"
            size="80px"
            class="ErrorNotFound-Icon"
          />
        </div>

        <!-- Error Code -->
        <div class="ErrorNotFound-Code">
          404
        </div>

        <!-- Message -->
        <div class="ErrorNotFound-Title">
          {{ $t('common.errors.notFound.title') }}
        </div>

        <div class="ErrorNotFound-Description">
          {{ $t('common.errors.notFound.description') }}
        </div>

        <!-- Actions -->
        <div class="ErrorNotFound-Actions">
          <q-btn
            unelevated
            size="md"
            :to="homeRoute"
            class="PrimaryButton GenericBorder ErrorNotFound-HomeButton"
            no-caps
          >
            <q-icon
              name="eva-home-outline"
              size="18px"
              class="q-mr-xs"
            />
            {{ $t('common.errors.notFound.actions.home') }}
          </q-btn>

          <q-btn
            outline
            size="md"
            @click="goBack"
            class="Button GenericBorder ErrorNotFound-BackButton"
            no-caps
          >
            <q-icon
              name="eva-arrow-back-outline"
              size="18px"
              class="q-mr-xs"
            />
            {{ $t('common.errors.notFound.actions.back') }}
          </q-btn>
        </div>

        <!-- Help Text -->
        <div class="ErrorNotFound-Help">
          <p class="ErrorNotFound-HelpText">
            {{ $t('common.errors.notFound.help') }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { QuvelRoutes } from 'src/modules/Quvel/router/constants';
import { DashboardRoutes } from 'src/modules/Dashboard/router/constants';

const router = useRouter();
const sessionStore = useSessionStore();

// Determine home route based on auth status
const homeRoute = computed(() => {
  return sessionStore.isAuthenticated
    ? { name: DashboardRoutes.DASHBOARD }
    : { name: QuvelRoutes.LANDING };
});

const goBack = () => {
  if (window.history.length > 1) {
    router.go(-1);
  } else {
    void router.push(homeRoute.value);
  }
};
</script>

<style lang="scss" scoped>
@reference '../../../css/tailwind.scss';

.ErrorNotFound {
  @apply tw:min-h-screen tw:flex tw:items-center tw:justify-center tw:relative;
  background: linear-gradient(170deg, #f9fafb, #e6e7eb);

  .dark & {
    background: linear-gradient(150deg, #202b3b, #12171e);
  }

  &-Container {
    @apply tw:max-w-lg tw:mx-auto tw:px-8 tw:relative tw:z-10;
  }

  &-Content {
    @apply tw:text-center tw:relative;
  }

  &-Glow {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 300px;
    height: 300px;
    transform: translate(-50%, -60%);
    border-radius: 50%;
    background: radial-gradient(circle,
        rgba(59, 130, 246, 0.15) 0%,
        rgba(243, 85, 44, 0.05) 40%,
        rgba(255, 255, 255, 0) 70%);
    opacity: 0.8;
    z-index: -1;

    .dark & {
      background: radial-gradient(circle,
          rgba(99, 102, 241, 0.2) 0%,
          rgba(253, 106, 42, 0.1) 40%,
          rgba(30, 30, 46, 0) 70%);
    }
  }

  &-IconContainer {
    @apply tw:mb-8 tw:inline-block;
  }

  &-Icon {
    @apply tw:text-gray-400;

    .dark & {
      @apply tw:text-gray-500;
    }
  }

  &-Code {
    @apply tw:text-8xl tw:font-bold tw:mb-6;
    font-family: system-ui, -apple-system, sans-serif;
    background: linear-gradient(135deg,
        rgba(59, 130, 246, 0.8) 0%,
        rgba(243, 85, 44, 0.8) 50%,
        rgba(234, 83, 1, 0.8) 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);

    .dark & {
      background: linear-gradient(135deg,
          rgba(139, 92, 246, 0.9) 0%,
          rgba(99, 102, 241, 0.9) 50%,
          rgba(59, 130, 246, 0.9) 100%);
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 0 4px 20px rgba(139, 92, 246, 0.4);
    }
  }

  &-Title {
    @apply tw:text-2xl tw:font-semibold tw:text-gray-900 tw:mb-3;

    .dark & {
      @apply tw:text-gray-100;
    }
  }

  &-Description {
    @apply tw:text-gray-600 tw:mb-10 tw:leading-relaxed tw:max-w-md tw:mx-auto;

    .dark & {
      @apply tw:text-gray-300;
    }
  }

  &-Actions {
    @apply tw:flex tw:flex-col tw:sm:flex-row tw:gap-4 tw:justify-center tw:mb-10;
  }

  &-HomeButton,
  &-BackButton {
    @apply tw:px-6 tw:py-3 tw:font-medium tw:transition-all tw:duration-300;
    min-width: 140px;

    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(59, 130, 246, 0.25);
    }

    &:active {
      transform: translateY(0);
    }
  }

  &-BackButton {
    &:hover {
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);

      .dark & {
        box-shadow: 0 8px 25px rgba(255, 255, 255, 0.1);
      }
    }
  }

  &-Help {
    @apply tw:text-sm;
  }

  &-HelpText {
    @apply tw:text-gray-500 tw:leading-relaxed;

    .dark & {
      @apply tw:text-gray-400;
    }
  }
}

@media (max-width: 640px) {
  .ErrorNotFound {
    &-Container {
      @apply tw:px-6;
    }

    &-Code {
      @apply tw:text-6xl;
    }

    &-Title {
      @apply tw:text-xl;
    }

    &-Actions {
      .q-btn {
        @apply tw:w-full;
      }
    }

    &-Glow {
      width: 200px;
      height: 200px;
    }
  }
}
</style>
