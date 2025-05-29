import type { TenantConfig } from './modules/Core/types/tenant.types';
import type { TraceInfo } from './modules/Core/types/logging.types';

declare namespace NodeJS {
  interface ProcessEnv {
    NODE_ENV: string;
    VUE_ROUTER_MODE: 'hash' | 'history' | 'abstract' | undefined;
    VUE_ROUTER_BASE: string | undefined;
  }
}

declare global {
  interface Window {
    __TENANT_CONFIG__: TenantConfig | null;
    __TRACE__: TraceInfo;
  }
}

export {};
