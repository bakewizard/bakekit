<?php
declare(strict_types=1);

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Resource
{
    /**
     * @param string $label A human-readable description of the action shown in the permissions UI.
     *                      Falls back to the method name (alias) if empty.
     */
    public function __construct(
        public readonly string $label = '',
    ) {
    }
}
