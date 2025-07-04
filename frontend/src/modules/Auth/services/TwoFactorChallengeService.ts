import { Service } from 'src/modules/Core/services/Service';
import { ApiService } from 'src/modules/Core/services/ApiService';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import type { RegisterService } from 'src/modules/Core/types/service.types';
import type { IUser } from 'src/modules/Core/types/user.types';

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
  async submitCode(code: string): Promise<{ user: IUser }> {
    return await this.api.post<{ message: string; user: IUser }>('/auth/two-factor-challenge', {
      code,
    });
  }

  /**
   * Submit recovery code to complete login
   */
  async submitRecoveryCode(recovery_code: string): Promise<{ user: IUser }> {
    return await this.api.post<{ message: string; user: IUser }>('/auth/two-factor-challenge', {
      recovery_code,
    });
  }
}