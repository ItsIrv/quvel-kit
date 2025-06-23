/**
 * Spanish (Mexico) translations
 *
 * This file imports and combines all Spanish translations from different modules.
 * Add your own translations here to extend the core translations.
 */
import coreTranslations from 'src/modules/Core/i18n/es-MX';
import { AuthModule } from 'src/modules/Auth';
import { NotificationsModule } from 'src/modules/Notifications';
import { QuvelModule } from 'src/modules/Quvel';

/**
 * Application-specific translations that extend the core translations
 */
const appTranslations = {
  // Add your custom translations here
  createdBy: 'Creado por',
};

/**
 * Export combined translations
 */
export default {
  ...coreTranslations,
  ...(AuthModule.i18n?.()['es-MX'] || {}),
  ...(NotificationsModule.i18n?.()['es-MX'] || {}),
  ...appTranslations,
  ...(QuvelModule.i18n?.()['es-MX'] || {}),
} as const;
