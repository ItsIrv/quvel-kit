import type { I18nType } from 'src/types/i18n.types';
import { isValidLocale, storeLocale } from 'src/utils/i18nUtil';
import { Service } from './Service';

/**
 * I18n Service for managing localization.
 */
export class I18nService extends Service {
  private readonly i18n: I18nType;

  constructor(i18nInstance: I18nType) {
    super();

    this.i18n = i18nInstance;
  }

  /**
   * Retrieves the i18n instance.
   */
  get instance(): I18nType {
    return this.i18n;
  }

  /**
   * Changes the locale and stores it.
   */
  changeLocale(locale: string): void {
    if (isValidLocale(locale)) {
      this.i18n.global.locale.value = locale;

      storeLocale(locale);
    }
  }

  /**
   * Translates a message using the current locale.
   */
  t(key: string, params: Record<string, unknown> = {}): string {
    return this.i18n.global.t(key, params);
  }

  /**
   * Checks if a translation exists.
   */
  te(key: string): boolean {
    return this.i18n.global.te(key);
  }
}
