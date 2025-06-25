import { TraceInfo } from 'src/modules/Core/types/logging.types';
import { AppConfigProtected, TenantConfigProtected } from './types/tenant.types';

export interface SSRRequestContext {
  startTime: number;
  appConfig: AppConfigProtected | TenantConfigProtected | null;
  traceInfo: TraceInfo;
}

declare module 'express' {
  interface Request {
    requestContext: SSRRequestContext;
  }
}
