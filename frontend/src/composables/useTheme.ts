import { Dark, LocalStorage } from 'quasar';
import { onMounted } from 'vue';

/**
 * Loads the theme from local storage or the system preference.
 */
export function useTheme(): void {
  // TODO: Detect and pass in headers for SSR to serve the page with the correct theme loaded.
  onMounted(() => {
    const userTheme = LocalStorage.getItem('theme');

    if (userTheme) {
      Dark.set(userTheme === 'dark');
    } else {
      Dark.set(window.matchMedia('(prefers-color-scheme: dark)').matches);
    }
  });
}

/**
 * Sets the theme in local storage and the Quasar Dark mode.
 * @param theme - The theme to set. Either 'light' or 'dark'.
 */
export function setTheme(theme: 'light' | 'dark'): void {
  LocalStorage.set('theme', theme);
  Dark.set(theme === 'dark');
}

/**
 * Toggles the theme between light and dark modes.
 */
export function toggleTheme(): void {
  setTheme(Dark.isActive ? 'light' : 'dark');
}
