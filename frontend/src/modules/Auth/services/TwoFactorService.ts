import { Service } from 'src/modules/Core/services/Service';
import { ApiService } from 'src/modules/Core/services/ApiService';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import type { RegisterService } from 'src/modules/Core/types/service.types';

/**
 * Interface for two-factor authentication response data
 */
export interface TwoFactorQRResponse {
  svg: string;
}

export interface TwoFactorSecretResponse {
  secretKey: string;
}

export interface TwoFactorRecoveryCodesResponse {
  recoveryCodes: string[];
}

/**
 * Service for managing two-factor authentication operations.
 */
export class TwoFactorService extends Service implements RegisterService {
  private api!: ApiService;

  /**
   * Registers the service.
   */
  register(container: ServiceContainer): void {
    this.api = container.api;
  }

  /**
   * Confirm user password before sensitive operations
   */
  async confirmPassword(password: string): Promise<void> {
    await this.api.post('/auth/user/confirm-password', { password });
  }

  /**
   * Check if password confirmation is still valid
   */
  async checkPasswordConfirmation(): Promise<{ confirmed: boolean }> {
    return await this.api.get('/auth/user/confirmed-password-status');
  }

  /**
   * Enable two-factor authentication for the current user
   */
  async enable(): Promise<void> {
    await this.api.post('/auth/user/two-factor-authentication');
  }

  /**
   * Disable two-factor authentication for the current user
   */
  async disable(): Promise<void> {
    await this.api.delete('/auth/user/two-factor-authentication');
  }

  /**
   * Get the QR code for setting up two-factor authentication
   */
  async getQRCode(): Promise<TwoFactorQRResponse> {
    return await this.api.get('/auth/user/two-factor-qr-code');
  }

  /**
   * Get the secret key for manual entry
   */
  async getSecretKey(): Promise<TwoFactorSecretResponse> {
    return await this.api.get('/auth/user/two-factor-secret-key');
  }

  /**
   * Confirm two-factor authentication setup with verification code
   */
  async confirm(code: string): Promise<void> {
    await this.api.post('/auth/user/confirmed-two-factor-authentication', { code });
  }

  /**
   * Get recovery codes for two-factor authentication
   */
  async getRecoveryCodes(): Promise<string[]> {
    return await this.api.get('/auth/user/two-factor-recovery-codes');
  }

  /**
   * Regenerate recovery codes for two-factor authentication
   */
  async regenerateRecoveryCodes(): Promise<string[]> {
    return await this.api.post('/auth/user/two-factor-recovery-codes');
  }
}
