# PWA Limitations with Multi-Tenancy

## ⚠️ Important Notice

**PWA mode is not compatible with multi-tenant setups in QuVel Kit.**

## Why PWA Doesn't Work with Multi-Tenancy

In a multi-tenant setup, QuVel Kit requires server-side tenant resolution to:

1. **Determine the correct API endpoint** - Each tenant may have different internal/external API URLs
2. **Load tenant-specific configuration** - Database settings, feature flags, API keys, etc.
3. **Handle authentication properly** - Session cookies, CSRF tokens, and tenant-specific auth flows

PWA mode runs entirely client-side after the initial load, which means:

- ❌ No access to server-side tenant resolution
- ❌ Cannot determine correct API URLs dynamically  
- ❌ Missing tenant-specific configuration
- ❌ Authentication issues with tenant-specific sessions

## Alternative

One alternative is to set up a frontend application per-tenant, so that the fallback
config values (e.g. VITE_API_URL) coming from the environment are correct.  

## Configuration

PWA is automatically disabled in SSR mode when `VITE_MULTI_TENANT=true` is set in your environment variables.

```bash
# .env
VITE_MULTI_TENANT=true  # Disables PWA in SSR mode
```

## Technical Details

The issue occurs because:

1. **Hard refresh**: Works because SSR resolves tenant config server-side
2. **Soft refresh**: Fails because PWA tries to call incorrect API endpoints

Example of the problem:

```txt
✅ SSR calls: https://api.tenant.domain.com/auth/session
❌ PWA calls: https://<VITE_API_URL ?? ''>/auth/session (wrong!)
```
