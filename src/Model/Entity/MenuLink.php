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
 * @property int $lft
 * @property int $rght
 *
 * @property \App\Model\Entity\Menu $menu
 * @property \App\Model\Entity\MenuLink|null $parent_menu_link
 * @property \App\Model\Entity\MenuLink[] $child_menu_links
 * @property \Cake\ORM\Entity[] $_i18n
 */
class MenuLink extends Entity
{
    /**
     * @inheritDoc
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
        'child_menu_links' => true,
    ];
}
