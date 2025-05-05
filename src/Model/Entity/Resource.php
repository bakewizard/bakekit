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
 * @property \App\Model\Entity\Resource[] $child_resources
 * @property \App\Model\Entity\Permission[] $permissions
 * @property \App\Model\Entity\Role[] $roles
 * @property \App\Model\Entity\Permission $_joinData
 */
class Resource extends Entity
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
        'parent_id' => true,
        'alias' => true,
        'lft' => true,
        'rght' => true,
        'parent_resource' => true,
        'child_resources' => true,
        'permissions' => true,
    ];
}
