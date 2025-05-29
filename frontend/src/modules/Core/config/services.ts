import type { ServiceClass } from 'src/modules/Core/types/service.types';
import { ConfigService } from 'src/modules/Core/services/ConfigService';
import { LogService } from 'src/modules/Core/services/LogService';

export function getAllServices(): Map<string, ServiceClass> {
  const serviceClasses = new Map<string, ServiceClass>([
    ['ConfigService', ConfigService],
    ['LogService', LogService],
  ]);

  return serviceClasses;
}
