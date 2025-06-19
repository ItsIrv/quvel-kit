# Dynamic Tenant Assets System

The Dynamic Assets System enables tenants to inject custom CSS and JavaScript into their applications through configuration stored in the tenant's dynamic config. This system provides granular control over asset loading, positioning, and execution strategies.

## Overview

The system operates through Laravel's tenant configuration pipeline, where assets are defined in tenant config with PUBLIC visibility and automatically served to the frontend through the API config endpoint.

### Core Components

- **Tenant Configuration Storage** - Assets stored in dynamic tenant config
- **Configuration Pipes** - Exposes assets through CoreConfigPipe  
- **Asset Serving** - Static files served from frontend/public directory
- **Security Validation** - Frontend validates URLs and sanitizes content

## Configuration Structure

Assets are stored under the `assets` key in tenant dynamic configuration:

```php
'assets' => [
    'css' => [...],  // Array of CSS asset configurations
    'js' => [...]    // Array of JavaScript asset configurations
]
```

### CSS Asset Properties

| Property | Type | Description | Example |
|----------|------|-------------|---------|
| `url` | string | External or relative URL to CSS file | `/tenant-custom.css` |
| `inline` | string | Inline CSS content | `.btn { color: red; }` |
| `media` | string | CSS media query | `screen`, `print` |
| `integrity` | string | SRI integrity hash for external URLs | `sha384-...` |
| `crossorigin` | string | CORS policy for external resources | `anonymous` |
| `position` | string | DOM injection position | `head`, `body-start`, `body-end` |
| `priority` | string | Loading priority level | `critical`, `normal`, `low` |

### JavaScript Asset Properties

| Property | Type | Description | Example |
|----------|------|-------------|---------|
| `url` | string | External or relative URL to JS file | `/tenant-custom.js` |
| `inline` | string | Inline JavaScript content | `console.log('hello');` |
| `defer` | boolean | Legacy defer attribute | `true`, `false` |
| `async` | boolean | Legacy async attribute | `true`, `false` |
| `integrity` | string | SRI integrity hash | `sha384-...` |
| `crossorigin` | string | CORS policy | `anonymous` |
| `position` | string | DOM injection position | `head`, `body-start`, `body-end` |
| `priority` | string | Loading priority level | `critical`, `normal`, `low` |
| `loading` | string | Modern loading strategy | `immediate`, `deferred`, `lazy` |

## Loading Strategies

### Priority Levels

- **Critical** - Essential assets that must load first
- **Normal** - Standard assets loaded in default order  
- **Low** - Optional assets loaded last

### JavaScript Loading Options

- **Immediate** - Loads and executes synchronously, blocking page rendering
- **Deferred** - Uses defer attribute, executes after DOM is ready
- **Lazy** - Uses async with requestIdleCallback for non-critical functionality

### Position Control

- **Head** - Injected before `</head>` closing tag
- **Body Start** - Injected immediately after `<body>` opening tag
- **Body End** - Injected before `</body>` closing tag

## Backend Implementation

### Configuration Pipeline

The CoreConfigPipe in the Tenant module exposes assets to the frontend:

**File**: `backend/Modules/Tenant/app/Pipes/CoreConfigPipe.php`

The pipe's resolve method includes:

- `assets` key containing tenant asset configuration
- `meta` key for tenant-specific meta tags
- PUBLIC visibility ensuring assets are available to frontend

### Database Storage

Assets are stored in the tenants table under the dynamic_config JSON column with the following structure:

```json
{
  "configs": [
    {
      "key": "assets",
      "value": {
        "css": [...],
        "js": [...]
      },
      "visibility": "PUBLIC"
    }
  ]
}
```

### Seeder Configuration

**File**: `backend/Modules/Tenant/app/Seeders/CoreConfig/CoreApplicationBasicSeeder.php`

The seeder demonstrates asset configuration without validation, allowing flexible tenant customization. Example configurations include:

- CSS file with normal priority in head position
- JavaScript file with lazy loading in body-end position

## Asset Serving

### Static File Location

Tenant assets are typically stored in:

