import { Dark, LocalStorage } from 'quasar';
import { onMounted } from 'vue';

/**
 * Loads the theme from local storage or the system preference.
 */
export function useTheme(): void {
  onMounted(() => {
    const userTheme = LocalStorage.getItem('theme');

    if (userTheme) {
      Dark.set(userTheme === 'dark');
    } else {
      Dark.set(window.matchMedia('(prefers-color-scheme: dark)').matches);
    }
  });
}
