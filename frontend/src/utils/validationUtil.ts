import { type ZodSchema } from 'zod';

// TODO: Validation needs to be  hooked up to i18n.
/**
 * Validates a value against a Zod schema.
 * @param value - The value to validate.
 * @param schema - The Zod schema.
 * @returns A string with the error message, or `true` if validation passes.
 */
export function validateOrError<T>(value: unknown, schema: ZodSchema<T>): string | true {
  const result = schema.safeParse(value);

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
