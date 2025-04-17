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
 * @property string|null $prefix
 * @property bool|null $enabled
 *
 * @property \App\Model\Entity\MenuLink[] $menu_links
 */
class Menu extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected array $_accessible = [
        'name' => true,
        'description' => true,
        'prefix' => true,
        'enabled' => true,
        'menu_links' => true
    ];

}
