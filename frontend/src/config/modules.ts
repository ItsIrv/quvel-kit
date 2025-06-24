import type { ModuleLoader } from 'src/modules/Core/types/module.types';
import { AuthModule } from 'src/modules/Auth';
import { NotificationsModule } from 'src/modules/Notifications';
import { QuvelModule } from 'src/modules/Quvel';
import { CoreModule } from 'src/modules/Core';

/**
 * Registered Modules Configuration
 *
 * Add new modules here to make them available throughout the app.
 * This is the single place to configure which modules are active.
 */
export const registeredModules: Record<string, ModuleLoader> = {
  CoreModule,
  AuthModule,
  NotificationsModule,
  QuvelModule,
};

/**
 * Get all registered modules
 */
export function getAllModules(): Record<string, ModuleLoader> {
  return registeredModules;
}
