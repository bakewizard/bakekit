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
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected array $_accessible = [
        'role_id' => true,
        'resource_id' => true,
        'allowed' => true,
        'role' => true,
        'resource' => true,
    ];
}
