<?php

namespace App\Lib;

/**
 * Parses PHP docblocks into summary, description, and tags.
 */
class DocBlockParser
{

    private string $summary = '';
    private string $description = '';
    private array $tags = [];

    public function __construct(?string $docBlock = null)
    {
        if ($docBlock) {
            $this->parse($docBlock);
        }
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getDescription(): string
    {
        return trim($this->description); // Trim to remove trailing newlines
    }

    public function getTag(string $tagName): array|string|false
    {
        return $this->tags[$tagName] ?? false;
    }

    private function parse(string $docBlock): void
    {
        $lines = preg_split("/\r\n|\n|\r/", $docBlock);
        if (!$lines) {
            return;
        }

        array_shift($lines); // Remove opening `/**`
        array_pop($lines);   // Remove closing `*/`

        $insideDescription = false;

        foreach ($lines as $line) {
            $trimmedLine = trim($line, " *");

            if ($trimmedLine === '') {
                continue;
            }

            if (str_starts_with($trimmedLine, '@')) {
                $insideDescription = false; // Stop adding to description when tags begin
                $this->parseTag($trimmedLine);
            } elseif ($this->summary === '') {
                $this->summary = $trimmedLine;
            } else {
                $insideDescription = true;
                $this->description .= $trimmedLine . PHP_EOL;
            }
        }
    }

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
