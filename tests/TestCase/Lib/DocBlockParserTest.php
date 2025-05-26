<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\DocBlockParser;
use PHPUnit\Framework\TestCase;

class DocBlockParserTest extends TestCase
{
    /**
     * Tests that the parser correctly extracts the summary and multiline description
     * from a well-formed docblock.
     */
    public function testParsesSummaryAndDescription(): void
    {
        $doc = <<<DOC
        /**
         * This is the summary.
         *
         * This is the description line 1.
         * This is the description line 2.
         */
        DOC;

        $parser = new DocBlockParser($doc);

        // Assert the summary matches the first non-empty line
        $this->assertSame('This is the summary.', $parser->getSummary());

        // Assert the description contains all lines after summary until tags (trimmed)
        $expectedDescription = "This is the description line 1.\nThis is the description line 2.";
        $this->assertSame($expectedDescription, $parser->getDescription());
    }

    /**
     * Tests that a single tag (e.g., @return) is parsed and returned correctly.
     */
    public function testParsesSingleTag(): void
    {
        $doc = <<<DOC
        /**
         * Summary
         *
         * @return string
         */
        DOC;

        $parser = new DocBlockParser($doc);

        // Assert the @return tag value is correctly parsed
        $this->assertSame('string', $parser->getTag('return'));
    }

    /**
     * Tests that multiple tags of the same name (e.g., multiple @param) are
     * correctly parsed into an array.
     */
    public function testParsesMultipleTagsOfSameType(): void
    {
        $doc = <<<DOC
        /**
         * Summary
         *
         * @param int \$a Description of a
         * @param string \$b Description of b
         */
        DOC;

        $parser = new DocBlockParser($doc);

        // Get the multiple @param tags as an array
        $params = $parser->getTag('param');

        // Assert it's an array with two entries
        $this->assertIsArray($params);
        $this->assertCount(2, $params);

        // Assert the first and second param tag values match expected
        $this->assertSame('int $a Description of a', $params[0]);
        $this->assertSame('string $b Description of b', $params[1]);
    }

    /**
     * Tests that querying a tag which doesn't exist returns null.
     */
    public function testReturnsNullForMissingTag(): void
    {
        $doc = <<<DOC
        /**
         * Summary
         */
        DOC;

        $parser = new DocBlockParser($doc);

        // Assert that an undefined tag returns null
        $this->assertNull($parser->getTag('return'));
    }

    /**
     * Tests the parser's behavior when provided with an invalid or
     * non-standard docblock string (no starting and ending delimiters).
     */
    public function testHandlesEmptyOrMalformedDocblock(): void
    {
        $parser = new DocBlockParser('just some random text');

        // Since no proper docblock format, summary and description remain empty
        $this->assertSame('', $parser->getSummary());
        $this->assertSame('', $parser->getDescription());

        // Any tag query returns null
        $this->assertNull($parser->getTag('param'));
    }
}
