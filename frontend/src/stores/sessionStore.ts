import { acceptHMRUpdate, defineStore } from 'pinia';
import { User } from 'src/models/User';
import { IUser } from 'src/types/user.types';
import { createUserFromApi } from 'src/factories/userFactory';

/**
 * Type for the authenticated user.
 */
type StateUser = User | null | undefined;

/**
 * Interface defining the structure of the session state.
 */
interface SessionState {
  user: StateUser;
}

/**
 * Interface defining getters for the session store.
 */
type SessionGetters = {
  isAuthenticated: (state: SessionState) => boolean;
  getUser: (state: SessionState) => StateUser;
};

/**
 * Interface defining actions for the session store.
 */
interface SessionActions {
  setSession(data: IUser): void;
  fetchSession(): Promise<void>;
  logout(): Promise<void>;
  login(email: string, password: string): Promise<void>;
}

/**
 * Pinia store for managing user session state.
 */
export const useSessionStore = defineStore<'session', SessionState, SessionGetters, SessionActions>(
  'session',
  {
    state: (): SessionState => ({
      user: undefined,
    }),

    getters: {
      /**
       * Determines if a user is authenticated.
       */
      isAuthenticated: (state) => state.user !== null && state.user !== undefined,

      /**
       * Retrieves the authenticated user.
       */
      getUser: (state) => state.user,
    },

    actions: {
      /**
       * Sets the user session.
       * @param data - User data from API response.
       */
      setSession(data: IUser) {
        this.user = createUserFromApi(data);
      },

      /**
       * Fetches the user session from the API if not previously attempted.
       */
      async fetchSession(): Promise<void> {
        if (this.user === undefined) {
          try {
            const { data } = await this.$container.api.get<IUser>('/session');

            this.setSession(data);
          } catch {
            this.user = null;
          }
        }
      },

      /**
       * Logs the user out and resets the session.
       */
      async logout(): Promise<void> {
        try {
          await this.$container.api.post('/logout');

          this.$reset();
        } catch {
          // TODO: Handle specific error codes
        }
      },

      /**
       * Logs in the user and sets the session.
       * @param email - User's email.
       * @param password - User's password.
       */
      async login(email: string, password: string): Promise<void> {
        try {
          const { data } = await this.$container.api.post<IUser>('/login', { email, password });

          this.setSession(data);
        } catch {
          // TODO: Handle specific error codes
        }
      },
    },
  },
);

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useSessionStore, import.meta.hot));
}
