import { useMeta } from 'quasar';

/**
 * Configure meta tags for a page, allowing full customization.
 * @param {string} pageTitle - The page title (first param for simplicity)
 * @param {Partial<MetaOptions>} overrides - Optional overrides to modify meta data
 */
export function useMetaConfig(pageTitle?: string, overrides?: Partial<unknown>): void {
  useMeta({
    title: pageTitle ?? 'A Modern Hybrid App Framework',
    titleTemplate: (title) => `${title} - QuVel Kit`,

    meta: {
      description: {
        name: 'description',
        content:
          'QuVel Kit is a Laravel & Quasar hybrid framework optimized for SSR and seamless development.',
      },
      keywords: { name: 'keywords', content: 'Quasar, Laravel, SSR, Hybrid, Framework' },
      ogTitle: { property: 'og:title', content: pageTitle ?? 'QuVel Kit' },
      ogDescription: {
        property: 'og:description',
        content: 'A powerful Laravel & Quasar SSR framework for building modern apps.',
      },
      ogImage: { property: 'og:image', content: 'https://quvelkit.com/meta-image.jpg' },
      ogType: { property: 'og:type', content: 'website' },
      twitterCard: { name: 'twitter:card', content: 'summary_large_image' },
      twitterTitle: { name: 'twitter:title', content: pageTitle ?? 'QuVel Kit' },
      twitterDescription: {
        name: 'twitter:description',
        content: 'A Laravel & Quasar hybrid framework optimized for SSR.',
      },
      twitterImage: { name: 'twitter:image', content: 'https://quvelkit.com/meta-image.jpg' },
    },

    script: {
      structuredData: {
        type: 'application/ld+json',
        innerHTML: JSON.stringify({
          '@context': 'http://schema.org',
          '@type': 'WebSite',
          name: 'QuVel Kit',
          url: 'https://quvelkit.com',
          description:
            'A Laravel & Quasar hybrid framework optimized for SSR and seamless development.',
        }),
      },
    },

    htmlAttr: {
      lang: 'en',
    },

    ...(overrides || {}),
  });
}
