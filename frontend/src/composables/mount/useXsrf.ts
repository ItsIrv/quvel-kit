import { useQuasar } from 'quasar';
import { onMounted } from 'vue';
import { createApi } from 'src/utils/axiosUtil';
import { XsrfName } from 'src/models/Session';

export function useXsrf(): void {
  const $q = useQuasar();

  onMounted(() => {
    const xsrf = $q.cookies.get(XsrfName);

    if (!xsrf) {
      try {
        void createApi().get('/');
      } catch {
        //
      }
    }
  });
}
