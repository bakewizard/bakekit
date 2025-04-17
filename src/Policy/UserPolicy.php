<?php

declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Authorization\IdentityInterface;
use Authorization\Policy\BeforePolicyInterface;
use Authorization\Policy\Result;
use Authorization\Policy\ResultInterface;

class UserPolicy implements BeforePolicyInterface
{

    #[\Override]
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

    public function canView(IdentityInterface $user, User $currentUser)
    {
        if ($user->id == $currentUser->id) {
            return new Result(true);
        }

        return new Result(false, __('User can only view his own profile.'));
    }

    public function canEdit(IdentityInterface $user, User $currentUser)
    {
        if ($user->id == $currentUser->id) {
            return new Result(true);
        }

        return new Result(false, __('User can only edit his own profile.'));
    }

    public function canAdd(IdentityInterface $user, User $currentUser)
    {
        return new Result(false, __('Only Root can create new users.'));
    }

    public function canDelete(IdentityInterface $user, User $currentUser)
    {
        return new Result(false, __('Only Root can delete other users.'));
    }
}
