<?php

namespace Modules\Tenant\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Modules\Tenant\Enums\TenantConfigVisibility;

/**
 * Represents the configuration for a tenant.
 * Implements Arrayable for conversion between object and array representation.
 *
 * @implements Arrayable<string, mixed>
 */
class TenantConfig implements Arrayable
{
    /** The base API endpoint (e.g., https://api.quvel.app) */
    public readonly string $apiUrl;

    /** The main application (UI) URL, typically the frontend app (e.g., https://quvel.app) */
    public readonly string $appUrl;

    /** The name of the app used for branding */
    public readonly string $appName;

    /** The Laravel environment (e.g., local, staging, production) */
    public readonly string $appEnv;

    /** Internal-only API URL for capacitor-to-laravel SSR calls */
    public readonly ?string $internalApiUrl;

    /** Whether debug mode is enabled */
    public readonly bool $debug;

    /** Name used in 'from' header for tenant emails */
    public readonly string $mailFromName;

    /** Email address used in 'from' header for tenant emails */
    public readonly string $mailFromAddress;

    /**
     * @var string|null Capacitor scheme
     *                  - <value> custom scheme <value>://<appUrl>
     *                  - null - use appUrl
     */
    public readonly ?string $capacitorScheme;

    /**
     * Visibility settings per field
     *
     * @var array<string, TenantConfigVisibility>
     */
    public readonly array $visibility;

    /**
     * @param  array<string, TenantConfigVisibility>  $visibility
     */
    public function __construct(
        string $apiUrl,
        string $appUrl,
        string $appName,
        string $appEnv,
        ?string $internalApiUrl = null,
        bool $debug = false,
        string $mailFromName = '',
        string $mailFromAddress = '',
        array $visibility = [],
        ?string $capacitorScheme = null,
    ) {
        $this->apiUrl = $apiUrl;
        $this->appUrl = $appUrl;
        $this->appName = $appName;
        $this->appEnv = $appEnv;
        $this->internalApiUrl = $internalApiUrl;
        $this->debug = $debug;
        $this->mailFromName = $mailFromName;
        $this->mailFromAddress = $mailFromAddress;
        $this->visibility = $visibility;
        $this->capacitorScheme = $capacitorScheme;
    }

    /**
     * Create an instance from an array.
     *
     * @param  array<string, mixed>  $data  The configuration data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            apiUrl: $data['api_url'] ?? '',
            appUrl: $data['app_url'] ?? '',
            appName: $data['app_name'] ?? '',
            appEnv: $data['app_env'] ?? '',
            internalApiUrl: $data['internal_api_url'] ?? null,
            debug: $data['debug'] ?? false,
            mailFromName: $data['mail_from_name'] ?? '',
            mailFromAddress: $data['mail_from_address'] ?? '',
            visibility: array_map(
                static fn ($value) => TenantConfigVisibility::tryFrom($value) ?? TenantConfigVisibility::PRIVATE,
                $data['__visibility'] ?? []
            ),
            capacitorScheme: $data['capacitor_scheme'] ?? null,
        );
    }

    /**
     * Convert the object to an array for JSON storage.
     */
    public function toArray(): array
    {
        return [
            'api_url' => $this->apiUrl,
            'app_url' => $this->appUrl,
            'app_name' => $this->appName,
            'internal_api_url' => $this->internalApiUrl,
            'app_env' => $this->appEnv,
            'debug' => $this->debug,
            'mail_from_name' => $this->mailFromName,
            'mail_from_address' => $this->mailFromAddress,
            '__visibility' => array_map(
                static fn (TenantConfigVisibility $v): string => $v->value,
                $this->visibility,
            ),
            'capacitor_scheme' => $this->capacitorScheme,
        ];
    }
}
