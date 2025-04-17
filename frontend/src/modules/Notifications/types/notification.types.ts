/**
 * Interface for a notification.
 */
export interface INotification {
  id: string;
  message: string;
  read_at: string | null;
  data: Record<string, unknown> | null;
  created_at: string;
}
