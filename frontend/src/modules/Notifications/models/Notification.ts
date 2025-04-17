import { INotification } from 'src/modules/Notifications/types/notification.types';

/**
 * Class representing a notification.
 */
export class Notification implements INotification {
  id = '';
  message = '';
  read_at: string | null = null;
  created_at = '';
  data: Record<string, unknown> | null = null;

  constructor(data: INotification) {
    Object.assign(this, data);
  }

  /**
   *  Creates a Notification instance from API data.
   */
  static fromApi(data: Partial<INotification>): Notification {
    return new Notification({
      id: data.id ?? '',
      message: data.message ?? (data.data?.message as string) ?? '',
      read_at: data.read_at ?? null,
      created_at: data.created_at ?? new Date().toISOString(),
      data: data.data ?? null,
    });
  }

  /**
   * Checks if the provided data is a valid notification object.
   */
  static isModel(data: unknown): data is INotification {
    return typeof data === 'object' && data !== null && 'message' in data && 'id' in data;
  }
}
