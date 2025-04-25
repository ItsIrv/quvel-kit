import { acceptHMRUpdate, defineStore } from 'pinia';
import type { User } from 'src/modules/User/models/User';
import type { IUser } from 'src/modules/Core/types/user.types';
import { createUserFromApi } from 'src/modules/User/factories/userFactory';
import { showNotification } from 'src/modules/Core/utils/notifyUtil';
import OAuthStatusEnum, {
  mapStatusToType,
  normalizeOAuthStatus,
} from 'src/modules/Auth/enums/OAuthStatusEnum';

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
  fetchSession(): Promise<void>;
  logout(): Promise<void>;
  login(email: string, password: string): Promise<User>;
  signUp(email: string, password: string, name: string): Promise<void>;
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
        this.user = createUserFromApi(data);
      },

      /**
       * Fetches the user session from the API.
       */
      async fetchSession(): Promise<void> {
        try {
          const { data } = await this.$container.api.get<{ data: IUser }>('/auth/session');

          this.setSession(data);
        } catch {
          // TODO: Global error handling
        }
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
       */
      async login(email: string, password: string): Promise<User> {
        const { user } = await this.$container.api.post<{ message: string; user: IUser }>(
          '/auth/login',
          { email, password },
        );

        this.setSession(user);

        return this.user!;
      },

      /**
       * Signs up a new user and sets the session.
       */
      async signUp(email: string, password: string, name: string): Promise<void> {
        await this.$container.api.post<{ message: string; user: IUser }>('/auth/register', {
          email,
          password,
          name,
        });
      },

      /**
       * OAuth Flow: Request nonce, store it, and redirect.
       */

      async loginWithOAuth(provider: string, stateless: boolean) {
        const redirectBase = `${this.$container.config.get('api_url')}/auth/provider/${provider}/redirect`;

        if (!stateless) {
          window.location.href = redirectBase;
          return;
        }

        const createNonce = async (): Promise<{ nonce: string }> => {
          return await this.$container.api.post<{ nonce: string }>(
            `/auth/provider/${provider}/create-nonce`,
          );
        };

        const handleOAuthFlow = async (nonce: string) => {
          const wsService = this.$container.ws;
          const taskService = this.$container.task;
          const channelName = `auth.nonce.${nonce}`;

          if (this.resultChannel) {
            this.resultChannel.unsubscribe();
          }

          this.resultChannel = await wsService.subscribe({
            channelName,
            type: 'public',
            event: '.oauth.result',
            callback: ({ status }: { status: OAuthStatusEnum }) => {
              this.resultChannel!.unsubscribe();

              status = normalizeOAuthStatus(status);

              if (status !== OAuthStatusEnum.LOGIN_OK) {
                showNotification(mapStatusToType(status), this.$container.i18n.t(status), {
                  timeout: 8000,
                  closeBtn: true,
                });
                return;
              }

              void taskService
                .newTask<{ user: IUser }>({
                  showLoading: true,
                  showNotification: {
                    success: () => this.$container.i18n.t('auth.status.success.loggedIn'),
                    error: () => this.$container.i18n.t('auth.status.errors.login'),
                  },
                  task: async (): Promise<{ user: IUser }> =>
                    await this.$container.api.post<{ user: IUser }>(
                      `/auth/provider/${provider}/redeem-nonce`,
                      { nonce },
                    ),
                  successHandlers: ({ user }): void => {
                    this.setSession(user);
                  },
                })
                .run();
            },
          });
        };

        try {
          const { nonce } = await createNonce();

          void handleOAuthFlow(nonce);

          window.location.href = `${redirectBase}?nonce=${encodeURIComponent(nonce)}`;
        } catch {
          showNotification('negative', this.$container.i18n.t('common.task.error'));
        }
      },
    },
  },
);

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useSessionStore, import.meta.hot));
}
