<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Authorization\IdentityInterface;
use Authorization\Policy\BeforePolicyInterface;
use Authorization\Policy\RequestPolicyInterface;
use Authorization\Policy\Result;
use Authorization\Policy\ResultInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Override;

class RequestPolicy implements RequestPolicyInterface, BeforePolicyInterface
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
     * @inheritDoc
     */
    #[Override]
    public function canAccess(?IdentityInterface $identity, ServerRequest $request): bool|ResultInterface
    {
        if ($identity instanceof User && $this->authorize($identity, $request)) {
            return new Result(true);
        }

        return new Result(false, __('User {0} is not allowed to access the resource!', $identity->full_name ?? 'Guest'));
    }

    /**
     * Authorizes a user.
     *
     * @param \App\Model\Entity\User $user The user to authorize
     * @param \Cake\Http\ServerRequest $request The request needing authorization.
     * @return bool
     */
    private function authorize(User $user, ServerRequest $request): bool
    {
        $pluginParam = $request->getParam('plugin');
        $plugin = empty($pluginParam)
        ? 'System'
        : str_replace('//', '/', str_replace('/', '\\', Inflector::camelize($pluginParam)));

        $controller = Inflector::camelize($request->getParam('controller'));
        $action = $request->getParam('action');

        $permissions = TableRegistry::getTableLocator()->get('Permissions');
        $path = sprintf('Site/%s/%s/%s', $plugin, $controller, $action);

        return $permissions->check($user->role_id, $path);
    }
}
