import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { showNotification } from 'src/utils/notifyUtil';
import OAuthStatusEnum, { normalizeOAuthStatus, mapStatusToType } from 'src/enums/OAuthStatusEnum';
import { useContainer } from 'src/composables/useContainer';

/**
 * Composable to handle OAuth messages passed via query params.
 */
export function useOAuthMessageHandler(): void {
  const route = useRoute();
  const router = useRouter();
  const $container = useContainer();

  onMounted(() => {
    const { message, ...query } = route.query;

    if (message && typeof message === 'string' && message.length > 0) {
      try {
        const decodedMessage = normalizeOAuthStatus(decodeURIComponent(message) as OAuthStatusEnum);

        if (!$container.i18n.te(decodedMessage)) {
          return;
        }

        showNotification(mapStatusToType(decodedMessage), $container.i18n.t(decodedMessage), {
          timeout: 8000,
          closeBtn: true,
        });

        router.replace({ query }).catch(() => {});
      } catch {
        // Ignore any decode issues silently
      }
    }
  });
}
