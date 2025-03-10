<?php

namespace Modules\Tenant\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\TenantFindService;
use Modules\Tenant\ValueObjects\TenantConfig;

class ManageTenantConfig extends Command
{
    /**
     * Command signature.
     */
    protected $signature = 'tenant:config
        {domain : The domain of the tenant}
        {--list : List current tenant configuration}
        {--update= : Update a specific config key=value}
        {--add-visibility= : Add visibility rule (key=public|protected)}
        {--remove-visibility= : Remove visibility rule (key)}';

    /**
     * Command description.
     */
    protected $description = 'Manage tenant configurations dynamically';

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        assert(is_string($this->argument('domain')));
        $domain = $this->argument('domain');

        /** @var TenantFindService $tenantFindService */
        $tenantFindService = app(TenantFindService::class);

        /** @var Tenant|null $tenant */
        $tenant = $tenantFindService->findTenantByDomain($domain);

        if (!$tenant) {
            $this->error("Tenant with domain '$domain' not found.");
            return Command::FAILURE;
        }

        /** @var TenantConfig|null $effectiveConfig */
        $effectiveConfig = $tenant->getEffectiveConfig();

        if (!$effectiveConfig) {
            $this->error("No configuration found for tenant '$domain'.");
            return Command::FAILURE;
        }

        /** @var array<string, mixed> $currentConfig */
        $currentConfig = $effectiveConfig->toArray();

        assert(is_string(json_encode($currentConfig, JSON_PRETTY_PRINT)));
        if ($this->option('list')) {
            $this->info("Current Configuration for '$domain':");
            $this->line(json_encode($currentConfig, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        assert(is_string($this->option('update')));
        if ($updateOption = $this->option('update')) {
            return $this->handleUpdate($tenant, $currentConfig, $updateOption);
        }

        assert(is_string($this->option('add-visibility')));
        if ($addVisibility = $this->option('add-visibility')) {
            return $this->handleAddVisibility($tenant, $currentConfig, $addVisibility);
        }

        assert(is_string($this->option('remove-visibility')));
        if ($removeVisibility = $this->option('remove-visibility')) {
            return $this->handleRemoveVisibility($tenant, $currentConfig, $removeVisibility);
        }

        $this->warn("No valid option provided. Use `--list`, `--update`, `--add-visibility`, or `--remove-visibility`.");
        return Command::INVALID;
    }

    /**
     * Handles updating a config key.
     *
     * @param Tenant $tenant
     * @param array<string, mixed> $currentConfig
     * @param string $updateOption
     * @return int
     */
    private function handleUpdate(Tenant $tenant, array $currentConfig, string $updateOption): int
    {
        [$key, $value] = explode('=', $updateOption, 2);
        $snakeKey      = Str::snake($key);

        if (!array_key_exists($snakeKey, $currentConfig)) {
            $this->error("Invalid config key: '$key' (expected: '$snakeKey')");
            return Command::FAILURE;
        }

        $newConfig            = $currentConfig;
        $newConfig[$snakeKey] = $this->castValue($snakeKey, $value);

        $tenant->update(['config' => $newConfig]);

        $this->info("Updated '$snakeKey' to '$value' for tenant '{$tenant->domain}'.");
        return Command::SUCCESS;
    }

    /**
     * Handles adding a visibility rule.
     *
     * @param Tenant $tenant
     * @param array<string, mixed> $currentConfig
     * @param string $visibilityOption
     * @return int
     */
    private function handleAddVisibility(Tenant $tenant, array $currentConfig, string $visibilityOption): int
    {
        [$key, $visibilityLevel] = explode('=', $visibilityOption, 2);
        $snakeKey                = Str::snake($key);

        if (!array_key_exists($snakeKey, $currentConfig)) {
            $this->error("Invalid config key: '$key' (expected: '$snakeKey')");
            return Command::FAILURE;
        }

        if (!in_array($visibilityLevel, ['public', 'protected'], true)) {
            $this->error("Invalid visibility level: '$visibilityLevel'. Allowed: 'public', 'protected'.");
            return Command::FAILURE;
        }

        if (!isset($currentConfig['__visibility']) || !is_array($currentConfig['__visibility'])) {
            $currentConfig['__visibility'] = [];
        }

        $currentConfig['__visibility'][$snakeKey] = $visibilityLevel;

        $tenant->update(['config' => $currentConfig]);

        $this->info("Added visibility rule: '$snakeKey' => '$visibilityLevel' for tenant '{$tenant->domain}'.");
        return Command::SUCCESS;
    }

    /**
     * Handles removing a visibility rule.
     *
     * @param Tenant $tenant
     * @param array<string, mixed> $currentConfig
     * @param string $key
     * @return int
     */
    private function handleRemoveVisibility(Tenant $tenant, array $currentConfig, string $key): int
    {
        $snakeKey = Str::snake($key);

        if (!isset($currentConfig['__visibility'][$snakeKey])) {
            $this->error("Visibility rule not found for '$key' (expected: '$snakeKey')");
            return Command::FAILURE;
        }

        unset($currentConfig['__visibility'][$snakeKey]);

        $tenant->update(['config' => $currentConfig]);

        $this->info("Removed visibility rule for '$snakeKey' from tenant '{$tenant->domain}'.");
        return Command::SUCCESS;
    }

    /**
     * Casts a value to the correct type based on the key.
     *
     * @param string $key
     * @param string $value
     * @return mixed
     */
    private function castValue(string $key, string $value): mixed
    {
        $booleanFields  = ['debug'];
        $nullableFields = ['internal_api_url'];

        if (in_array($key, $booleanFields, true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (in_array($key, $nullableFields, true) && empty($value)) {
            return null;
        }

        return $value;
    }
}
