/**
 * English (US) translations
 * 
 * This file imports and combines all English translations from different modules.
 * Add your own translations here to extend the core translations.
 */
import coreTranslations from 'src/modules/Core/i18n/en-US';

/**
 * Application-specific translations that extend the core translations
 */
const appTranslations = {
  // Add your custom translations here
  app: {
    name: 'QuVel Kit',
    version: 'v1.0.0',
  },
};

/**
 * Export combined translations
 */
export default {
  ...coreTranslations,
  ...appTranslations,
} as const;
