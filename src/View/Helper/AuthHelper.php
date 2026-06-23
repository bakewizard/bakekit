<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Model\Entity\User;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\View\Helper;
use Override;

/**
 * Auth helper
 *
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class AuthHelper extends Helper
{
    /**
     * Request object
     *
     * @var \Cake\Http\ServerRequest
     */
    protected ServerRequest $_request;

    /**
     * Session object
     *
     * @var \Cake\Http\Session
     */
    protected Session $_session;

    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        $this->_request = $this->getView()->getRequest();
        $this->_session = $this->_request->getSession();
    }

    /**
     * Checks if the user is root
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->_request->getAttribute('identity')->getIdentifier() === 1;
    }

    /**
     * Checks if current user is logged in.
     *
     * @param string $model Model name
     * @return bool
     */
    public function isLoggedIn(string $model = 'User'): bool
    {
        return $this->_session->check("Auth.$model");
    }

    /**
     * Checks if given user is logged in.
     *
     * @param \App\Model\Entity\User $entity User entity
     * @param string $model Model name
     * @return bool
     */
    public function isUserLoggedIn(?User $entity, string $model = 'User'): bool
    {
        if (is_null($entity)) {
            return false;
        }

        return $this->_session->check("Auth.$model") && $this->get('id', $model) === $entity->id;
    }

    /**
     * Returns given session variable
     *
     * @param string $key Session key
     * @param string $model Model name
     * @return mixed|null The value of the session variable
     */
    public function get(string $key, string $model = 'User'): mixed
    {
        return $this->_session->read("Auth.$model.$key");
    }
}
