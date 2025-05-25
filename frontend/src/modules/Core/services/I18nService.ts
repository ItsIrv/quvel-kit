import { RegisterService } from './../types/service.types';
import type { I18nType } from 'src/modules/Core/types/i18n.types';
import { isValidLocale, storeLocale } from 'src/modules/Core/utils/i18nUtil';
import { Service } from './Service';
import { ServiceContainer } from './ServiceContainer';
import { ApiService } from './ApiService';

/**
 * I18n Service for managing localization.
 */
export class I18nService extends Service implements RegisterService {
  private readonly i18n: I18nType;
  private api!: ApiService;

  constructor(i18nInstance: I18nType) {
    super();

    this.i18n = i18nInstance;
  }

  /**
   * Registers the service.
   */
  register({ api }: ServiceContainer): void {
    this.api = api;

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
