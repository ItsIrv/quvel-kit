import { defineBoot } from '#q-app/wrappers';
import { createUserFromApi } from 'src/modules/User/factories/userFactory';
/**
 * Boot function to hydrate the session store with real objects.
 */
export default defineBoot(({ store }) => {
  if (store.state.value.session && store.state.value.session.user) {
    store.state.value.session.user = createUserFromApi(store.state.value.session.user);
  }
});
