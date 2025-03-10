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
    public function __construct(
        public readonly string $apiUrl,
        public readonly string $appUrl,
        public readonly string $appName,
        public readonly string $appEnv,
        public readonly ?string $internalApiUrl = null,
        public readonly bool $debug = false,
        public readonly string $mailFromName = '',
        public readonly string $mailFromAddress = '',
        /** @var array<string, \Modules\Tenant\Enums\TenantConfigVisibility> */
        public readonly array $visibility = [],
    ) {
    }

    /**
     * Create an instance from an array.
     * @param array<string, mixed> $data The configuration data.
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
                function ($value): TenantConfigVisibility {
                    return TenantConfigVisibility::tryFrom($value) ?? TenantConfigVisibility::PRIVATE;
                },
                $data['__visibility'] ?? []
            ),
        );
    }

    /**
     * Convert the object to an array for JSON storage.
     */
    public function toArray(): array
    {
        return [
            'api_url'           => $this->apiUrl,
            'app_url'           => $this->appUrl,
            'app_name'          => $this->appName,
            'app_env'           => $this->appEnv,
            'internal_api_url'  => $this->internalApiUrl,
            'debug'             => $this->debug,
            'mail_from_name'    => $this->mailFromName,
            'mail_from_address' => $this->mailFromAddress,
            '__visibility'      => array_map(
                fn (TenantConfigVisibility $v): string => $v->value,
                $this->visibility,
            ),
        ];
    }
}
