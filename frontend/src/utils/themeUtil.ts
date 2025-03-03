import { Dark, Cookies } from 'quasar';

export const THEME_COOKIE_NAME = 'user-theme';

/**
 * Define the theme options.
 */
export const themeOptions = ['light', 'dark'] as const; // Readonly tuple

/**
 * Type definition for the theme options.
 */
export type ThemeOptions = (typeof themeOptions)[number];

/**
 * Loads the theme from header cookies or the system preference.
 */
export function loadTheme(): void {
  // TODO: Load theme can only be called after the app has finished booting on the browser.
  if (typeof window === 'undefined') return;

  const userTheme = Cookies.get(THEME_COOKIE_NAME);

  if (userTheme && themeOptions.includes(userTheme as ThemeOptions)) {
    setTheme(userTheme as ThemeOptions);
  } else {
    setTheme(window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  }
}

/**
 * Sets the theme in local storage, Quasar Dark mode, and Tailwind classes.
 */
export function setTheme(theme: ThemeOptions): void {
  if (typeof window === 'undefined') return;

  Cookies.set(THEME_COOKIE_NAME, theme);
  Dark.set(theme === 'dark');

  // Ensure Tailwind dark mode class is applied properly
  const root = document.documentElement;
  if (theme === 'dark') {
    root.classList.add('dark');
  } else {
    root.classList.remove('dark');
  }
}

/**
 * Toggles the theme between light and dark modes.
 */
export function toggleTheme(): void {
  setTheme(Dark.isActive ? 'light' : 'dark');
}
