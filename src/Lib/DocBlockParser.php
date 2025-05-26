<?php
declare(strict_types=1);

namespace App\Lib;

/**
 * Class DocBlockParser
 *
 * Parses PHP docblocks into summary, description, and tags.
 *
 * This class provides functionality to parse a given PHP docblock into its components,
 * including the summary, description, and any tags that may be present (such as @param, @return, etc.).
 * The parsed information can be retrieved using the appropriate methods.
 *
 * @package App\Lib
 */
class DocBlockParser
{
    /**
     * The first non-empty line before description and tags.
     */
    private string $summary = '';

    /**
     * Lines after summary and before tags.
     */
    private string $description = '';

    /**
     * Tags stored as an associative array where the key is the tag name without '@'.
     *
     * @var array<string, string|array>
     */
    private array $tags = [];

    /**
     * DocBlockParser constructor.
     *
     * If a docblock string is provided, it will be parsed immediately.
     *
     * @param string|null $docBlock The docblock string to parse (optional).
     */
    public function __construct(?string $docBlock = null)
    {
        if ($docBlock !== null) {
            $this->parse($docBlock);
        }
    }

    /**
     * Get the summary from the docblock.
     *
     * The summary is the first non-empty line before any description or tags.
     *
     * @return string The summary text.
     */
    public function getSummary(): string
    {
        return $this->summary;
    }

    /**
     * Get the description from the docblock.
     *
     * The description consists of lines following the summary and before tags.
     *
     * @return string The description text (trimmed).
     */
    public function getDescription(): string
    {
        return trim($this->description);
    }

    /**
     * Retrieves the value(s) of a specific tag.
     *
     * Returns string if only one tag of that type, array if multiple, or null if not found.
     *
     * @param string $tagName The tag name without '@' (e.g. 'param', 'return').
     * @return array|string|null Tag value(s) or null if tag not found.
     */
    public function getTag(string $tagName): string|array|null
    {
        return $this->tags[$tagName] ?? null;
    }

    /**
     * Parses the docblock string into summary, description, and tags.
     *
     * @param string $docBlock The docblock string to parse.
     * @return void
     */
    private function parse(string $docBlock): void
    {
        $lines = preg_split("/\r\n|\n|\r/", $docBlock);

        if ($lines === false || count($lines) < 2) {
            // No lines or malformed docblock
            return;
        }

        // Remove the opening /** and closing */
        array_shift($lines);
        array_pop($lines);

        foreach ($lines as $line) {
            $trimmedLine = trim($line, ' *');

            if ($trimmedLine === '') {
                continue;
            }

            if (str_starts_with($trimmedLine, '@')) {
                $this->parseTag($trimmedLine);
            } elseif ($this->summary === '') {
                $this->summary = $trimmedLine;
            } else {
                $this->description .= $trimmedLine . PHP_EOL;
            }
        }
    }

    /**
     * Parses a single tag line and stores its name and value.
     *
     * @param string $tagLine A tag line (starting with '@').
     * @return void
     */
    private function parseTag(string $tagLine): void
    {
        $parts = preg_split('/\s+/', ltrim($tagLine, '@'), 2);
        $name = $parts[0] ?? '';
        $value = $parts[1] ?? '';

        if ($name === '') {
            return;
        }

        if (!isset($this->tags[$name])) {
            $this->tags[$name] = $value;
        } else {
            if (!is_array($this->tags[$name])) {
                $this->tags[$name] = [$this->tags[$name]];
            }
            $this->tags[$name][] = $value;
        }
    }
}
