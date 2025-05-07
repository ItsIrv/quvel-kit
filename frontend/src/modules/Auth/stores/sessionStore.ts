import { acceptHMRUpdate, defineStore } from 'pinia';
import { User } from 'src/modules/Core/models/User';
import type { IUser } from 'src/modules/Core/types/user.types';
import { AuthStatusEnum } from '../enums/AuthStatusEnum';
import { AuthService } from '../services/AuthService';

/**
 * Type for the authenticated user.
 */
type StateUser = User | null;

/**
 * Interface defining the structure of the session state.
 */
interface SessionState {
  user: StateUser;
  resultChannel: {
    unsubscribe: () => void;
  } | null;
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
  fetchSession(): Promise<IUser | null>;
  logout(): Promise<void>;
  login(email: string, password: string): Promise<void>;
  signUp(email: string, password: string, name: string): Promise<AuthStatusEnum>;
  loginWithOAuth(provider: string, stateless: boolean): Promise<void>;
}

/**
 * Pinia store for managing user session state.
 */
export const useSessionStore = defineStore<'session', SessionState, SessionGetters, SessionActions>(
  'session',
  {
    state: (): SessionState => ({
      user: null,
      resultChannel: null,
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
       */
      setSession(data: IUser) {
        this.user = User.fromApi(data);
      },

      /**
       * Fetches the user session from the API.
       */
      async fetchSession(): Promise<IUser | null> {
        const authService = this.$container.getService<AuthService>('auth');
        const userData = await authService.fetchSession();

        if (userData) {
          this.setSession(userData);
        }

        return userData;
      },

      /**
       * Logs the user out and resets the session.
       */
      async logout(): Promise<void> {
        const authService = this.$container.getService<AuthService>('auth');
        await authService.logout();

        this.user = null;
      },

      /**
       * Logs in the user and sets the session.
       */
      async login(email: string, password: string): Promise<void> {
        const authService = this.$container.getService<AuthService>('auth');
        const { user } = await authService.login(email, password);

        this.setSession(user);
      },

      /**
       * Signs up a new user and sets the session.
       */
      async signUp(email: string, password: string, name: string): Promise<AuthStatusEnum> {
        const authService = this.$container.getService<AuthService>('auth');
        const { status, user } = await authService.signUp(email, password, name);

        if (status === AuthStatusEnum.LOGIN_SUCCESS) {
          this.setSession(user);
        }

        return status;
      },

      /**
       * OAuth Flow: Request nonce, store it, and redirect.
       */
      async loginWithOAuth(provider: string, stateless: boolean) {
        const authService = this.$container.getService<AuthService>('auth');

        // Clean up any existing channel subscription
        if (this.resultChannel) {
          this.resultChannel.unsubscribe();
          this.resultChannel = null;
        }

        // Handle the OAuth flow through the service
        this.resultChannel = await authService.loginWithOAuth(provider, stateless, (user: IUser) =>
          this.setSession(user),
        );
      },
    },
  },
);

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useSessionStore, import.meta.hot));
}
