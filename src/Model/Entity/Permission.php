<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Permission Entity
 *
 * @property int $role_id
 * @property int $resource_id
 * @property bool $allowed
 *
 * @property \App\Model\Entity\Role $role
 * @property \App\Model\Entity\Resource $resource
 */
class Permission extends Entity
{
    /**
     * @inheritDoc
     */
    protected array $_accessible = [
        'role_id' => true,
        'resource_id' => true,
        'allowed' => true,
        'role' => true,
        'resource' => true,
    ];
}
