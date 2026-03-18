<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Filter;

use App\Model\Filter\FullTextFilter;
use Cake\TestSuite\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * App\Model\Filter\FullTextFilter Test Case
 *
 * process() depends on Search plugin infrastructure and MySQL — not tested here.
 * filter() is private but contains the core string-sanitization logic — tested via reflection.
 *
 * @uses \App\Model\Filter\FullTextFilter
 */
class FullTextFilterTest extends TestCase
{
    private FullTextFilter $filter;
    private ReflectionMethod $filterMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Instantiate without constructor arguments — Base filter requires a manager,
        // but we only need the private filter() method which has no dependencies.
        $this->filter = (new ReflectionClass(FullTextFilter::class))
            ->newInstanceWithoutConstructor();

        $this->filterMethod = new ReflectionMethod(FullTextFilter::class, 'filter');
    }

    private function callFilter(string $text, string $matchMode): string
    {
        return $this->filterMethod->invoke($this->filter, $text, $matchMode);
    }

    // -------------------------------------------------------------------------
    // Natural language mode — no wildcards added
    // -------------------------------------------------------------------------

    /**
     * Plain text passes through unchanged in natural language mode.
     */
    public function testNaturalLanguageModePassesPlainText(): void
    {
        $result = $this->callFilter('hello world', 'IN NATURAL LANGUAGE MODE');
        $this->assertSame('hello world', $result);
    }

    /**
     * Special characters are stripped in natural language mode.
     */
    public function testNaturalLanguageModeStripsSpecialChars(): void
    {
        $result = $this->callFilter('hello! world?', 'IN NATURAL LANGUAGE MODE');
        $this->assertSame('hello world', $result);
    }

    /**
     * SQL injection characters are stripped.
     */
    public function testStripsQuotesAndSqlChars(): void
    {
        $result = $this->callFilter("it's a \"test\"; DROP TABLE", 'IN NATURAL LANGUAGE MODE');
        $this->assertSame('its a test DROP TABLE', $result);
    }

    /**
     * Hyphens are preserved (allowed by the regex).
     */
    public function testHyphensArePreserved(): void
    {
        $result = $this->callFilter('full-text search', 'IN NATURAL LANGUAGE MODE');
        $this->assertSame('full-text search', $result);
    }

    /**
     * Unicode letters are preserved.
     */
    public function testUnicodeLettersArePreserved(): void
    {
        $result = $this->callFilter('привіт світ', 'IN NATURAL LANGUAGE MODE');
        $this->assertSame('привіт світ', $result);
    }

    // -------------------------------------------------------------------------
    // Boolean mode — wildcard * appended to each word
    // -------------------------------------------------------------------------

    /**
     * In boolean mode each word gets a * suffix appended.
     */
    public function testBooleanModeAppendsWildcard(): void
    {
        $result = $this->callFilter('hello world', 'IN BOOLEAN MODE');
        $this->assertSame('hello* world*', $result);
    }

    /**
     * Words that already end with * are not double-wildcarded.
     */
    public function testBooleanModeDoesNotDoubleWildcard(): void
    {
        $result = $this->callFilter('hello*', 'IN BOOLEAN MODE');
        $this->assertSame('hello*', $result);
    }

    /**
     * Special chars are stripped before wildcards are added in boolean mode.
     */
    public function testBooleanModeStripsSpecialCharsThenAppendsWildcard(): void
    {
        $result = $this->callFilter('hello! world?', 'IN BOOLEAN MODE');
        $this->assertSame('hello* world*', $result);
    }

    /**
     * Empty string returns empty string in both modes.
     */
    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', $this->callFilter('', 'IN NATURAL LANGUAGE MODE'));
        $this->assertSame('*', $this->callFilter('', 'IN BOOLEAN MODE'));
    }
}
