/**
 * Validation Utils to be used when translation is not important.
 * If translation is needed use the `container.validation` service.
 */
import { SafeParseReturnType, ZodSchema } from 'zod';

/**
 * Validates a value against a Zod schema.
 * @param value - The value to validate.
 * @param schema - The Zod schema.
 * @returns A SafeParseReturnType object with the parsed value and validation errors.
 */
export function safeParse<T>(value: unknown, schema: ZodSchema<T>): SafeParseReturnType<T, T> {
  return schema.safeParse(value);
}

/**
 * Validates a value against a Zod schema.
 * @param value - The value to validate.
 * @param schema - The Zod schema.
 * @returns A string with the error message, or `true` if validation passes.
 */
export function validateOrError<T>(value: unknown, schema: ZodSchema<T>): string | true {
  const result = safeParse(value, schema);

  return result.success ? true : (result.error.issues[0]?.message ?? 'Invalid input');
}

/**
 * Creates a validation rule compatible with Quasar's `rules` prop.
 * @param schema - The Zod schema.
 * @returns A validation function for Quasar inputs.
 */
export function createValidationRule<T>(schema: ZodSchema<T>): (value: unknown) => string | true {
  return (value: unknown) => validateOrError(value, schema);
}
