<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Menu Entity
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property bool|null $prefix
 * @property bool|null $enabled
 *
 * @property \App\Model\Entity\MenuLink[] $menu_links
 */
class Menu extends Entity
{
    /**
     * @inheritDoc
     */
    protected array $_accessible = [
        'name' => true,
        'description' => true,
        'prefix' => true,
        'enabled' => true,
        'menu_links' => true,
    ];
}
