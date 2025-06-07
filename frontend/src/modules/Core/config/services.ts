import type { ServiceClass } from 'src/modules/Core/types/service.types';
import { ConfigService } from 'src/modules/Core/services/ConfigService';
import { LogService } from 'src/modules/Core/services/LogService';
import { ApiService } from 'src/modules/Core/services/ApiService';
import { TaskService } from 'src/modules/Core/services/TaskService';
import { ValidationService } from 'src/modules/Core/services/ValidationService';
import { I18nService } from 'src/modules/Core/services/I18nService';

export function getAllServices(): Map<string, ServiceClass> {
  const serviceClasses = new Map<string, ServiceClass>([
    ['ConfigService', ConfigService],
    ['LogService', LogService],
    ['ApiService', ApiService],
    ['TaskService', TaskService],
    ['I18nService', I18nService],
    ['ValidationService', ValidationService],
  ]);

  return serviceClasses;
}
