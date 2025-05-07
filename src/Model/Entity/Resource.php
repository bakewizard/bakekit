<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Resource Entity
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $alias
 * @property int $lft
 * @property int $rght
 *
 * @property \App\Model\Entity\Resource|null $parent_resource
 * @property array<\App\Model\Entity\Resource> $child_resources
 * @property array<\App\Model\Entity\Permission> $permissions
 * @property array<\App\Model\Entity\Role> $roles
 * @property \App\Model\Entity\Permission $_joinData
 */
class Resource extends Entity
{
    /**
     * @inheritDoc
     */
    protected array $_accessible = [
        'parent_id' => true,
        'alias' => true,
        'lft' => true,
        'rght' => true,
        'parent_resource' => true,
        'child_resources' => true,
        'permissions' => true,
    ];
}
