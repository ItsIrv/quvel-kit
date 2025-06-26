import type { ModuleLoader } from 'src/modules/Core/types/module.types';
import { AuthModule } from 'src/modules/Auth';
import { NotificationsModule } from 'src/modules/Notifications';
import { QuvelModule } from 'src/modules/Quvel';
import { CoreModule } from 'src/modules/Core';
import { DashboardModule } from 'src/modules/Dashboard';

/**
 * Get all registered modules
 */
export function getAllModules(): Record<string, ModuleLoader> {
  return {
    CoreModule,
    AuthModule,
    NotificationsModule,
    QuvelModule,
    DashboardModule,
  };
}
