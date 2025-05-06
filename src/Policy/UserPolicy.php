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
     * @inheritDoc
     */
    #[Override]
    public function before(?IdentityInterface $identity, mixed $resource, string $action): ResultInterface|bool|null
    {
        if (!$identity) {
            return true;
        }

        if ($identity instanceof User) {
            if ($identity->isRoot()) {
                if ($action === 'delete' && isset($resource->id) && $identity->id == $resource->id) {
                    return new Result(false, __('Root user cannot be deleted.'));
                }

                return true;
            }
        }

        return null;
    }

    /**
     * View check
     *
     * @param \App\Model\Entity\User $user
     * @param \App\Model\Entity\User $currentUser
     * @return \Authorization\Policy\Result|bool
     */
    public function canView(User $user, User $currentUser): bool|Result
    {
        if ($user->id == $currentUser->id) {
            return new Result(true);
        }

        return new Result(false, __('User can only view his own profile.'));
    }

    /**
     * Edit check
     *
     * @param \App\Model\Entity\User $user
     * @param \App\Model\Entity\User $currentUser
     * @return \Authorization\Policy\Result|bool
     */
    public function canEdit(User $user, User $currentUser): bool|Result
    {
        if ($user->id == $currentUser->id) {
            return new Result(true);
        }

        return new Result(false, __('User can only edit his own profile.'));
    }

    /**
     * Add check
     *
     * @param \App\Model\Entity\User $user
     * @param \App\Model\Entity\User $currentUser
     * @return \Authorization\Policy\Result|bool
     */
    public function canAdd(User $user, User $currentUser): bool|Result
    {
        return new Result(false, __('Only Root can create new users.'));
    }

    /**
     * Delete check
     *
     * @param \App\Model\Entity\User $user
     * @param \App\Model\Entity\User $currentUser
     * @return \Authorization\Policy\Result|bool
     */
    public function canDelete(User $user, User $currentUser): bool|Result
    {
        return new Result(false, __('Only Root can delete other users.'));
    }
}
