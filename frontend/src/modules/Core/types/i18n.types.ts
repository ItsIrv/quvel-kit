import type { I18n } from 'vue-i18n';

export type MessageLanguages = 'en-US' | 'es-MX';
export type MessageSchema = Record<string, unknown>;

/**
 * Shared type for Vue I18n configuration
 */
export type I18nType = I18n<
  { messages: MessageSchema },
  Record<string, unknown>,
  Record<string, unknown>,
  string,
  false
>;
