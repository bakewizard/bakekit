<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * UserImage Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $path
 *
 * @property \App\Model\Entity\User $user
 */
class UserImage extends Entity
{
    protected array $_virtual = ['tmp_name'];

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'name' => true,
        'path' => true,
        'user' => true,
        'tmp_name' => true,
    ];
}
