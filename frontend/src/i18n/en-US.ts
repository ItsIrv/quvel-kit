/**
 * English (US) translations
 *
 * This file imports and combines all English translations from different modules.
 * Add your own translations here to extend the core translations.
 */
import coreTranslations from 'src/modules/Core/i18n/en-US';
import { AuthModule } from 'src/modules/Auth';
import { NotificationsModule } from 'src/modules/Notifications';
import { QuvelModule } from 'src/modules/Quvel';

/**
 * Application-specific translations that extend the core translations
 */
const appTranslations = {
  // Add your custom translations here
  createdBy: 'Created by',
};

/**
 * Export combined translations
 */
export default {
  ...coreTranslations,
  ...(AuthModule.i18n?.()['en-US'] || {}),
  ...(NotificationsModule.i18n?.()['en-US'] || {}),
  ...appTranslations,
  ...(QuvelModule.i18n?.()['en-US'] || {}),
} as const;
