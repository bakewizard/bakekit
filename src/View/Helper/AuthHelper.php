<?php

declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;
use App\Model\Entity\User;

/**
 * Auth helper
 */
class AuthHelper extends Helper
{

    /**
     * Request object
     * 
     * @var null|\Cake\Http\Client\Request 
     */
    protected $_request;

    /**
     * Session object
     *
     * @var null|\Cake\Http\Session
     */
    protected $_session;

    #[\Override]
    public function initialize(array $config): void
    {
        $this->_request = $this->getView()->getRequest();
        $this->_session = $this->_request->getSession();
    }

    public function isRoot()
    {
        return $this->_request->getAttribute('identity')->getIdentifier() === 1;
    }

    /**
     * Checks if current user is logged in.
     * 
     * @param type $model Model name
     * @return bool
     */
    public function isLoggedIn($model = 'User')
    {
        return $this->_session->check("Auth.$model");
    }

    /**
     * Checks if given user is logged in.
     * 
     * @param User $entity User entity
     * @param type $model Model name
     * @return bool
     */
    public function isUserLoggedIn(?User $entity, $model = 'User')
    {
        if (is_null($entity)) {
            return false;
        }
        return $this->_session->check("Auth.$model") && $this->get('id', $model) === $entity->id;
    }

    public function get($key, $model = 'User')
    {
        return $this->_session->read("Auth.$model.$key");
    }
}
