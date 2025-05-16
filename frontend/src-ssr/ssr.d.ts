import { TraceInfo } from 'src/modules/Core/types/logging.types';
import { TenantConfigProtected } from './types/tenant.types';

declare module 'express' {
  interface Request {
    tenantConfig: TenantConfigProtected;
    __TRACE__: TraceInfo;
  }
}
