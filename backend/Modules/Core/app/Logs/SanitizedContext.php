<?php

namespace Modules\Core\Logs;

use ArrayAccess;
use JsonSerializable;

/**
 * Context wrapper that applies sanitization rules to sensitive data before logging.
 * Provides explicit, declarative control over PII sanitization for compliance.
 */
class SanitizedContext implements ArrayAccess, JsonSerializable
{
    /**
     * Sanitization strategies
     */
    public const HASH = 'hash';
    public const MASK = 'mask';
    public const DOMAIN_ONLY = 'domain_only';
    public const TRUNCATE = 'truncate';
    public const REMOVE = 'remove';

    /**
     * Create a new sanitized context.
     *
     * @param array<string, mixed> $data The raw data to log
     * @param array<string, string> $sanitizeRules Rules for sanitizing specific keys
     */
    public function __construct(
        private array $data,
        private array $sanitizeRules = []
    ) {
    }

    /**
     * Get the sanitized data array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $sanitized = [];

        foreach ($this->data as $key => $value) {
            if (isset($this->sanitizeRules[$key])) {
                $sanitized[$key] = $this->applySanitization($value, $this->sanitizeRules[$key]);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Apply sanitization strategy to a value.
     */
    private function applySanitization(mixed $value, string $strategy): mixed
    {
        if ($value === null) {
            return null;
        }

        $stringValue = (string) $value;

        return match ($strategy) {
            self::HASH => hash('sha256', $stringValue),
            self::MASK => $this->maskValue($stringValue),
            self::DOMAIN_ONLY => $this->extractDomain($stringValue),
            self::TRUNCATE => $this->truncateValue($stringValue),
            self::REMOVE => '[REMOVED]',
            default => $value,
        };
    }

    /**
     * Mask a value showing only first and last characters.
     */
    private function maskValue(string $value): string
    {
        $length = strlen($value);
        
        if ($length <= 2) {
            return str_repeat('*', $length);
        }

        if ($length <= 4) {
            return $value[0] . str_repeat('*', $length - 2) . $value[-1];
        }

        return $value[0] . $value[1] . str_repeat('*', $length - 4) . $value[-2] . $value[-1];
    }

    /**
     * Extract domain from email address.
     */
    private function extractDomain(string $value): string
    {
        if (str_contains($value, '@')) {
            return '@' . explode('@', $value)[1];
        }

        return $value;
    }

    /**
     * Truncate value to specified length.
     */
    private function truncateValue(string $value, int $length = 50): string
    {
        if (strlen($value) <= $length) {
            return $value;
        }

        return substr($value, 0, $length) . '...';
    }

    /**
     * ArrayAccess implementation
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * JsonSerializable implementation
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}