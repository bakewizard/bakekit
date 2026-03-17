<?php
declare(strict_types=1);

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Link
{
    /**
     * @param string $summary A brief summary of the link.
     * @param string $description A detailed description of the link.
     * @param string $picker A name of admin controller for selecting related resources (optional).
     */
    public function __construct(
        public readonly string $summary,
        public readonly string $description,
        public readonly string $picker = '',
    ) {
    }
}
