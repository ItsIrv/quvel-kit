import { acceptHMRUpdate, defineStore } from 'pinia';
import type { User } from 'src/models/User';
import type { IUser } from 'src/types/user.types';
import { createUserFromApi } from 'src/factories/userFactory';

/**
 * Type for the authenticated user.
 */
type StateUser = User | null;

/**
 * Interface defining the structure of the session state.
 */
interface SessionState {
  user: StateUser;
  // Don't try to re-authenticate when hydrating
  hasRun: boolean;
  // Oauth nonce
  nonce: string | null;
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
  login(email: string, password: string): Promise<User>;
  signUp(email: string, password: string, name: string): Promise<void>;
  setNonce(nonce: string): void;
  getNonce(): string | null;
  clearNonce(): void;
}

/**
 * Pinia store for managing user session state.
 */
export const useSessionStore = defineStore<'session', SessionState, SessionGetters, SessionActions>(
  'session',
  {
    state: (): SessionState => ({
      user: null,
      hasRun: false,
      nonce: null,
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
        if (!this.hasRun) {
          const { data } = await this.$container.api.get<{ data: IUser }>('/auth/session');

          this.setSession(data);
        }

        this.hasRun = true;
      },

      /**
       * Logs the user out and resets the session.
       */
      async logout(): Promise<void> {
        await this.$container.api.post('/auth/logout');

        this.user = null;
      },

      /**
       * Logs in the user and sets the session.
       * @param email - User's email.
       * @param password - User's password.
       */
      async login(email: string, password: string): Promise<User> {
        // TODO: Add helpers for validating data from the backend. This could be
        // for integrity, or for security when working with external sources.
        const { user } = await this.$container.api.post<{ message: string; user: IUser }>(
          '/auth/login',
          { email, password },
        );

        this.setSession(user);

        return this.user!;
      },

      /**
       * Signs up a new user and sets the session.
       * @param email - User's email.
       * @param password - User's password.
       * @param name - User's name.
       */
      async signUp(email: string, password: string, name: string): Promise<void> {
        await this.$container.api.post<{ message: string; user: IUser }>('/auth/register', {
          email,
          password,
          name,
        });
      },

      /**
       * Stores the OAuth nonce in the session store
       * @param nonce - The OAuth nonce
       */
      setNonce(nonce: string) {
        this.nonce = nonce;
      },

      /**
       * Retrieves the stored nonce
       */
      getNonce(): string | null {
        return this.nonce;
      },

      /**
       * Clears the nonce after use
       */
      clearNonce() {
        this.nonce = null;
      },
    },
  },
);

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useSessionStore, import.meta.hot));
}
