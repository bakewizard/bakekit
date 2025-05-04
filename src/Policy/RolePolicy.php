<?php
declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;
use Authorization\Policy\BeforePolicyInterface;
use Authorization\Policy\Result;
use Authorization\Policy\ResultInterface;
use Override;

class RolePolicy implements BeforePolicyInterface
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
            if ($action === 'delete' && $identity->id == $resource->id) {
                return new Result(false, __('Root role cannot be deleted.'));
            }

            return true;
        }

        return null;
    }

    /**
     * Handles dynamic policy method calls for undefined actions.
     *
     * This fallback method is triggered when a specific action method (like canEdit, canView, etc.)
     * is not explicitly defined in the policy. It denies access by default with a generic message.
     *
     * @param string $name
     * @param array $arguments
     * @return \Authorization\Policy\Result
     */
    public function __call(string $name, array $arguments): Result
    {
        return new Result(false, __('Only Root can control roles.'));
    }
}
