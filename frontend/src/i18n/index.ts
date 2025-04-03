import enUSCore from '../modules/Core/i18n/en-US';
import esMXCore from '../modules/Core/i18n/es-MX';

export default {
  'en-US': { ...enUSCore } as const,
  'es-MX': { ...esMXCore } as const,
} as const;
