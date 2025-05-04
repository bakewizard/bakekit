<?php
declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;
use Cake\ORM\Query\SelectQuery;

class UsersTablePolicy
{
    /**
     * Restricts index method
     *
     * @param \App\Model\Entity\User $user
     * @param \Cake\ORM\Query\SelectQuery $query
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeIndex(IdentityInterface $user, SelectQuery $query): SelectQuery
    {
        if (!$user->isRoot()) {
            return $query->where(['Users.id' => $user->getIdentifier()]);
        } else {
            return $query;
        }
    }
}
