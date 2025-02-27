import { z } from 'zod';

/**
 * Email validation schema.
 */
export const emailSchema = (): z.ZodString => z.string().email();

/**
 * Password validation schema.
 */
export const passwordSchema = (): z.ZodString => z.string().min(6).max(100);
