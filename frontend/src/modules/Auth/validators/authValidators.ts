import { z } from 'zod';
import { emailSchema, passwordSchema } from '../../Core/utils/validators/commonValidators';

/**
 * Login Validation Schema.
 */
export const loginSchema = (): z.ZodObject<{ email: z.ZodString; password: z.ZodString }> =>
  z.object({
    email: emailSchema(),
    password: passwordSchema(),
  });

/**
 * Register Validation Schema.
 */
export const registerSchema = (): z.ZodEffects<
  z.ZodObject<{
    email: z.ZodString;
    password: z.ZodString;
    confirmPassword: z.ZodString;
  }>
> =>
  z
    .object({
      email: emailSchema(),
      password: passwordSchema(),
      confirmPassword: z.string().min(8).max(100),
    })
    .refine((data) => data.password === data.confirmPassword, {
      message: 'Passwords do not match',
      path: ['confirmPassword'],
    });
/**
 * Reset Password Validation Schema.
 */
export const resetPasswordSchema = (): z.ZodObject<{ email: z.ZodString }> =>
  z.object({
    email: emailSchema(),
  });

export const resetPasswordTokenSchema = () =>
  z.object({
    form: z.literal('password-reset'),
    token: z
      .string()
      .length(64)
      .regex(/^[a-zA-Z0-9]+$/),
  });
