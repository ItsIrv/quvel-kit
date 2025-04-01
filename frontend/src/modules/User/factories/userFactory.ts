import { User } from 'src/modules/User/models/User';
import type { IUser } from 'src/modules/Core/types/user.types';

/**
 * Factory function to create a User instance from API response.
 */
export function createUserFromApi(data: IUser): User {
  return new User({
    id: data.id,
    name: data.name,
    email: data.email,
    avatar: data.avatar,
    emailVerifiedAt: data.emailVerifiedAt,
    createdAt: data.createdAt,
    updatedAt: data.updatedAt,
  });
}
