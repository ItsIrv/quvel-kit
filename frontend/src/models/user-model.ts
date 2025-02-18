/**
 * Interface defining the structure of a User.
 */
export interface IUser {
  id: number
  name: string
  email: string
  emailVerifiedAt: string
  createdAt: string
  updatedAt: string
}

/**
 * Class representing a User entity.
 */
export class User implements IUser {
  id: number
  name: string
  email: string
  emailVerifiedAt: string
  createdAt: string
  updatedAt: string

  /**
   * Constructs a new User instance.
   * @param data - Partial user data to initialize the object.
   */
  constructor(data: Partial<IUser> = {}) {
    this.id = data.id ?? 0
    this.name = data.name ?? ''
    this.email = data.email ?? ''
    this.emailVerifiedAt = data.emailVerifiedAt ?? ''
    this.createdAt = data.createdAt ?? ''
    this.updatedAt = data.updatedAt ?? ''
  }

  /**
   * Creates a User instance from an API response.
   * @param data - User data from the API.
   * @returns A new User instance.
   */
  static fromApi(data: IUser): User {
    return new User({
      id: data.id,
      name: data.name,
      email: data.email,
      emailVerifiedAt: data.emailVerifiedAt,
      createdAt: data.createdAt,
      updatedAt: data.updatedAt,
    })
  }
}
