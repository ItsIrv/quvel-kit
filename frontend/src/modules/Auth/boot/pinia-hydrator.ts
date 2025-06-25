import { defineBoot } from '#q-app/wrappers';
import { User } from 'src/modules/Core/models/User';
/**
 * When hydrating, the stores objects are plain objects, not instances of the classes.
 * This boot function converts them back to instances.
 */
export default defineBoot(({ store }) => {
  if (store.state.value.session && store.state.value.session.user) {
    store.state.value.session.user = User.fromApi(store.state.value.session.user);
  }
});
