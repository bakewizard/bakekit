<?php
declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;
use Authorization\Policy\BeforePolicyInterface;
use Authorization\Policy\Result;
use Authorization\Policy\ResultInterface;
use Cake\ORM\Query\SelectQuery;
use Override;

class RolesTablePolicy implements BeforePolicyInterface
{
    /**
     * Pre-authorization check
     *
     * @param \App\Model\Entity\User|null $identity
     * @param mixed $resource
     * @param string $action
     * @return \Authorization\Policy\ResultInterface|bool|null
     */
    #[Override]
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

    /**
     * Checks if user can view roles list
     *
     * @param \Authorization\IdentityInterfac $identity
     * @param \Cake\ORM\Query\SelectQuery $query
     * @return \Authorization\Policy\Result|bool
     */
    public function canIndex(IdentityInterface $identity, SelectQuery $query): bool|Result
    {
        return new Result(false, __('Only Root can control roles.'));
    }
}
