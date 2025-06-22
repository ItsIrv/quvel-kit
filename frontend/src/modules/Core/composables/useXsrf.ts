import { useQuasar } from 'quasar';
import { onMounted } from 'vue';
import { XsrfName } from 'src/modules/Auth/models/Session';
import { useContainer } from 'src/modules/Core/composables/useContainer';

/**
 * Sets the XSRF-TOKEN cookie if not already set.
 * Supports tenant-aware CSRF tokens.
 */
export function useXsrf(): void {
  const $q = useQuasar();

  onMounted(() => {
    // Get tenant ID from config service (if available)
    const { config } = useContainer();
    const tenantId = config.isTenantConfig() ? config.getTenantId() : null;
    
    // Use tenant-specific cookie name if tenant ID is available
    const cookieName = tenantId && typeof tenantId === 'string' ? `${XsrfName}-${tenantId}` : XsrfName;
    const xsrf = $q.cookies.get(cookieName);

    if (xsrf === null) {
      try {
        void useContainer().api.get('/sanctum/csrf-cookie');
      } catch {
        //
      }
    }
  });
}
