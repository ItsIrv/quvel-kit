import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { showNotification } from 'src/modules/Core/utils/notifyUtil';
import { useContainer } from 'src/modules/Core/composables/useContainer';

type MessageHandlerOptions<T> = {
  key?: string;
  normalize?: (value: string) => T;
  type?: (normalized: T) => 'positive' | 'negative' | 'warning' | 'info';
  i18nPrefix?: string;
  timeout?: number;
  silent?: boolean;
};

/**
 * Generic composable to handle query-based messages (e.g., OAuth, verification, etc.)
 */
export function useQueryMessageHandler<T = string>(options: MessageHandlerOptions<T> = {}): void {
  const {
    key = 'message',
    normalize = (val: string) => val as unknown as T,
    type = () => 'info',
    i18nPrefix = '',
    timeout = 8000,
  } = options;

  const route = useRoute();
  const router = useRouter();
  const { i18n } = useContainer();

  onMounted(() => {
    const { [key]: rawMessage, ...rest } = route.query;

    if (typeof rawMessage !== 'string' || rawMessage.length === 0) return;

    try {
      const normalized = normalize(decodeURIComponent(rawMessage));
      const translationKey = i18nPrefix + normalized;

      if (!i18n.te(translationKey)) {
        return;
      }

      showNotification(type(normalized), i18n.t(translationKey), { timeout });

      router.replace({ query: rest }).catch(() => {});
    } catch {
      //
    }
  });
}

/**
 * Backend module language keys are in the format `module::key`,
 * while frontend language keys are in the format `module.key`.
 *
 * This function normalizes the key to the frontend format.
 */
export function normalizeKey(key: string, prefix: string = ''): string {
  return key.replace(`${prefix}::`, `${prefix}.`);
}

/**
 * Detect a "status.success", "status.warning", "status.error", or "status.info"
 * and return the appropriate type for the notification.
 */
export function mapStatusToType(status: string): 'positive' | 'warning' | 'negative' | 'info' {
  switch (true) {
    case status.includes('status.success'):
      return 'positive';
    case status.includes('status.warnings'):
      return 'warning';
    case status.includes('status.errors'):
      return 'negative';
    case status.includes('status.info'):
      return 'info';
    default:
      return 'info';
  }
}
