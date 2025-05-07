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
     * @param \App\Model\Entity\User $user The user identity.
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\User> $query The query to modify.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\User> The modified query.
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
