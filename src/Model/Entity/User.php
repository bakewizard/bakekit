<?php
declare(strict_types=1);

namespace App\Model\Entity;

use ArrayAccess;
use Authentication\IdentityInterface as AuthenticationIdentity;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Authorization\AuthorizationServiceInterface;
use Authorization\IdentityInterface as AuthorizationIdentity;
use Authorization\Policy\ResultInterface;
use Cake\ORM\Entity;

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
 * @property array<\Cake\ORM\Entity> $files
 * @property-read string|null $full_name
 * @property \Authorization\AuthorizationServiceInterface $authorization
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
        'files' => true,
    ];
    protected array $_hidden = [
        'password',
    ];
    protected array $_virtual = [
        'full_name',
    ];

    /**
     * Checks if the user has the root role.
     *
     * @return bool True if the user is a root user, false otherwise.
     */
    public function isRoot(): bool
    {
        return $this->role_id === 1;
    }

    /**
     * @inheritDoc
     */
    public function can(string $action, mixed $resource): bool
    {
        return $this->authorization->can($this, $action, $resource);
    }

    /**
     * @inheritDoc
     */
    public function canResult(string $action, mixed $resource): ResultInterface
    {
        return $this->authorization->canResult($this, $action, $resource);
    }

    /**
     * @inheritDoc
     */
    public function applyScope(string $action, mixed $resource, mixed ...$optionalArgs): mixed
    {
        return $this->authorization->applyScope($this, $action, $resource, ...$optionalArgs);
    }

    /**
     * Retrieves the original data of the entity.
     *
     * @return \ArrayAccess<string, mixed>|array<string, mixed> The original data of the entity.
     */
    public function getOriginalData(): ArrayAccess|array
    {
        return $this;
    }

    /**
     * Gets the identifier for the user entity.
     *
     * @return array<int|string>|string|int|null The identifier of the user.
     */
    public function getIdentifier(): array|string|int|null
    {
        return $this->id;
    }

    /**
     * Sets the authorization service for the entity.
     *
     * @param \Authorization\AuthorizationServiceInterface $service The authorization service.
     * @return self
     */
    public function setAuthorization(AuthorizationServiceInterface $service): self
    {
        $this->authorization = $service;

        return $this;
    }

    /**
     * Sets the password after hashing it.
     *
     * @param string $password The plain password.
     * @return string|null The hashed password or the existing password if the input is empty.
     * @see \App\Model\Entity\User::$password
     */
    protected function _setPassword(string $password): ?string
    {
        if (strlen($password) > 0) {
            return (new DefaultPasswordHasher())->hash($password);
        }

        return $this->password;
    }

    /**
     * Gets the user's full name by concatenating the first and last names.
     *
     * @return string|null The full name of the user or null if either first or last name is not set.
     * @see \App\Model\Entity\User::$full_name
     */
    protected function _getFullName(): ?string
    {
        return isset($this->_fields['first_name'], $this->_fields['last_name']) ? $this->_fields['first_name'] . '  ' . $this->_fields['last_name'] : null;
    }
}
