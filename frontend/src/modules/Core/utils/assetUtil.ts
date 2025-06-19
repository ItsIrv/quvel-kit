import type { CSSAssetConfig, JSAssetConfig, TenantAssets } from '../types/tenant.types';

/**
 * Validates if a URL is safe to load as an asset.
 * Prevents loading from potentially malicious sources.
 */
export function isValidAssetUrl(url: string): boolean {
  try {
    // Allow relative URLs
    if (url.startsWith('/')) {
      return true;
    }

    const parsedUrl = new URL(url);
    
    // Only allow HTTPS in production
    if (import.meta.env.PROD && parsedUrl.protocol !== 'https:') {
      return false;
    }

    // Allow HTTP/HTTPS
    if (!['http:', 'https:'].includes(parsedUrl.protocol)) {
      return false;
    }

    return true;
  } catch {
    return false;
  }
}

/**
 * Sanitizes inline content to prevent XSS attacks.
 * This is a basic implementation - consider using a library like DOMPurify for production.
 */
export function sanitizeInlineContent(content: string): string {
  // Remove script tags and event handlers
  return content
    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
    .replace(/on\w+\s*=\s*"[^"]*"/gi, '')
    .replace(/on\w+\s*=\s*'[^']*'/gi, '')
    .replace(/javascript:/gi, '');
}

/**
 * Creates a CSS link element from configuration.
 */
export function createCSSElement(config: CSSAssetConfig): HTMLLinkElement | HTMLStyleElement | null {
  if (config.url) {
    if (!isValidAssetUrl(config.url)) {
      console.warn('Invalid CSS URL:', config.url);
      return null;
    }

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = config.url;
    
    if (config.media) {
      link.media = config.media;
    }
    
    if (config.integrity) {
      link.integrity = config.integrity;
    }
    
    if (config.crossorigin) {
      link.crossOrigin = config.crossorigin;
    }

    // Handle loading priority for CSS
    if (config.priority === 'critical') {
      link.rel = 'preload';
      link.as = 'style';
      link.onload = () => { link.rel = 'stylesheet'; };
    } else if (config.priority === 'low') {
      link.media = 'print';
      link.onload = () => { link.media = config.media || 'all'; };
    }

    return link;
  } else if (config.inline) {
    const style = document.createElement('style');
    style.textContent = sanitizeInlineContent(config.inline);
    
    // Add priority hint as data attribute for debugging
    if (config.priority) {
      style.setAttribute('data-priority', config.priority);
    }
    
    return style;
  }

  return null;
}

/**
 * Creates a JavaScript script element from configuration.
 */
export function createJSElement(config: JSAssetConfig): HTMLScriptElement | null {
  const script = document.createElement('script');

  if (config.url) {
    if (!isValidAssetUrl(config.url)) {
      console.warn('Invalid JS URL:', config.url);
      return null;
    }

    script.src = config.url;
    
    if (config.integrity) {
      script.integrity = config.integrity;
    }
    
    if (config.crossorigin) {
      script.crossOrigin = config.crossorigin;
    }
  } else if (config.inline) {
    script.textContent = sanitizeInlineContent(config.inline);
  } else {
    return null;
  }

  // Handle loading strategies
  if (config.loading === 'immediate') {
    // No defer/async - loads and executes immediately
  } else if (config.loading === 'deferred') {
    script.defer = true;
  } else if (config.loading === 'lazy') {
    script.async = true;
  } else {
    // Fallback to explicit defer/async if loading not specified
    if (config.defer) {
      script.defer = true;
    }
    
    if (config.async) {
      script.async = true;
    }
  }

  // Add priority and position hints as data attributes for debugging
  if (config.priority) {
    script.setAttribute('data-priority', config.priority);
  }
  if (config.position) {
    script.setAttribute('data-position', config.position);
  }

  return script;
}


/**
 * Injects tenant assets into the document.
 * Used by client-side asset loading (non-SSR modes).
 */
