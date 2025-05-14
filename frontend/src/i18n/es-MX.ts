/**
 * Spanish (Mexico) translations
 *
 * This file imports and combines all Spanish translations from different modules.
 * Add your own translations here to extend the core translations.
 */
import coreTranslations from 'src/modules/Core/i18n/es-MX';
import authTranslations from 'src/modules/Auth/i18n/es-MX';
import notificationsTranslations from 'src/modules/Notifications/i18n/es-MX';

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
  ...authTranslations,
  ...notificationsTranslations,
  ...appTranslations,
} as const;
