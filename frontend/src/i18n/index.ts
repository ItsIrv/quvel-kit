/**
 * Main i18n translations index
 * 
 * This file imports all language translations and exports them in the format expected by Vue I18n.
 * To add a new language, create a new language file and import it here.
 */
import enUS from './en-US';
import esMX from './es-MX';

/**
 * Export all translations
 */
export default {
  'en-US': enUS,
  'es-MX': esMX,
} as const;
