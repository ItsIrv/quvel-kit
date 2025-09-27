<?php

declare(strict_types=1);

namespace Quvel\Core\Logs;

use ArrayAccess;
use JsonSerializable;
use Closure;

/**
 * Context wrapper that applies sanitization rules to sensitive data before logging.
 * Provides explicit, declarative control over PII sanitization for compliance.
 */
class SanitizedContext implements ArrayAccess, JsonSerializable
{
    /**
     * Sanitization strategies
     */
    public const string HASH = 'hash';
    public const string MASK = 'mask';
    public const string DOMAIN_ONLY = 'domain_only';
    public const string TRUNCATE = 'truncate';
    public const string REMOVE = 'remove';

    /**
     * Custom sanitizer function.
     */
    protected static ?Closure $customSanitizer = null;

    /**
     * Create a new sanitized context.
     */
    public function __construct(
        private array $data,
        private readonly array $sanitizeRules = []
    ) {
    }

    /**
     * Set a custom sanitizer function.
     */
    public static function setCustomSanitizer(?Closure $sanitizer): void
    {
        static::$customSanitizer = $sanitizer;
    }

    /**
     * Create from array with sanitization rules.
     */
    public static function make(array $data, array $sanitizeRules = []): self
    {
        return new self($data, $sanitizeRules);
    }

    /**
     * Create with common PII sanitization rules.
     */
    public static function forPii(array $data): self
    {
        return new self($data, [
            'email' => self::DOMAIN_ONLY,
            'password' => self::REMOVE,
            'token' => self::HASH,
            'api_key' => self::HASH,
            'secret' => self::REMOVE,
            'phone' => self::MASK,
            'ssn' => self::MASK,
            'credit_card' => self::MASK,
        ]);
    }

    /**
     * Get the sanitized data array.
     */
    public function toArray(): array
    {
        if (!config('quvel-core.logging.context_enrichment.sanitize_sensitive_data', true)) {
            return $this->data;
        }

        if (static::$customSanitizer !== null) {
            return (static::$customSanitizer)($this->data, $this->sanitizeRules);
        }

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