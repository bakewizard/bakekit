<?php

declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;
use Cake\ORM\Query\SelectQuery;

class UsersTablePolicy
{

    public function scopeIndex(IdentityInterface $user, SelectQuery $query)
    {
        if (!$user->isRoot()) {
            return $query->where(['Users.id' => $user->getIdentifier()]);
        } else {
            return $query;
        }
    }
}
