import { TenantConfigProtected } from './types/tenant.types';

declare module 'express' {
  interface Request {
    tenantConfig: TenantConfigProtected;
  }
}
