import type messages from 'src/i18n';
import type { I18n } from 'vue-i18n';

export type MessageLanguages = keyof typeof messages;
export type MessageSchema = (typeof messages)[MessageLanguages];

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
