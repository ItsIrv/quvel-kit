import { Service } from 'src/modules/Core/services/Service';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import type { BootableService } from 'src/modules/Core/types/service.types';
import { ApiService } from 'src/modules/Core/services/ApiService';

export class EmailService extends Service implements BootableService {
  private api!: ApiService;

  register({ api }: ServiceContainer): void {
    this.api = api;
  }

  sendPasswordResetLink(email: string): Promise<void> {
    return this.api.post('/auth/forgot-password', { email });
  }
}
