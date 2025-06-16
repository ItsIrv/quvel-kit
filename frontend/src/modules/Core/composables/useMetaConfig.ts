import { useMeta } from 'quasar';
import type { TenantMeta } from '../types/tenant.types';
import { useContainer } from './useContainer';

/**
 * Configure meta tags for a page, allowing full customization.
 * Uses tenant meta configuration when available, falls back to defaults when SSR is off.
 * @param {string} pageTitle - The page title (first param for simplicity)
 * @param {Partial<unknown>} overrides - Optional overrides to modify metadata
 */
export function useMetaConfig(pageTitle?: string, overrides?: Partial<unknown>): void {
  // Get tenant meta configuration from the service container
  const { config } = useContainer();
  const tenantMeta: TenantMeta = config.get('meta') || {};

  // Use tenant configuration or fallback to defaults
  const title = pageTitle ?? tenantMeta?.title ?? 'A Modern Hybrid App Framework';
  
  // Create title template function from tenant config or use default
  const titleTemplate = tenantMeta?.titleTemplate 
    ? (title: string) => tenantMeta.titleTemplate!.replace('%s', title)
    : (title: string) => `${title} - QuVel Kit`;
  const description =
    tenantMeta?.description ??
    'QuVel Kit is a Laravel & Quasar hybrid framework optimized for SSR and seamless development.';
  const keywords = tenantMeta?.keywords ?? 'Quasar, Laravel, SSR, Hybrid, Framework';
  const ogTitle = tenantMeta?.ogTitle ?? pageTitle ?? 'QuVel Kit';
  const ogDescription =
    tenantMeta?.ogDescription ??
    'A powerful Laravel & Quasar SSR framework for building modern apps.';
  const twitterTitle = tenantMeta?.twitterTitle ?? pageTitle ?? 'QuVel Kit';
  const twitterDescription =
    tenantMeta?.twitterDescription ?? 'A Laravel & Quasar hybrid framework optimized for SSR.';

  useMeta({
    title,
    titleTemplate,

    meta: {
      description: {
        name: 'description',
        content: description,
      },
      keywords: { name: 'keywords', content: keywords },
      ogTitle: { property: 'og:title', content: ogTitle },
      ogDescription: {
        property: 'og:description',
        content: ogDescription,
      },
      ...(tenantMeta?.ogImage && {
        ogImage: { property: 'og:image', content: tenantMeta.ogImage },
      }),
      ogType: { property: 'og:type', content: 'website' },
      twitterCard: { name: 'twitter:card', content: 'summary_large_image' },
      twitterTitle: { name: 'twitter:title', content: twitterTitle },
      twitterDescription: {
        name: 'twitter:description',
        content: twitterDescription,
      },
      ...(tenantMeta?.twitterImage && {
        twitterImage: { name: 'twitter:image', content: tenantMeta.twitterImage },
      }),
    },

    script: {
      structuredData: {
        type: 'application/ld+json',
        innerHTML: JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'WebSite',
          name: tenantMeta?.title ?? 'QuVel Kit',
          url: config.get('appUrl') ?? 'https://quvelkit.com',
          description,
        }),
      },
    },

    htmlAttr: {
      lang: 'en',
    },

    ...(overrides || {}),
  });
}
