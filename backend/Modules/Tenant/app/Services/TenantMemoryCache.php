<?php

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Laravel\Octane\Octane;
use Modules\Tenant\Models\Tenant;

/**
 * High-performance in-memory cache for tenant resolution using Octane Tables.
 * Falls back to static arrays for non-Octane environments.
 */
class TenantMemoryCache
{
    /**
     * Fallback cache for non-Octane environments
     */
    private static array $fallbackCache = [];

    public function __construct(
        private readonly ConfigRepository $config,
    ) {
    }

    /**
     * Get a tenant from memory cache
     */
    public function getTenant(string $domain): ?Tenant
    {
        if ($this->isOctaneEnvironment()) {
            $table = app(Octane::class)->table('tenants');
            $data  = $table->get($domain);

            if ($data && $this->isValidCacheEntry($data)) {
                return unserialize($data['tenant']);
            }

            return null;
        }

        // Fallback for non-Octane
        if (isset(self::$fallbackCache[$domain])) {
            $entry = self::$fallbackCache[$domain];
            if ($this->isValidCacheEntry($entry)) {
                return $entry['tenant'];
            }
            unset(self::$fallbackCache[$domain]);
        }

        return null;
    }

    /**
     * Cache a tenant in memory
     */
    public function cacheTenant(string $domain, Tenant $tenant): void
    {
        $ttl     = $this->config->get('tenant.tenant_cache.resolver_ttl', 300);
        $maxSize = $this->config->get('tenant.memory_cache.max_size', 1000);
        $entry   = [
            'tenant'     => $tenant,
            'expires_at' => time() + $ttl,
        ];

        if ($this->isOctaneEnvironment()) {
            $table = app(Octane::class)->table('tenants');

            // Check size limit
            if (count($table) >= $maxSize) {
                $this->evictOldestOctaneEntry($table);
            }

            $table->set($domain, [
                'tenant'     => serialize($tenant),
                'expires_at' => $entry['expires_at'],
            ]);
        } else {
            // Fallback for non-Octane
            if (count(self::$fallbackCache) >= $maxSize) {
                $this->evictOldestFallbackEntry();
            }

            self::$fallbackCache[$domain] = $entry;
        }
    }

    /**
     * Invalidate a specific tenant from memory cache
     */
    public function invalidateTenant(string $domain): void
    {
        if ($this->isOctaneEnvironment()) {
            app(Octane::class)->table('tenants')->del($domain);
        } else {
            unset(self::$fallbackCache[$domain]);
        }
    }

    /**
     * Clear all tenants from memory cache
     */
    public function clearAll(): void
    {
        if ($this->isOctaneEnvironment()) {
            // Clear all entries from Swoole table
            $table = app(Octane::class)->table('tenants');
            // Swoole tables don't have a built-in clear method
            // We need to iterate through and delete each key
            foreach ($table as $key => $value) {
                $table->del($key);
            }
        } else {
            self::$fallbackCache = [];
        }
    }

    /**
     * Check if we're running in an Octane environment
     */
    public function isOctaneEnvironment(): bool
    {
        return class_exists(Octane::class)
            && app()->bound(Octane::class)
            && app()->bound(\Swoole\Http\Server::class);
    }

    /**
     * Check if a cache entry is still valid
     */
    private function isValidCacheEntry(array $entry): bool
    {
        return isset($entry['expires_at']) && time() < $entry['expires_at'];
    }

    /**
     * Evict the oldest entry from Octane table
     */
    private function evictOldestOctaneEntry($table): void
    {
        $oldest    = null;
        $oldestKey = null;

        foreach ($table as $key => $entry) {
            if ($oldest === null || $entry['expires_at'] < $oldest) {
                $oldest    = $entry['expires_at'];
                $oldestKey = $key;
            }
        }

        if ($oldestKey !== null) {
            $table->del($oldestKey);
        }
    }

    /**
     * Evict the oldest entry from fallback cache
     */
    private function evictOldestFallbackEntry(): void
    {
        if (empty(self::$fallbackCache)) {
            return;
        }

        $firstKey = array_key_first(self::$fallbackCache);
        unset(self::$fallbackCache[$firstKey]);
    }
}
