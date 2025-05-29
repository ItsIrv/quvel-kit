import type { ServiceClass } from 'src/modules/Core/types/service.types';
import { ConfigService } from 'src/modules/Core/services/ConfigService';
import { LogService } from 'src/modules/Core/services/LogService';
import { ApiService } from 'src/modules/Core/services/ApiService';

export function getAllServices(): Map<string, ServiceClass> {
  const serviceClasses = new Map<string, ServiceClass>([
    ['ConfigService', ConfigService],
    ['LogService', LogService],
    ['ApiService', ApiService],
    // ['I18nService', I18nService],
    // ['ValidationService', ValidationService],
    // ['TaskService', TaskService],
    // ['WebSocketService', WebSocketService],
  ]);

  return serviceClasses;
}
