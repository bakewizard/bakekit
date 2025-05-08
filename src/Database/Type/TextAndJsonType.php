<?php
declare(strict_types=1);

namespace App\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\BaseType;
use InvalidArgumentException;
use Override;
use PDO;

/**
 * Json type converter.
 *
 * Use to convert json data between PHP and the database types.
 */
class TextAndJsonType extends BaseType
{
    /**
     * Convert a value data into a JSON string
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return string|null
     */
    #[Override]
    public function toDatabase(mixed $value, Driver $driver): mixed
    {
        if (is_resource($value)) {
            throw new InvalidArgumentException('Cannot convert a resource value to JSON');
        }

        if (is_null($value)) {
            return null;
        }

        $json = is_string($value) ? $value : json_encode($value);

        return $json === false ? null : $json;
    }

    /**
     * Convert string values to PHP arrays.
     *
     * @param mixed $value The value to convert.
     * @param \Cake\Database\Driver $driver The driver instance to convert with.
     * @return mixed|null
     */
    #[Override]
    public function toPHP(mixed $value, Driver $driver): mixed
    {
        if (is_null($value)) {
            return null;
        }

        return json_decode($value, true) ?? $value;
    }

    /**
     * Get the correct PDO binding type for string data.
     *
     * @param mixed $value The value being bound.
     * @param \Cake\Database\Driver $driver The driver.
     * @return int
     */
    #[Override]
    public function toStatement(mixed $value, Driver $driver): int
    {
        return PDO::PARAM_STR;
    }

    /**
     * Marshals request data into a JSON compatible structure.
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
