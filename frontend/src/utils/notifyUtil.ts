import { Notify } from 'quasar';

/**
 * Standardized notification utility.
 */
export function showNotification(type: 'negative' | 'warning' | 'positive', message: string): void {
  if (typeof window === 'undefined') {
    return;
  }

  const iconMap = {
    negative: 'eva-alert-circle-outline',
    warning: 'eva-alert-triangle-outline',
    positive: 'eva-checkmark-circle-outline',
  };

  Notify.create({
    icon: iconMap[type] || 'eva-info-outline',
    type,
    message,
  });
}
