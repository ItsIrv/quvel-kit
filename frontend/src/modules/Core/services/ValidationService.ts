import { ZodSchema, ZodIssueCode, ZodIssue } from 'zod';
import type { ServiceContainer } from './ServiceContainer';
import type { RegisterService } from 'src/modules/Core/types/service.types';
import type { I18nService } from 'src/modules/Core/services/I18nService';
import { Service } from './Service';

/**
 * Validation service with translation and a scoped Zod instance.
 */
export class ValidationService extends Service implements RegisterService {
  private i18n!: I18nService;

  /**
   * Injects the container dependencies.
   */
  public register(container: ServiceContainer): void {
    this.i18n = container.i18n;
  }

  /**
   * Validates a value and stops at the first error (for Quasar `rules`).
   *
   * @param value - The value to validate.
   * @param schema - The Zod schema.
   * @param attribute - The field name (e.g., "Email").
   * @returns The first translated error or `true` if valid.
   */
  public validateFirstError<T>(
    value: unknown,
    schema: ZodSchema<T>,
    attribute: string,
  ): string | true {
    const result = schema.safeParse(value);
    if (result.success) return true;

    const firstIssue = result.error.issues[0];
    if (firstIssue) {
      return this.translateError(firstIssue, attribute);
    }

    return this.i18n.t('validation.generic', { attribute });
  }

  /**
   * Validates a value and returns **all** translated errors.
   *
   * @param value - The value to validate.
   * @param schema - The Zod schema.
   * @param attribute - The field name (e.g., "Email").
   * @returns An array of translated errors or `[]` if valid.
   */
  public validateAllErrors<T>(value: unknown, schema: ZodSchema<T>, attribute: string): string[] {
    const result = schema.safeParse(value);
    if (result.success) return [];

    return result.error.issues.map((issue) => this.translateError(issue, attribute));
  }

  /**
   * Creates a Quasar-compatible validation rule that stops at the first error.
   */
  public createInputRule<T>(
    schema: ZodSchema<T>,
    attribute: string,
  ): (value: unknown) => string | true {
    return (value: unknown) => this.validateFirstError(value, schema, attribute);
  }

  /**
   * Translates a Zod error message into an i18n-friendly format.
   * @param issue - The Zod validation issue.
   * @param attribute - The attribute name (required).
   */
  public translateError(issue: ZodIssue, attribute: string): string {
    const i18n = this.i18n;

    switch (issue.code) {
      case ZodIssueCode.invalid_type:
        return i18n.t('validation.invalid_type', {
          expectedType: issue.expected,
          receivedType: issue.received,
          attribute,
        });

      case ZodIssueCode.too_small:
        return i18n.t(issue.type === 'string' ? 'validation.minLength' : 'validation.min', {
          min: issue.minimum,
          attribute,
        });

      case ZodIssueCode.too_big:
        return i18n.t(issue.type === 'string' ? 'validation.maxLength' : 'validation.max', {
          max: issue.maximum,
          attribute,
        });

      case ZodIssueCode.invalid_string:
        if (typeof issue.validation === 'string') {
          return i18n.t(`validation.${issue.validation}`, { attribute });
        }
        return i18n.t('validation.invalid_string', { attribute });

      case ZodIssueCode.invalid_enum_value:
        return i18n.t('validation.invalid_enum_value', {
          attribute,
          received: issue.received,
          options: issue.options.join(', '),
        });

      case ZodIssueCode.unrecognized_keys:
        return i18n.t('validation.unrecognized_keys', {
          attribute,
          keys: issue.keys.join(', '),
        });

      case ZodIssueCode.invalid_union:
      case ZodIssueCode.invalid_union_discriminator:
        return i18n.t('validation.invalid_union', { attribute });

      case ZodIssueCode.invalid_date:
        return i18n.t('validation.invalid_date', { attribute });

      case ZodIssueCode.not_multiple_of:
        return i18n.t('validation.not_multiple_of', {
          attribute,
          multipleOf: issue.multipleOf,
        });

      case ZodIssueCode.not_finite:
        return i18n.t('validation.not_finite', { attribute });

      case ZodIssueCode.custom:
        return i18n.t(issue.params?.translationKey || 'validation.default', { attribute });

      default:
        return issue.message || i18n.t('validation.default', { attribute });
    }
  }
}
