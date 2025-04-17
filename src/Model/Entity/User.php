<?php

declare(strict_types=1);

namespace App\Model\Entity;

use ArrayAccess;
use Authorization\AuthorizationServiceInterface;
use Authorization\Policy\ResultInterface;
use Cake\ORM\Entity;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Authentication\IdentityInterface as AuthenticationIdentity;
use Authorization\IdentityInterface as AuthorizationIdentity;

/**
 * User Entity
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $alias
 * @property string $email
 * @property string $password
 * @property int $role_id
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Role $role
 */
class User extends Entity implements AuthenticationIdentity, AuthorizationIdentity
{

    protected array $_accessible = [
        'first_name' => true,
        'last_name' => true,
        'alias' => true,
        'email' => true,
        'password' => true,
        'role_id' => true,
        'created' => true,
        'modified' => true,
        'role' => true,
        'files' => true
    ];
    protected array $_hidden = [
        'password'
    ];
    protected array $_virtual = [
        'full_name'
    ];

    public function isRoot(): bool
    {
        return $this->role_id === 1;
    }

    public function can(string $action, mixed $resource): bool
    {
        return $this->authorization->can($this, $action, $resource);
    }

    public function canResult(string $action, mixed $resource): ResultInterface
    {
        return $this->authorization->canResult($this, $action, $resource);
    }

    public function applyScope(string $action, mixed $resource, mixed ...$optionalArgs): mixed
    {
        return $this->authorization->applyScope($this, $action, $resource, ...$optionalArgs);
    }

    public function getOriginalData(): ArrayAccess|array
    {
        return $this;
    }

    public function getIdentifier(): array|string|int|null
    {
        return $this->id;
    }

    public function setAuthorization(AuthorizationServiceInterface $service)
    {
        $this->authorization = $service;

        return $this;
    }

    protected function _setPassword(string $password): ?string
    {
        if (strlen($password) > 0) {
            return (new DefaultPasswordHasher)->hash($password);
        }

        return $this->password;
    }

    protected function _getFullName(): string|null
    {
        return isset($this->_fields['first_name'], $this->_fields['last_name']) ? $this->_fields['first_name'] . '  ' . $this->_fields['last_name'] : null;
    }
}
