import { Notify, QNotifyCreateOptions } from 'quasar';

/**
 * Standardized notification utility.
 */
export function showNotification(
  type: 'negative' | 'warning' | 'positive' | 'info',
  message: string,
  options: QNotifyCreateOptions = {},
): void {
  if (typeof window === 'undefined') {
    return;
  }

  const iconMap = {
    negative: 'eva-alert-circle-outline',
    warning: 'eva-alert-triangle-outline',
    positive: 'eva-checkmark-circle-outline',
    info: 'eva-info-outline',
  };

  Notify.create({
    ...options,
    icon: iconMap[type] || 'eva-info-outline',
    type,
    message,
  });
}
