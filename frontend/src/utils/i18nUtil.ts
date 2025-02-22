import { Cookies } from 'quasar';
import type { QSsrContext } from '@quasar/app-vite';
import type { I18nType, MessageLanguages } from 'src/types/i18n.types';
import { createI18n as createI18nInstance } from 'vue-i18n';
import messages from 'src/i18n';
import { I18nService } from 'src/services/I18nService';

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
export function getStoredLocale(ssrContext?: QSsrContext | null): MessageLanguages {
  let locale = '';

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
export function storeLocale(locale: MessageLanguages): void {
  if (!isValidLocale(locale)) return;

  document.cookie = `${LOCALE_COOKIE_KEY}=${encodeURIComponent(locale)}; path=/; max-age=31536000;`;

  if (typeof window !== 'undefined') {
    locale = Cookies.get(LOCALE_COOKIE_KEY) || '';
  }
}

/**
 * Applies the locale to Vue I18n.
 */
export function applyLocale(i18n: I18nType, locale: string): void {
  if (!isValidLocale(locale)) return;

  i18n.global.locale.value = locale;

  storeLocale(locale);
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
export function createI18n(ssrContext?: QSsrContext | null): I18nType {
  const locale = getStoredLocale(ssrContext);

  return createI18nInstance({
    locale,
    legacy: false,
    messages,
  });
}

/**
 * Creates an instance of the I18nService.
 * @param ssrContext - The server-side rendering context.
 * @returns An instance of the I18nService.
 */
export function createI18nService(ssrContext?: QSsrContext | null): I18nService {
  return new I18nService(createI18n(ssrContext));
}
