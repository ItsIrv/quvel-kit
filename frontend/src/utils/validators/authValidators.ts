import { z } from 'zod';
import { emailSchema, passwordSchema } from './commonValidators';

/**
 * Login Validation Schema
 */
export const loginSchema = z.object({
  email: emailSchema,
  password: passwordSchema,
});

/**
 * Register Validation Schema
 */
export const registerSchema = z
  .object({
    email: emailSchema,
    password: passwordSchema,
    confirmPassword: z.string().min(8, 'Password must be at least 8 characters').max(100),
  })
  .refine((data) => data.password === data.confirmPassword, {
    message: 'Passwords must match',
    path: ['confirmPassword'],
  });

/**
 * Reset Password Validation Schema
 */
export const resetPasswordSchema = z.object({
  email: emailSchema,
});
