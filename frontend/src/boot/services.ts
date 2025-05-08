import { defineBoot } from '@quasar/app-vite/wrappers';
import { AuthService } from 'src/modules/Auth/services/AuthService';
import { CatalogService } from 'src/modules/Catalog/sevices/CatalogService';
import { useContainer } from 'src/modules/Core/composables/useContainer';

/**
 * Boot function to register global dynamic services.
 */
export default defineBoot(({ ssrContext }) => {
  let container = ssrContext?.$container;

  if (!container) {
    container = useContainer();
  }

  container.addService(CatalogService, new CatalogService());
  container.addService(AuthService, new AuthService());
});
