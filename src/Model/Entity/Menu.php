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
 *
 * @property array<\App\Model\Entity\MenuLink> $menu_links
 */
class Menu extends Entity
{
    /**
     * @inheritDoc
     */
    protected array $_accessible = [
        'name' => true,
        'description' => true,
        'menu_links' => true,
    ];

    /**
     * Checks if the menu is a system (backend) menu based on its ID.
     *
     * @return bool True if the menu ID is 1 or 2, false otherwise.
     */
    public function isSystem(): bool
    {
        return in_array($this->id, [1, 2]);
    }
}
