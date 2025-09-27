<?php

declare(strict_types=1);

namespace Quvel\Tenant;

use Illuminate\Config\Repository as ConfigRepository;
use InvalidArgumentException;
use Quvel\Tenant\Contracts\TenantConfigHandler;
use Quvel\Tenant\Models\Tenant;

/**
 * Manages tenant configuration handlers.
 *
 * Coordinates multiple config handlers for seeding, endpoint data aggregation,
 * and per-request configuration application.
 */
class TenantConfigManager
{
    /**
     * @var array<string> Registered handler class names
     */
    protected array $handlers = [];

    /**
     * Register a single tenant config handler.
     *
     * @param string $handlerClass Handler class name that extends TenantConfigHandler
     * @throws InvalidArgumentException If handler doesn't extend TenantConfigHandler
     */
    public function registerHandler(string $handlerClass): void
    {
        if (!is_subclass_of($handlerClass, TenantConfigHandler::class)) {
            throw new InvalidArgumentException("Handler must extend TenantConfigHandler");
        }

        $this->handlers[] = $handlerClass;
    }

    /**
     * Register multiple tenant config handlers.
     *
     * @param array<string> $handlerClasses Array of handler class names
     */
    public function registerHandlers(array $handlerClasses): void
    {
        foreach ($handlerClasses as $handlerClass) {
            $this->registerHandler($handlerClass);
        }
    }

    /**
     * Get aggregated seed data from all registered handlers.
     *
     * @return array Combined seed data from all handlers
     */
    public function getAllSeedData(): array
    {
        $allSeedData = [];

        foreach ($this->handlers as $handlerClass) {
            $handler = new $handlerClass();
            $allSeedData[] = $handler->getSeedData();
        }

        return array_merge([], ...$allSeedData);
    }

    /**
     * Get aggregated endpoint data from all registered handlers.
     *
     * @return array Combined endpoint data from all handlers
     */
    public function getAllEndpointData(): array
    {
        $allEndpointData = [];

        foreach ($this->handlers as $handlerClass) {
            $handler = new $handlerClass();
            $allEndpointData[] = $handler->getEndpointData();
        }

        return array_merge([], ...$allEndpointData);
    }

    /**
     * Apply all registered handlers to the current request.
     *
     * @param Tenant $tenant The current tenant
     * @param ConfigRepository $config Laravel config repository
     * @param array $tenantConfig The tenant's configuration array
     */
    public function applyAllToRequest(Tenant $tenant, ConfigRepository $config, array $tenantConfig): void
    {
        foreach ($this->handlers as $handlerClass) {
            $handler = new $handlerClass();
            $handler->applyToRequest($tenant, $config, $tenantConfig);
        }
    }

    /**
     * Get all registered handler class names.
     *
     * @return array<string> Array of registered handler class names
     */
    public function getRegisteredHandlers(): array
    {
        return $this->handlers;
    }
}