- **Path**: `frontend/public/`
- **URL**: `https://domain.com/asset-name.ext`
- **Access**: Direct HTTP access, cached by browser

### External Assets

The system supports external assets with:

- HTTPS requirement in production environments
- SRI integrity validation for security
- CORS configuration for cross-origin resources

## Security Features

### URL Validation

- Relative URLs (starting with `/`) are always allowed
- External URLs must use HTTPS in production
- Protocol validation prevents javascript: and data: URLs

### Content Sanitization

For inline content, the frontend removes:

- Script tags from CSS content
- Event handlers (onclick, onload, etc.)
- JavaScript protocol references
- Potentially malicious content

### Integrity Checking

- Support for Subresource Integrity (SRI) hashes
- Automatic validation of external resource integrity
- CORS policy enforcement for external assets

## Configuration Examples

### Basic CSS Override

```php
[
    'key' => 'assets',
    'value' => [
        'css' => [
            [
                'url' => '/tenant-brand.css',
                'position' => 'head',
                'priority' => 'normal'
            ]
        ]
    ],
    'visibility' => TenantConfigVisibility::PUBLIC
]
```

### Advanced JavaScript Loading

```php
[
    'key' => 'assets', 
    'value' => [
        'js' => [
            [
                'url' => '/critical-init.js',
                'position' => 'head',
                'priority' => 'critical',
                'loading' => 'immediate'
            ],
            [
                'url' => '/analytics.js',
                'position' => 'body-end', 
                'priority' => 'low',
                'loading' => 'lazy'
            ]
        ]
    ],
    'visibility' => TenantConfigVisibility::PUBLIC
]
```

### Inline Styles

```php
[
    'key' => 'assets',
    'value' => [
        'css' => [
            [
                'inline' => '.tenant-btn { background: linear-gradient(45deg, #ff6b6b, #4ecdc4); }',
                'position' => 'head',
                'priority' => 'normal'
            ]
        ]
    ],
    'visibility' => TenantConfigVisibility::PUBLIC
]
```

## Integration Points

### Frontend API Endpoint

The tenant configuration API endpoint (`/api/config`) automatically includes the assets configuration when:

- Tenant is properly resolved by TenantMiddleware
- Assets have PUBLIC visibility in tenant config
- CoreConfigPipe processes the configuration

### Cache Strategy

- Tenant configurations are cached in memory during request
- Asset files benefit from standard HTTP caching headers
- Configuration changes require cache invalidation

### Multi-Platform Support

The system works across all QuVel Kit deployment modes:

- **SSR** - Assets injected during server-side rendering
- **SPA** - Assets loaded via boot files after app initialization  
- **PWA** - Assets cached for offline availability
- **Capacitor** - Assets served from bundled public directory

## Performance Considerations

### Loading Order

Assets load in this sequence:

1. Critical priority assets (immediate loading)
2. Normal priority assets (standard loading)
3. Low priority assets (deferred/lazy loading)

### Resource Optimization

- CSS can use critical loading for above-fold styles
- JavaScript uses requestIdleCallback for non-essential features
- External assets leverage browser caching and CDN distribution

### Bundle Impact

The system adds minimal overhead:

- Configuration data is already part of tenant API response
- Asset utilities are lazy-loaded only when needed
- No additional HTTP requests for configuration

## Troubleshooting

### Common Issues

**Assets Not Loading**

- Verify PUBLIC visibility in tenant configuration
- Check CoreConfigPipe includes assets in resolve method
- Confirm asset files exist at specified URLs

**Security Errors**

- Ensure HTTPS for external assets in production
- Validate SRI hashes match actual file content
- Check CORS headers for cross-origin assets

**Performance Problems**

- Review loading strategies (use lazy for non-critical assets)
- Minimize inline content in configuration
- Leverage browser caching for static assets

### Debugging

**Configuration Validation**

- Check tenant dynamic_config JSON structure
- Verify asset visibility is set to PUBLIC
- Review CoreConfigPipe output in API response

**Asset Loading**

- Monitor browser network tab for failed requests
- Check console for validation errors
- Verify asset positioning in DOM inspector
