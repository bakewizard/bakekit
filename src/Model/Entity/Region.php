<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Utility\Text;

/**
 * Region Entity
 *
 * @property int $id
 * @property string $alias
 * @property string $theme
 * @property string|null $description
 *
 * @property array<\App\Model\Entity\Block> $blocks
 */
class Region extends Entity
{
    /**
     * @inheritDoc
     */
    protected array $_accessible = [
        'alias' => true,
        'theme' => true,
        'description' => true,
        'blocks' => true,
    ];

    /**
     * Setter for the alias property, which automatically generates a URL-friendly slug.
     *
     * @param string $alias The alias value to set.
     * @return string The generated slug.
     * @see \App\Model\Entity\Region::$alias
     */
    protected function _setAlias(string $alias): string
    {
        return strtolower(Text::slug($alias));
    }
}
