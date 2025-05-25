import { useQuasar } from 'quasar';
import { onMounted } from 'vue';
import { XsrfName } from 'src/modules/Auth/models/Session';
import { useContainer } from 'src/modules/Core/composables/useContainer';

/**
 * Sets the XSRF-TOKEN cookie if not already set.
 */
export function useXsrf(): void {
  const $q = useQuasar();

  onMounted(() => {
    const xsrf = $q.cookies.get(XsrfName);

    if (xsrf === null) {
      try {
        void useContainer().api.get('/sanctum/csrf-cookie');
      } catch {
        //
      }
    }
  });
}
