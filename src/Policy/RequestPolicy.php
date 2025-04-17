<?php

declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;
use Authorization\Policy\BeforePolicyInterface;
use Authorization\Policy\RequestPolicyInterface;
use Authorization\Policy\Result;
use Authorization\Policy\ResultInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class RequestPolicy implements RequestPolicyInterface, BeforePolicyInterface
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

    /**
     * Checks if the request can be accessed.
     *
     * @param \Authorization\IdentityInterface|null Identity
     * @param \Cake\Http\ServerRequest $request Server Request
     * @return bool
     */
    #[\Override]
    public function canAccess($identity, ServerRequest $request): bool|ResultInterface
    {

        if ($this->_authorize($identity, $request)) {
            return new Result(true);
        }

        return new Result(false, __('User {0} is not allowed to access the resource!', $identity->full_name));
    }

    /**
     * Authorizes a user.
     *
     * @param \App\Model\Entity\User $user The user to authorize
     * @param \Cake\Http\ServerRequest $request The request needing authorization.
     * @return bool
     */
    private function _authorize($user, ServerRequest $request): bool
    {
        $plugin = empty($request->getParam('plugin')) ? 'System' : str_replace('//', '/', preg_replace('/\//', '\\', Inflector::camelize($request->getParam('plugin'))));
        $controller = Inflector::camelize($request->getParam('controller'));
        $action = $request->getParam('action');

        $permissions = TableRegistry::getTableLocator()->get('Permissions');

        return $permissions->check($user->role_id, 'Site/' . $plugin . '/' . $controller . '/' . $action);
    }
}
