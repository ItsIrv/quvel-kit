<?php

namespace Modules\Tenant\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

class TenantConfig implements Arrayable
{
    public function __construct(
        public readonly string $apiUrl = '',
        public readonly string $appUrl = '',
        public readonly string $appName = '',
        public readonly string $appEnv = 'production',
        public readonly ?string $internalApiUrl = null,
        public readonly bool $debug = false,
    ) {
    }

    /**
     * Create an instance from an array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            apiUrl: $data['api_url'] ?? '',
            appUrl: $data['app_url'] ?? '',
            appName: $data['app_name'] ?? '',
            appEnv: $data['app_env'] ?? 'production',
            internalApiUrl: $data['internal_api_url'] ?? null,
            debug: $data['debug'] ?? false,
        );
    }

    /**
     * Convert the object to an array for JSON storage.
     */
    public function toArray(): array
    {
        return [
            'api_url'          => $this->apiUrl,
            'app_url'          => $this->appUrl,
            'app_name'         => $this->appName,
            'app_env'          => $this->appEnv,
            'internal_api_url' => $this->internalApiUrl,
            'debug'            => $this->debug,
        ];
    }
}
