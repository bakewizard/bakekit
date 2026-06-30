<?php
declare(strict_types=1);

namespace App\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\BaseType;
use InvalidArgumentException;
use JsonException;
use Override;
use PDO;

/**
 * Text and JSON type converter.
 *
 * Seamlessly marshals data between PHP structures (arrays, scalars) and the database.
 * Automatically detects and handles both native JSON strings and raw text fallback.
 */
class TextAndJsonType extends BaseType
{
    /**
     * Convert a PHP value into a JSON string for database storage.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string|null
     * @throws \InvalidArgumentException If a resource is passed.
     */
    #[Override]
    public function toDatabase(mixed $value, Driver $driver): ?string
    {
        if (is_resource($value)) {
            throw new InvalidArgumentException('Cannot convert a resource value to JSON.');
        }

        if ($value === null) {
            return null;
        }

        // If it is already a string, store it as-is, otherwise encode the structure
        return is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * Convert database string values back to PHP arrays, primitives, or raw strings.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return mixed
     */
    #[Override]
    public function toPHP(mixed $value, Driver $driver): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return $value;
        }

        // ✨ PHP 8.3 Optimization: Fast validation without allocating memory for decoding.
        // If it's a plain string (e.g., "BakeKit"), we exit early and return it directly.
        if (!json_validate($value)) {
            return $value;
        }

        // Safe decode for valid JSON primitives or arrays
        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $value;
        }
    }

    /**
     * Get the correct PDO binding type for string data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\Driver $driver The driver instance.
     * @return int
     */
    #[Override]
    public function toStatement(mixed $value, Driver $driver): int
    {
        return PDO::PARAM_STR;
    }

    /**
     * Marshals request data into a structure compatible with this type.
     *
     * @param mixed $value The value to convert.
     * @return mixed Converted value.
     */
    #[Override]
    public function marshal(mixed $value): mixed
    {
        return $value;
    }
}
