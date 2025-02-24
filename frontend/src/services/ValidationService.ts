import type { ServiceContainer } from './ServiceContainer';
import type { ZodSchema } from 'zod';
import { validateOrError } from 'src/utils/validationUtil';
import type { BootableService } from 'src/types/service.types';
import type { I18nService } from './I18nService';
import { Service } from './Service';

/**
 * Validation service with translation support.
 */
export class ValidationService extends Service implements BootableService {
  private i18n: I18nService | null = null;

  /**
   * Injects the container dependencies.
   */
  register(container: ServiceContainer): void {
    this.i18n = container.i18n;
  }

  /**
   * Validates a value with optional translation.
   *
   * TODO: This only supports basic validators like email, min, max, type.
   * Zod requires setErrorMap which will be implemented at a later time.
   * A default message will return for now. You can also pass `translate`: false.
   * @param value - The value to validate.
   * @param schema - The Zod schema.
   * @param field - The field name for translation (default: 'value').
   * @param translate - Whether to translate the error message (default: true).
   */
  validate<T>(
    value: unknown,
    schema: ZodSchema<T>,
    field: string = 'value',
    translate: boolean = true,
  ): string | true {
    if (!this.i18n) throw new Error('ValidationService is missing i18n instance.');

    const result = validateOrError(value, schema);
    if (result === true) return true;

    return translate ? this.translateError(result, field) : result;
  }

  /**
   * Translates a Zod error message to an i18n-friendly format.
   * @param issue - The error message from Zod.
   * @param field - The field name for translation.
   */
  private translateError(issue: string, field: string): string {
    if (!this.i18n) return issue;

    // Extract structured parts (e.g., { min: 8 })
    const parsed = this.parseZodMessage(issue);
    const translationKey = `validation.${parsed.key}`;

    return this.i18n.t(translationKey, { ...parsed.params, field });
  }

  /**
   * Parses a Zod error message into a structured translation format.
   * @param message - The full error message from Zod.
   */
  private parseZodMessage(message: string): { key: string; params: Record<string, unknown> } {
    if (message.includes('at least')) {
      const match = message.match(/at least (\d+) character/);
      return match
        ? { key: 'minLength', params: { min: parseInt(match[1] ?? '0', 10) } }
        : { key: 'minLength', params: {} };
    }

    if (message.includes('at most')) {
      const match = message.match(/at most (\d+) character/);
      return match
        ? { key: 'maxLength', params: { max: parseInt(match[1] ?? '0', 10) } }
        : { key: 'maxLength', params: {} };
    }

    if (message.toLowerCase().includes('invalid email')) {
      return { key: 'email', params: {} };
    }

    if (message.toLowerCase().includes('required')) {
      return { key: 'required', params: {} };
    }

    if (message.includes('Expected') && message.includes('received')) {
      const match = message.match(/Expected (\w+), received (\w+)/);
      return match
        ? { key: 'invalid_type', params: { expectedType: match[1], receivedType: match[2] } }
        : { key: 'invalid_type', params: {} };
    }

    return { key: 'default', params: {} };
  }

  /**
   * Creates a Quasar-compatible validation rule using Vue i18n.
   */
  createTranslatedValidationRule<T>(
    schema: ZodSchema<T>,
    field: string = 'Field',
  ): (value: unknown) => string | true {
    return (value: unknown) => this.validate(value, schema, field);
  }
}
