/** Config type for SPA */
export interface TenantConfig {
  apiUrl: string;
  appUrl: string;
  appName: string;
  tenantId: string;
  tenantName: string;
  pusherAppKey: string;
  pusherAppCluster: string;
  socialiteProviders: string[];
  sessionName: string;
  recaptchaGoogleSiteKey: string;
}
