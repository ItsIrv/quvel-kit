/**
 * Utility functions for working with i18n.
 * Recommended to use the  `container.i18n` service instead.
 */
import { Cookies } from 'quasar';
import type { SsrServiceOptions } from 'src/modules/Core/types/service.types';
import type { I18nType, MessageLanguages } from 'src/modules/Core/types/i18n.types';
import { createI18n as createI18nInstance } from 'vue-i18n';
import { getModuleI18n } from 'src/modules/moduleRegistry';

const LOCALE_COOKIE_KEY = 'user-locale';
const DEFAULT_LOCALE: MessageLanguages = 'en-US';

/**
 * List of supported locales.
 */
const SUPPORTED_LOCALES: MessageLanguages[] = ['en-US', 'es-MX'];

/**
 * Retrieves the stored locale from cookies (server) or localStorage (client).
 * Falls back to the default locale if no valid locale is found.
 */
export function getStoredLocale(ssrContext?: SsrServiceOptions | null): MessageLanguages {
  let locale: string;

  if (ssrContext) {
    const cookies = Cookies.parseSSR(ssrContext);
    locale = cookies.get(LOCALE_COOKIE_KEY) || '';
  } else {
    locale = Cookies.get(LOCALE_COOKIE_KEY) || '';
  }

  return isValidLocale(locale) ? locale : DEFAULT_LOCALE;
}

/**
 * Stores the selected locale persistently.
 * - Uses cookies (client)
 * - Uses localStorage as fallback
 */
export function storeLocale(locale: string): void {
  if (!isValidLocale(locale)) return;

  document.cookie = `${LOCALE_COOKIE_KEY}=${encodeURIComponent(locale)}; path=/; max-age=31536000;`;

  if (typeof window !== 'undefined') {
    Cookies.set(LOCALE_COOKIE_KEY, locale, { expires: 365 });
  }
}

/**
 * Validates if a locale is supported using a runtime check.
 */
export function isValidLocale(locale: string): locale is MessageLanguages {
  return SUPPORTED_LOCALES.includes(locale as MessageLanguages);
}

/**
 * Creates an instance of the I18nType.
 * @param ssrContext - The server-side rendering context.
 * @returns An instance of the I18n service.
 */
export function createI18n(ssrContext?: SsrServiceOptions | null): I18nType {
  const locale = getStoredLocale(ssrContext);

  // Build messages from all module translations
  const messages = {
    'en-US': getModuleI18n('en-US'),
    'es-MX': getModuleI18n('es-MX'),
  };

  return createI18nInstance({
    locale: locale as string,
    legacy: false,
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    messages: messages as any,
  });
}
