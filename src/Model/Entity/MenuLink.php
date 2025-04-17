<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MenuLink Entity
 *
 * @property int $id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property string $title
 * @property string|null $icon
 * @property string|null $link
 * @property string|null $target
 * @property int|null $lft
 * @property int|null $rght
 *
 * @property \App\Model\Entity\Menu $menu
 * @property \App\Model\Entity\MenuLink $parent_menu_link
 * @property \App\Model\Entity\MenuLink[] $child_menu_links
 */
class MenuLink extends Entity
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
        'menu_id' => true,
        'parent_id' => true,
        'title' => true,
        'icon' => true,
        'link' => true,
        'target' => true,
        'lft' => true,
        'rght' => true,
        'menu' => true,
        'parent_menu_link' => true,
        'child_menu_links' => true
    ];

}
