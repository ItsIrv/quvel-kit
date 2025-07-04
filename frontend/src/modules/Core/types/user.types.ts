/**
 * Interface defining the structure of a User.
 */
export interface IUser {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  emailVerifiedAt: string;
  two_factor_enabled?: boolean;
  createdAt: string;
  updatedAt: string;
}
