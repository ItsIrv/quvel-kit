import { defineBoot } from '@quasar/app-vite/wrappers';
import { useContainer } from 'src/modules/Core/composables/useContainer';

/**
 * Boot function to register global dynamic services.
 */
export default defineBoot(({ ssrContext }) => {
  let container = ssrContext?.$container;

  if (!container) {
    container = useContainer();
  }
});
