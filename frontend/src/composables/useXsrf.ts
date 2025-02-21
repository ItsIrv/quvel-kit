import { useQuasar } from 'quasar';
import { onMounted } from 'vue';
import { createApi } from 'src/utils/axiosUtil';
import { XsrfName } from 'src/models/Session';

/**
 * Sets the XSRF-TOKEN cookie if not already set.
 */
export function useXsrf(): void {
  const $q = useQuasar();

  onMounted(() => {
    const xsrf = $q.cookies.get(XsrfName);

    if (xsrf === null) {
      try {
        void createApi().get('/');
      } catch {
        //
      }
    }
  });
}
