<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Authorization\IdentityInterface;
use Authorization\Policy\BeforePolicyInterface;
use Authorization\Policy\Result;
use Authorization\Policy\ResultInterface;
use Override;

class UserPolicy implements BeforePolicyInterface
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
                return new Result(false, __('Root user cannot be deleted.'));
            }

            return true;
        }

        return null;
    }

    /**
     * View check
     *
     * @param \Authorization\IdentityInterface $user
     * @param \App\Model\Entity\User $currentUser
     * @return \Authorization\Policy\Result|bool
     */
    public function canView(IdentityInterface $user, User $currentUser): bool|Result
    {
        if ($user->id == $currentUser->id) {
            return new Result(true);
        }

        return new Result(false, __('User can only view his own profile.'));
    }

    /**
     * Edit check
     *
     * @param \Authorization\IdentityInterface $user
     * @param \App\Model\Entity\User $currentUser
     * @return \Authorization\Policy\Result|bool
     */
    public function canEdit(IdentityInterface $user, User $currentUser): bool|Result
    {
        if ($user->id == $currentUser->id) {
            return new Result(true);
        }

        return new Result(false, __('User can only edit his own profile.'));
    }

    /**
     * Add check
     *
     * @param \Authorization\IdentityInterface $user
     * @param \App\Model\Entity\User $currentUser
     * @return \Authorization\Policy\Result|bool
     */
    public function canAdd(IdentityInterface $user, User $currentUser): bool|Result
    {
        return new Result(false, __('Only Root can create new users.'));
    }

    /**
     * Delete check
     *
     * @param \Authorization\IdentityInterface $user
     * @param \App\Model\Entity\User $currentUser
     * @return \Authorization\Policy\Result|bool
     */
    public function canDelete(IdentityInterface $user, User $currentUser): bool|Result
    {
        return new Result(false, __('Only Root can delete other users.'));
    }
}
