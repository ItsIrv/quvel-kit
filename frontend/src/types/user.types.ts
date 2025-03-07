/**
 * Interface defining the structure of a User.
 */
export interface IUser {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  emailVerifiedAt: string;
  createdAt: string;
  updatedAt: string;
}
