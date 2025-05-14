/**
 * English (US) translations
 *
 * This file imports and combines all English translations from different modules.
 * Add your own translations here to extend the core translations.
 */
import coreTranslations from 'src/modules/Core/i18n/en-US';
import authTranslations from 'src/modules/Auth/i18n/en-US';
import notificationsTranslations from 'src/modules/Notifications/i18n/en-US';

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
  ...authTranslations,
  ...notificationsTranslations,
  ...appTranslations,
} as const;
