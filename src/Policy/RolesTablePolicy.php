<?php

declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;
use Authorization\Policy\BeforePolicyInterface;
use Authorization\Policy\Result;
use Authorization\Policy\ResultInterface;
use Cake\ORM\Query\SelectQuery;

class RolesTablePolicy implements BeforePolicyInterface
{

    #[\Override]
    public function before(?IdentityInterface $identity, mixed $resource, string $action): ResultInterface|bool|null
    {
        if (!$identity) {
            return true;
        }

        if ($identity->isRoot()) {
            return true;
        }

        return null;
    }

    public function canIndex(IdentityInterface $identity, SelectQuery $query)
    {
        return new Result(false, __('Only Root can control roles.'));
    }
}
