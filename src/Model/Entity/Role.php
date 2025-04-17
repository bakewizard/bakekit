<?php

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Utility\Text;

/**
 * Role Entity
 *
 * @property int $id
 * @property int|null $parent_id
 * @property int $lft
 * @property int $rght
 * @property string $name
 * @property string $alias
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\User[] $users
 */
class Role extends Entity
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
        'lft' => true,
        'rght' => true,
        'name' => true,
        'alias' => true,
        'created' => true,
        'modified' => true,
        'users' => true,
        'permissions' => true
    ];

    public function isRoot(): bool
    {
        return $this->id === 1;
    }

    protected function _setAlias($alias)
    {
        return strtolower(Text::slug($alias));
    }

}
