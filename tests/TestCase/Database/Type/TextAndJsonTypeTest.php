<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Type;

use App\Database\Type\TextAndJsonType;
use Cake\Database\Driver;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PDO;

/**
 * App\Database\Type\TextAndJsonType Test Case
 *
 * TextAndJsonType is a dual-purpose column type:
 * - stores PHP values as JSON strings in the database
 * - reads JSON strings back as PHP arrays
 * - passes plain strings through unchanged (for backwards compatibility)
 *
 * @uses \App\Database\Type\TextAndJsonType
 */
class TextAndJsonTypeTest extends TestCase
{
    private TextAndJsonType $type;
    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->type = new TextAndJsonType();
        $this->driver = $this->createStub(Driver::class);
    }

    // -------------------------------------------------------------------------
    // toDatabase()
    // -------------------------------------------------------------------------

    /**
     * null stays null — no JSON encoding needed.
     */
    public function testToDatabaseNullReturnsNull(): void
    {
        $this->assertNull($this->type->toDatabase(null, $this->driver));
    }

    /**
     * A plain string is passed through unchanged — no double-encoding.
     */
    public function testToDatabaseStringPassthrough(): void
    {
        $this->assertSame('hello', $this->type->toDatabase('hello', $this->driver));
    }

    /**
     * A string that looks like JSON is also passed through unchanged.
     */
    public function testToDatabaseJsonStringPassthrough(): void
    {
        $json = '{"key":"value"}';
        $this->assertSame($json, $this->type->toDatabase($json, $this->driver));
    }

    /**
     * An array is encoded to a JSON string.
     */
    public function testToDatabaseArrayEncodedToJson(): void
    {
        $result = $this->type->toDatabase(['key' => 'value'], $this->driver);
        $this->assertSame('{"key":"value"}', $result);
    }

    /**
     * A nested array is encoded correctly.
     */
    public function testToDatabaseNestedArrayEncodedToJson(): void
    {
        $result = $this->type->toDatabase(['a' => [1, 2, 3]], $this->driver);
        $this->assertSame('{"a":[1,2,3]}', $result);
    }

    /**
     * An integer is encoded to a JSON string.
     */
    public function testToDatabaseIntegerEncodedToJson(): void
    {
        $this->assertSame('42', $this->type->toDatabase(42, $this->driver));
    }

    /**
     * A boolean is encoded to a JSON string.
     */
    public function testToDatabaseBooleanEncodedToJson(): void
    {
        $this->assertSame('true', $this->type->toDatabase(true, $this->driver));
        $this->assertSame('false', $this->type->toDatabase(false, $this->driver));
    }

    /**
     * A resource value throws InvalidArgumentException.
     */
    public function testToDatabaseResourceThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = fopen('php://memory', 'r');
        try {
            $this->type->toDatabase($resource, $this->driver);
        } finally {
            fclose($resource);
        }
    }

    // -------------------------------------------------------------------------
    // toPHP()
    // -------------------------------------------------------------------------

    /**
     * null stays null.
     */
    public function testToPHPNullReturnsNull(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));
    }

    /**
     * A valid JSON string is decoded to a PHP array.
     */
    public function testToPHPJsonStringDecodedToArray(): void
    {
        $result = $this->type->toPHP('{"key":"value"}', $this->driver);
        $this->assertSame(['key' => 'value'], $result);
    }

    /**
     * A JSON array string is decoded to a PHP array.
     */
    public function testToPHPJsonArrayDecodedToArray(): void
    {
        $result = $this->type->toPHP('[1,2,3]', $this->driver);
        $this->assertSame([1, 2, 3], $result);
    }

    /**
     * A plain string that is not valid JSON is returned as-is.
     */
    public function testToPHPPlainStringReturnedAsIs(): void
    {
        $result = $this->type->toPHP('just a string', $this->driver);
        $this->assertSame('just a string', $result);
    }

    /**
     * An empty string is returned as-is (json_decode('') returns null, ?? falls back).
     */
    public function testToPHPEmptyStringReturnedAsIs(): void
    {
        $result = $this->type->toPHP('', $this->driver);
        $this->assertSame('', $result);
    }

    // -------------------------------------------------------------------------
    // toStatement()
    // -------------------------------------------------------------------------

    /**
     * Always returns PDO::PARAM_STR regardless of value.
     */
    public function testToStatementAlwaysReturnsParamStr(): void
    {
        $this->assertSame(PDO::PARAM_STR, $this->type->toStatement('any', $this->driver));
        $this->assertSame(PDO::PARAM_STR, $this->type->toStatement(null, $this->driver));
        $this->assertSame(PDO::PARAM_STR, $this->type->toStatement([], $this->driver));
    }

    // -------------------------------------------------------------------------
    // marshal()
    // -------------------------------------------------------------------------

    /**
     * marshal() is a passthrough — returns value unchanged.
     */
    public function testMarshalReturnsValueUnchanged(): void
    {
        $this->assertSame('hello', $this->type->marshal('hello'));
        $this->assertSame(['a' => 1], $this->type->marshal(['a' => 1]));
        $this->assertNull($this->type->marshal(null));
        $this->assertSame(42, $this->type->marshal(42));
    }
}
