import { TraceInfo } from 'src/modules/Core/types/logging.types';
import { AppConfigProtected, TenantConfigProtected } from './types/tenant.types';

declare module 'express' {
  interface Request {
    appConfig: AppConfigProtected | TenantConfigProtected;
    traceInfo: TraceInfo;
  }
}
