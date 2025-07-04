import { Service } from 'src/modules/Core/services/Service';
import { ApiService } from 'src/modules/Core/services/ApiService';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import type { RegisterService } from 'src/modules/Core/types/service.types';

/**
 * Service for handling two-factor authentication challenges during login.
 */
export class TwoFactorChallengeService extends Service implements RegisterService {
  private api!: ApiService;

  /**
   * Registers the service.
   */
  register(container: ServiceContainer): void {
    this.api = container.api;
  }

  /**
   * Submit two-factor authentication code to complete login
   */
  async submitCode(code: string): Promise<void> {
    await this.api.post('/auth/two-factor-challenge', {
      code,
    });
  }

  /**
   * Submit recovery code to complete login
   */
  async submitRecoveryCode(recovery_code: string): Promise<void> {
    await this.api.post('/auth/two-factor-challenge', {
      recovery_code,
    });
  }
}
