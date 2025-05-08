import { Service } from 'src/modules/Core/services/Service';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import type { RegisterService } from 'src/modules/Core/types/service.types';
import { ApiService } from 'src/modules/Core/services/ApiService';
import type { IUser } from 'src/modules/Core/types/user.types';
import { AuthStatusEnum } from '../enums/AuthStatusEnum';
import { OAuthStatusEnum } from '../enums/OAuthStatusEnum';
import { showNotification } from 'src/modules/Core/utils/notifyUtil';
import { mapStatusToType, normalizeKey } from 'src/modules/Core/composables/useQueryMessageHandler';
import { TaskService } from 'src/modules/Core/services/TaskService';
import { WebSocketService } from 'src/modules/Core/services/WebSocketService';
import { I18nService } from 'src/modules/Core/services/I18nService';
import { ConfigService } from 'src/modules/Core/services/ConfigService';

/**
 * Service responsible for handling authentication-related API requests.
 */
export class AuthService extends Service implements RegisterService {
  private api!: ApiService;
  private task!: TaskService;
  private ws!: WebSocketService;
  private i18n!: I18nService;
  private config!: ConfigService;

  /**
   * Registers the service with the container.
   *
   * @param container - The service container instance.
   */
  register({ api, task, ws, i18n, config }: ServiceContainer): void {
    this.api = api;
    this.task = task;
    this.ws = ws;
    this.i18n = i18n;
    this.config = config;
  }

  /**
   * Fetches the current user session.
   *
   * @returns The user data or null if not authenticated.
   */
  async fetchSession(): Promise<IUser | null> {
    try {
      const { data } = await this.api.get<{ data: IUser }>('/auth/session');
      return data;
    } catch {
      return null;
    }
  }

  /**
   * Logs the user out.
   */
  async logout(): Promise<void> {
    await this.api.post('/auth/logout');
  }

  /**
   * Authenticates a user with email and password.
   *
   * @param email - The user's email.
   * @param password - The user's password.
   * @returns The authenticated user data.
   */
  async login(email: string, password: string): Promise<{ user: IUser }> {
    return await this.api.post<{ message: string; user: IUser }>('/auth/login', {
      email,
      password,
    });
  }

  /**
   * Registers a new user.
   *
   * @param email - The user's email.
   * @param password - The user's password.
   * @param name - The user's name.
   * @returns The registration status and user data.
   */
  async signUp(
    email: string,
    password: string,
    name: string,
  ): Promise<{
    status: AuthStatusEnum;
    user: IUser;
  }> {
    return await this.api.post<{
      status: AuthStatusEnum;
      user: IUser;
    }>('/auth/register', {
      email,
      password,
      name,
    });
  }

  /**
   * Creates a nonce for OAuth authentication.
   *
   * @param provider - The OAuth provider.
   * @returns The generated nonce.
   */
  async createOAuthNonce(provider: string): Promise<{ nonce: string }> {
    return await this.api.post<{ nonce: string }>(`/auth/provider/${provider}/create-nonce`);
  }

  /**
   * Redeems an OAuth nonce after successful authentication.
   *
   * @param provider - The OAuth provider.
   * @param nonce - The nonce to redeem.
   * @returns The authenticated user data.
   */
  async redeemOAuthNonce(provider: string, nonce: string): Promise<{ user: IUser }> {
    return await this.api.post<{ user: IUser }>(`/auth/provider/${provider}/redeem-nonce`, {
      nonce,
    });
  }

  /**
   * Generates the OAuth redirect URL.
   *
   * @param provider - The OAuth provider.
   * @param nonce - Optional nonce for stateless authentication.
   * @returns The full redirect URL.
   */
  getOAuthRedirectUrl(provider: string, nonce?: string): string {
    const baseUrl = `${this.config.get('apiUrl')}/auth/provider/${provider}/redirect`;

    if (!nonce) {
      return baseUrl;
    }

    return `${baseUrl}?nonce=${encodeURIComponent(nonce)}`;
  }

  /**
   * Handles the OAuth authentication flow with WebSocket subscription.
   *
   * @param provider - The OAuth provider.
   * @param nonce - The nonce for authentication.
   * @param onUserAuthenticated - Callback for when a user is successfully authenticated.
   * @returns A function to unsubscribe from the WebSocket channel.
   */
  async handleOAuthFlow(
    provider: string,
    nonce: string,
    onUserAuthenticated: (user: IUser) => void,
  ): Promise<{ unsubscribe: () => void }> {
    const channelName = `auth.nonce.${nonce}`;

    const channel = await this.ws.subscribe({
      channelName,
      type: 'public',
      event: '.oauth.result',
      callback: ({ status }: { status: OAuthStatusEnum }) => {
        channel.unsubscribe();

        status = normalizeKey(status, 'auth') as OAuthStatusEnum;

        if (status !== OAuthStatusEnum.LOGIN_SUCCESS) {
          showNotification(mapStatusToType(status), this.i18n.t(status), {
            timeout: 8000,
            closeBtn: true,
          });
          return;
        }

        void this.task
          .newTask<{ user: IUser }>({
            showLoading: true,
            showNotification: {
              success: () => this.i18n.t('auth.status.success.loggedIn'),
              error: () => this.i18n.t('auth.status.errors.login'),
            },
            task: async (): Promise<{ user: IUser }> =>
              await this.redeemOAuthNonce(provider, nonce),
            successHandlers: ({ user }): void => {
              onUserAuthenticated(user);
            },
          })
          .run();
      },
    });

    return channel;
  }

  /**
   * Performs the complete OAuth authentication flow.
   *
   * @param provider - The OAuth provider.
   * @param stateless - Whether to use stateless authentication.
   * @param onUserAuthenticated - Callback for when a user is successfully authenticated.
   * @returns A function to unsubscribe from the WebSocket channel, or null if using non-stateless flow.
   */
  async loginWithOAuth(
    provider: string,
    stateless: boolean,
    onUserAuthenticated: (user: IUser) => void,
  ): Promise<{ unsubscribe: () => void } | null> {
    if (!stateless) {
      window.location.href = this.getOAuthRedirectUrl(provider);
      return null;
    }

    try {
      const { nonce } = await this.createOAuthNonce(provider);
      const channel = await this.handleOAuthFlow(provider, nonce, onUserAuthenticated);

      window.location.href = this.getOAuthRedirectUrl(provider, nonce);

      return channel;
    } catch {
      showNotification('negative', this.i18n.t('common.task.error'));
      return null;
    }
  }

  /**
   * Sends a password reset link to the user's email.
   *
   * @param email - The user's email.
   * @returns A promise that resolves when the request is complete.
   */
  sendPasswordResetLink(email: string): Promise<void> {
    return this.api.post('/auth/forgot-password', { email });
  }
}