export function injectTenantAssets(assets: TenantAssets): void {
  if (!assets) return;

  // Sort and inject CSS assets by priority
  if (assets.css?.length) {
    const sortedCSS = [...assets.css].sort((a, b) => {
      const priorityOrder = { critical: 0, normal: 1, low: 2 };
      const aPriority = priorityOrder[a.priority || 'normal'];
      const bPriority = priorityOrder[b.priority || 'normal'];
      return aPriority - bPriority;
    });

    sortedCSS.forEach(cssConfig => {
      const element = createCSSElement(cssConfig);
      if (element) {
        const position = cssConfig.position || 'head';
        injectElementAtPosition(element, position);
      }
    });
  }

  // Sort and inject JavaScript assets by priority and loading strategy
  if (assets.js?.length) {
    const sortedJS = [...assets.js].sort((a, b) => {
      const priorityOrder = { critical: 0, normal: 1, low: 2 };
      const loadingOrder = { immediate: 0, deferred: 1, lazy: 2 };
      
      const aPriority = priorityOrder[a.priority || 'normal'];
      const bPriority = priorityOrder[b.priority || 'normal'];
      
      if (aPriority !== bPriority) return aPriority - bPriority;
      
      const aLoading = loadingOrder[a.loading || 'deferred'];
      const bLoading = loadingOrder[b.loading || 'deferred'];
      return aLoading - bLoading;
    });

    // Inject immediate scripts first
    const immediateScripts = sortedJS.filter(js => js.loading === 'immediate');
    const deferredScripts = sortedJS.filter(js => js.loading !== 'immediate');

    // Process immediate scripts first
    immediateScripts.forEach(jsConfig => {
      const element = createJSElement(jsConfig);
      if (element) {
        const position = jsConfig.position || 'head';
        injectElementAtPosition(element, position);
      }
    });

    // Process deferred/lazy scripts
    if (deferredScripts.length > 0) {
      // Use requestIdleCallback or setTimeout for non-critical scripts
      const processDeferred = () => {
        deferredScripts.forEach(jsConfig => {
          const element = createJSElement(jsConfig);
          if (element) {
            const position = jsConfig.position || 'body-end';
            injectElementAtPosition(element, position);
          }
        });
      };

      if ('requestIdleCallback' in window) {
        requestIdleCallback(processDeferred);
      } else {
        setTimeout(processDeferred, 0);
      }
    }
  }
}

/**
 * Injects an element at the specified position in the document.
 */
function injectElementAtPosition(element: HTMLElement, position: 'head' | 'body-start' | 'body-end'): void {
  switch (position) {
    case 'head':
      document.head.appendChild(element);
      break;
    case 'body-start':
      if (document.body.firstChild) {
        document.body.insertBefore(element, document.body.firstChild);
      } else {
        document.body.appendChild(element);
      }
      break;
    case 'body-end':
    default:
      document.body.appendChild(element);
      break;
  }
}

/**
 * Generates HTML strings for server-side asset injection.
 * Used by SSR asset injection service.
 */
