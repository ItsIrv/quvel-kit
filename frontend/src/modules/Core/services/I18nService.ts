import { RegisterService, SsrAwareService, SsrServiceOptions } from './../types/service.types';
import type { I18nType } from 'src/modules/Core/types/i18n.types';
import { isValidLocale, storeLocale, createI18n } from 'src/modules/Core/utils/i18nUtil';
import { Service } from './Service';
import { ServiceContainer } from './ServiceContainer';
import { ApiService } from './ApiService';

/**
 * I18n Service for managing localization.
 */
export class I18nService extends Service implements SsrAwareService, RegisterService {
  private i18n!: I18nType;
  private api!: ApiService;

  /**
   * Boot method to initialize with SSR context if available.
   */
  boot(ssrServiceOptions?: SsrServiceOptions): void {
    // Create i18n instance with proper SSR context
    this.i18n = createI18n(ssrServiceOptions);
  }

  /**
   * Registers the service.
   */
  register(container: ServiceContainer): void {
    this.api = container.api;

    // If i18n not initialized yet (no SSR context), boot now
    if (!this.i18n) {
      this.boot();
    }

    this.setApiLocaleHeader();
  }

  /**
   * Retrieves the i18n instance.
   */
  get instance(): I18nType {
    return this.i18n;
  }

  /**
   * Sets the locale header for API requests.
   */
  setApiLocaleHeader(): void {
    this.api.instance.defaults.headers.common['Accept-Language'] = this.i18n.global.locale.value;
  }

  /**
   * Changes the locale and stores it.
   */
  changeLocale(locale: string): void {
    if (isValidLocale(locale)) {
      this.i18n.global.locale.value = locale;

      storeLocale(locale);

      this.setApiLocaleHeader();
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
