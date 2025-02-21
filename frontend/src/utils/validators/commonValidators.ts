import { z } from 'zod';

export const emailSchema = z.string().email({ message: 'Invalid email format' });

export const passwordSchema = z
  .string()
  .min(8, { message: 'Password must be at least 8 characters' })
  .max(100, { message: 'Password is too long' });

export const usernameSchema = z.string().min(3, { message: 'Username too short' });
