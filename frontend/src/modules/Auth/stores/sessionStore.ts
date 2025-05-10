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
  signUp(email: string, password: string, name: string, recaptchaToken?: string): Promise<AuthStatusEnum>;
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
        const userData = await this.$container.get(AuthService).fetchSession();

        if (userData) {
          this.setSession(userData);
        }

        return userData;
      },

      /**
       * Logs the user out and resets the session.
       */
      async logout(): Promise<void> {
        await this.$container.get(AuthService).logout();

        this.user = null;
      },

      /**
       * Logs in the user and sets the session.
       */
      async login(email: string, password: string): Promise<void> {
        const { user } = await this.$container.get(AuthService).login(email, password);

        this.setSession(user);
      },

      /**
       * Signs up a new user and sets the session.
       * 
       * @param email - User's email address
       * @param password - User's password
       * @param name - User's name
       * @param recaptchaToken - Google reCAPTCHA token for verification
       * @returns Authentication status
       */
      async signUp(email: string, password: string, name: string, recaptchaToken?: string): Promise<AuthStatusEnum> {
        const { status, user } = await this.$container
          .get(AuthService)
          .signUp(email, password, name, recaptchaToken);

        if (status === AuthStatusEnum.LOGIN_SUCCESS) {
          this.setSession(user);
        }

        return status;
      },

      /**
       * OAuth Flow: Request nonce, store it, and redirect.
       */
      async loginWithOAuth(provider: string, stateless: boolean) {
        // Clean up any existing channel subscription
        if (this.resultChannel) {
          this.resultChannel.unsubscribe();
          this.resultChannel = null;
        }

        // Handle the OAuth flow through the service
        this.resultChannel = await this.$container
          .get(AuthService)
          .loginWithOAuth(provider, stateless, (user: IUser) => this.setSession(user));
      },
    },
  },
);

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useSessionStore, import.meta.hot));
}
