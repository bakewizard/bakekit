<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Utility\Inflector;

/**
 * Plugin Entity
 *
 * @property int $id
 * @property string $name
 * @property string $alias
 * @property string|null $description
 * @property string|null $parent_plugin
 * @property bool|null $enabled
 *
 * @property \App\Model\Entity\Metum[] $meta
 */
class Plugin extends Entity
{
    /**
     * @inheritDoc
     */
    protected array $_accessible = [
        'name' => true,
        'alias' => true,
        'description' => true,
        'parent_plugin' => true,
        'enabled' => true,
        'meta' => true,
    ];

    /**
     * Setter for the alias property, which automatically generates a URL-friendly slug.
     *
     * @param string $alias The alias value to set.
     * @return string The generated slug.
     * @see \App\Model\Entity\Plugin::$alias
     */
    protected function _setAlias(string $alias): string
    {
        return Inflector::dasherize($alias);
    }
}
