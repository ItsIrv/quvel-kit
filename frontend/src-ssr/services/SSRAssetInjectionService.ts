import type { TenantAssets } from 'src/modules/Core/types/tenant.types';
import { generateAssetHTML } from 'src/modules/Core/utils/assetUtil';
import type { SSRScopedService } from '../types/service.types';
import { SSRService } from './SSRService';

/**
 * SSR Asset Injection Service
 *
 * Handles dynamic asset injection for multi-tenant SSR applications.
 * Processes tenant-specific CSS and JavaScript during server-side rendering.
 *
 * This is a scoped service - a new instance is created for each request.
 */
export class SSRAssetInjectionService extends SSRService implements SSRScopedService {
  private tenantAssets: TenantAssets | null = null;

  override boot(): void {}

  /**
   * Sets tenant assets for injection
   */
  setTenantAssets(assets: TenantAssets | undefined): void {
    this.tenantAssets = assets || null;
  }

  /**
   * Generates HTML for all tenant assets
   */
  generateAssetHTML(): { headHTML: string; bodyStartHTML: string; bodyEndHTML: string } {
    if (!this.tenantAssets) {
      return { headHTML: '', bodyStartHTML: '', bodyEndHTML: '' };
    }

    return generateAssetHTML(this.tenantAssets);
  }

  /**
   * Processes HTML to inject all tenant assets at appropriate positions
   */
  processHTML(html: string): string {
    if (!this.tenantAssets) {
      return html;
    }

    const { headHTML, bodyStartHTML, bodyEndHTML } = this.generateAssetHTML();
    let processedHTML = html;

    // Inject into head section
    if (headHTML) {
      const headEndIndex = processedHTML.indexOf('</head>');
      if (headEndIndex !== -1) {
        processedHTML =
          processedHTML.slice(0, headEndIndex) + headHTML + processedHTML.slice(headEndIndex);
      }
    }

    // Inject at body start
    if (bodyStartHTML) {
      const bodyStartIndex = processedHTML.indexOf('<body');
      if (bodyStartIndex !== -1) {
        // Find the end of the opening body tag
        const bodyTagEndIndex = processedHTML.indexOf('>', bodyStartIndex) + 1;
        processedHTML =
          processedHTML.slice(0, bodyTagEndIndex) +
          bodyStartHTML +
          processedHTML.slice(bodyTagEndIndex);
      }
    }

    // Inject at body end
    if (bodyEndHTML) {
      const bodyEndIndex = processedHTML.indexOf('</body>');
      if (bodyEndIndex !== -1) {
        processedHTML =
          processedHTML.slice(0, bodyEndIndex) + bodyEndHTML + processedHTML.slice(bodyEndIndex);
      }
    }

    return processedHTML;
  }
}
