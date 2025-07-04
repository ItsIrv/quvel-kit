import type { IUser } from 'src/modules/Core/types/user.types';

/**
 * Class representing a User entity.
 */
export class User implements IUser {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  emailVerifiedAt: string;
  two_factor_enabled?: boolean;
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
    this.two_factor_enabled = data.two_factor_enabled ?? false;
    this.createdAt = data.createdAt ?? '';
    this.updatedAt = data.updatedAt ?? '';
  }

  get avatarUrl(): string {
    return this.avatar ?? '';
  }

  static fromApi(data: IUser): User {
    return new User({
      id: data.id,
      name: data.name,
      email: data.email,
      avatar: data.avatar,
      emailVerifiedAt: data.emailVerifiedAt,
      two_factor_enabled: data.two_factor_enabled ?? false,
      createdAt: data.createdAt,
      updatedAt: data.updatedAt,
    });
  }
}
