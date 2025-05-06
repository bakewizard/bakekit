<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Authorization\IdentityInterface;
use Authorization\Policy\BeforePolicyInterface;
use Authorization\Policy\Result;
use Authorization\Policy\ResultInterface;
use Cake\ORM\Query\SelectQuery;
use Override;

class RolesTablePolicy implements BeforePolicyInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function before(?IdentityInterface $identity, mixed $resource, string $action): ResultInterface|bool|null
    {
        if (!$identity) {
            return true;
        }

        if ($identity instanceof User && $identity->isRoot()) {
            return true;
        }

        return null;
    }

    /**
     * Checks if user can view roles list
     *
     * @param \Authorization\IdentityInterface $identity
     * @param \Cake\ORM\Query\SelectQuery $query
     * @return \Authorization\Policy\Result|bool
     */
    public function canIndex(IdentityInterface $identity, SelectQuery $query): bool|Result
    {
        return new Result(false, __('Only Root can control roles.'));
    }
}
