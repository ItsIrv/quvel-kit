import { Service } from 'src/modules/Core/services/Service';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import type { RegisterService } from 'src/modules/Core/types/service.types';
import { ApiService } from 'src/modules/Core/services/ApiService';
import type { IUser } from 'src/modules/Core/types/user.types';
import { AuthStatusEnum } from '../enums/AuthStatusEnum';

/**
 * Service responsible for handling login, registration, and session management.
 */
export class AuthService extends Service implements RegisterService {
  private api!: ApiService;

  /**
   * Registers the service with the container.
   *
   * @param container - The service container instance.
   */
  register({ api }: ServiceContainer): void {
    this.api = api;
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
   * @param recaptchaToken - Google reCAPTCHA token for verification
   * @returns The registration status and user data.
   */
  async signUp(
    email: string,
    password: string,
    name: string,
    recaptchaToken?: string,
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
      captcha_token: recaptchaToken,
    });
  }
}
