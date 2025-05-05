<?php
declare(strict_types=1);

namespace App\Lib;

/**
 * Class DocBlockParser
 *
 * Parses PHP docblocks into summary, description, and tags.
 *
 * This class provides functionality to parse a given PHP docblock into its components, including the summary,
 * description, and any tags that may be present (such as @param, @return, etc.). The parsed information can
 * be retrieved using the appropriate methods.
 *
 * @package App\Lib
 */
class DocBlockParser
{
    private string $summary = '';
    private string $description = '';
    private array $tags = [];

    /**
     * DocBlockParser constructor.
     *
     * If a docblock is provided, it will be parsed immediately.
     *
     * @param string|null $docBlock The docblock string to parse (optional).
     */
    public function __construct(?string $docBlock = null)
    {
        if ($docBlock) {
            $this->parse($docBlock);
        }
    }

    /**
     * Retrieves the summary from the docblock.
     *
     * The summary is the first non-empty line in the docblock before any description or tags.
     *
     * @return string The summary of the docblock.
     */
    public function getSummary(): string
    {
        return $this->summary;
    }

    /**
     * Retrieves the description from the docblock.
     *
     * The description consists of the lines following the summary and before any tags.
     *
     * @return string The description of the docblock.
     */
    public function getDescription(): string
    {
        return trim($this->description); // Trim to remove trailing newlines
    }

    /**
     * Retrieves the value(s) of a specific tag in the docblock.
     *
     * If the tag does not exist, false is returned.
     *
     * @param string $tagName The name of the tag (e.g., '@param', '@return').
     * @return array|string|false The value(s) associated with the tag or false if the tag is not found.
     */
    public function getTag(string $tagName): array|string|false
    {
        return $this->tags[$tagName] ?? false;
    }

    /**
     * Parses the given docblock string into summary, description, and tags.
     *
     * This method processes the docblock line-by-line, separating the summary, description, and tags as it goes.
     *
     * @param string $docBlock The docblock string to parse.
     */
    private function parse(string $docBlock): void
    {
        $lines = preg_split("/\r\n|\n|\r/", $docBlock);
        if (!$lines) {
            return;
        }

        array_shift($lines);// Removes opening `/**`
        array_pop($lines);// Removes closing `*/`

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
     * Parses a single tag line and stores the tag name and its associated value.
     *
     * @param string $tagLine A line containing a tag and its value.
     */
    private function parseTag(string $tagLine): void
    {
        $parts = preg_split('/\s+/', ltrim($tagLine, '@'), 2);
        $name = $parts[0] ?? '';
        $value = $parts[1] ?? '';

        if ($name) {
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
}
