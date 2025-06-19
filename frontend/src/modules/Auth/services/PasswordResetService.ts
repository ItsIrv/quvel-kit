import { Service } from 'src/modules/Core/services/Service';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import type { RegisterService } from 'src/modules/Core/types/service.types';
import { ApiService } from 'src/modules/Core/services/ApiService';

/**
 * Service responsible for handling authentication-related API requests.
 */
export class PasswordResetService extends Service implements RegisterService {
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
   * Sends a password reset link to the user's email.
   *
   * @param email - The user's email.
   * @param recaptchaToken - Google reCAPTCHA token for verification
   * @returns A promise that resolves when the request is complete.
   */
  sendPasswordResetLink(email: string, recaptchaToken?: string): Promise<void> {
    return this.api.post('/auth/forgot-password', {
      email,
      captcha_token: recaptchaToken,
    });
  }

  /**
   * Resets a user's password using a token.
   *
   * @param token - The password reset token.
   * @param email - The user's email.
   * @param password - The new password.
   * @param passwordConfirmation - The password confirmation.
   * @returns A promise that resolves when the password has been reset.
   */
  resetPassword(
    token: string,
    email: string,
    password: string,
    passwordConfirmation: string,
  ): Promise<void> {
    return this.api.post('/auth/reset-password', {
      token,
      email,
      password,
      password_confirmation: passwordConfirmation,
    });
  }
}
