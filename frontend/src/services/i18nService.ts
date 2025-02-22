import type { I18nType } from 'src/types/i18n.types';
import { applyLocale } from 'src/utils/i18nUtil';

/**
 * I18n Service for managing localization.
 */
export class I18nService {
  private readonly i18n: I18nType;

  constructor(i18nInstance: I18nType) {
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
    applyLocale(this.i18n, locale);
  }

  /**
   * Translates a message using the current locale.
   */
  t(key: string, params: Record<string, unknown> = {}): string {
    return this.i18n.global.t(key, params);
  }
}
