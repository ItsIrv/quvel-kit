import type { IUser } from 'src/types/user.types';

/**
 * Class representing a User entity.
 */
export class User implements IUser {
  id: number;
  name: string;
  email: string;
  avatar: string;
  emailVerifiedAt: string;
  createdAt: string;
  updatedAt: string;

  /**
   * Constructs a new User instance.
   * @param data - Partial user data to initialize the object.
   */
  constructor(data: Partial<IUser> = {}) {
    this.id = data.id ?? 0;
    this.name = data.name ?? '';
    this.email = data.email ?? '';
    this.avatar = data.avatar ?? '';
    this.emailVerifiedAt = data.emailVerifiedAt ?? '';
    this.createdAt = data.createdAt ?? '';
    this.updatedAt = data.updatedAt ?? '';
  }
}