export function generateAssetHTML(assets: TenantAssets): {
  headHTML: string;
  bodyStartHTML: string;
  bodyEndHTML: string;
} {
  let headHTML = '';
  let bodyStartHTML = '';
  let bodyEndHTML = '';

  if (!assets) {
    return { headHTML, bodyStartHTML, bodyEndHTML };
  }

  // Sort CSS by priority
  const sortedCSS = assets.css ? [...assets.css].sort((a, b) => {
    const priorityOrder = { critical: 0, normal: 1, low: 2 };
    const aPriority = priorityOrder[a.priority || 'normal'];
    const bPriority = priorityOrder[b.priority || 'normal'];
    return aPriority - bPriority;
  }) : [];

  // Sort JS by priority and loading strategy
  const sortedJS = assets.js ? [...assets.js].sort((a, b) => {
    const priorityOrder = { critical: 0, normal: 1, low: 2 };
    const loadingOrder = { immediate: 0, deferred: 1, lazy: 2 };
    
    const aPriority = priorityOrder[a.priority || 'normal'];
    const bPriority = priorityOrder[b.priority || 'normal'];
    
    if (aPriority !== bPriority) return aPriority - bPriority;
    
    const aLoading = loadingOrder[a.loading || 'deferred'];
    const bLoading = loadingOrder[b.loading || 'deferred'];
    return aLoading - bLoading;
  }) : [];

  // Generate CSS HTML
  sortedCSS.forEach(config => {
    let cssHTML = '';
    
    if (config.url && isValidAssetUrl(config.url)) {
      const attrs = [];
      if (config.media) attrs.push(`media="${config.media}"`);
      if (config.integrity) attrs.push(`integrity="${config.integrity}"`);
      if (config.crossorigin) attrs.push(`crossorigin="${config.crossorigin}"`);
      if (config.priority) attrs.push(`data-priority="${config.priority}"`);
      
      // Handle CSS loading strategies
      if (config.priority === 'critical') {
        cssHTML = `<link rel="preload" href="${config.url}" as="style" onload="this.rel='stylesheet'" ${attrs.join(' ')}>`;
      } else if (config.priority === 'low') {
        cssHTML = `<link rel="stylesheet" href="${config.url}" media="print" onload="this.media='${config.media || 'all'}'" ${attrs.join(' ')}>`;
      } else {
        cssHTML = `<link rel="stylesheet" href="${config.url}" ${attrs.join(' ')}>`;
      }
    } else if (config.inline) {
      const priorityAttr = config.priority ? ` data-priority="${config.priority}"` : '';
      cssHTML = `<style${priorityAttr}>${sanitizeInlineContent(config.inline)}</style>`;
    }

    // Place CSS in appropriate position
    const position = config.position || 'head';
    if (position === 'head') {
      headHTML += cssHTML;
    } else if (position === 'body-start') {
      bodyStartHTML += cssHTML;
    } else {
      bodyEndHTML += cssHTML;
    }
  });

  // Generate JS HTML
  sortedJS.forEach(config => {
    let jsHTML = '';
    
    if (config.url && isValidAssetUrl(config.url)) {
      const attrs = [];
      
      // Handle loading strategies
      if (config.loading === 'immediate') {
        // No defer/async - loads and executes immediately
      } else if (config.loading === 'deferred') {
        attrs.push('defer');
      } else if (config.loading === 'lazy') {
        attrs.push('async');
      } else {
        // Fallback to explicit defer/async
        if (config.defer) attrs.push('defer');
        if (config.async) attrs.push('async');
      }
      
      if (config.integrity) attrs.push(`integrity="${config.integrity}"`);
      if (config.crossorigin) attrs.push(`crossorigin="${config.crossorigin}"`);
      if (config.priority) attrs.push(`data-priority="${config.priority}"`);
      if (config.position) attrs.push(`data-position="${config.position}"`);
      
      jsHTML = `<script src="${config.url}" ${attrs.join(' ')}></script>`;
    } else if (config.inline) {
      const priorityAttr = config.priority ? ` data-priority="${config.priority}"` : '';
      const positionAttr = config.position ? ` data-position="${config.position}"` : '';
      jsHTML = `<script${priorityAttr}${positionAttr}>${sanitizeInlineContent(config.inline)}</script>`;
    }

    // Place JS in appropriate position
    const position = config.position || (config.loading === 'immediate' ? 'head' : 'body-end');
    if (position === 'head') {
      headHTML += jsHTML;
    } else if (position === 'body-start') {
      bodyStartHTML += jsHTML;
    } else {
      bodyEndHTML += jsHTML;
    }
  });

  return { headHTML, bodyStartHTML, bodyEndHTML };
}