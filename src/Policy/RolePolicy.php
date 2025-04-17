<?php

declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;
use Authorization\Policy\BeforePolicyInterface;
use Authorization\Policy\Result;
use Authorization\Policy\ResultInterface;

class RolePolicy implements BeforePolicyInterface
{

    #[\Override]
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

    public function __call($name, $arguments)
    {
        return new Result(false, __('Only Root can control roles.'));
    }
}
