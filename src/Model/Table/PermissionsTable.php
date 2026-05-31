<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Cache\Cache;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * Permissions Model
 *
 * @property \App\Model\Table\RolesTable&\Cake\ORM\Association\BelongsTo $Roles
 * @property \App\Model\Table\ResourcesTable&\Cake\ORM\Association\BelongsTo $Resources
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Permission>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Permission> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Permission>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Permission> deleteManyOrFail(iterable $entities, array $options = [])
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\Permission>
 */
class PermissionsTable extends Table
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('permissions');
        $this->setPrimaryKey(['role_id', 'resource_id']);

        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Resources', [
            'foreignKey' => 'resource_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->boolean('allowed')
            ->requirePresence('allowed', 'create')
            ->notEmptyString('allowed');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['role_id'], 'Roles'), ['errorField' => 'role_id']);
        $rules->add($rules->existsIn(['resource_id'], 'Resources'), ['errorField' => 'resource_id']);

        return $rules;
    }

    /**
     * Clears the permissions cache after a record is saved.
     *
     * @param \Cake\Event\EventInterface $event
     * @param \App\Model\Entity\Permission $entity
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity): void
    {
        $this->clearPermissionsCache();
    }

    /**
     * Clears the permissions cache after a record is deleted.
     *
     * @param \Cake\Event\EventInterface $event
     * @param \App\Model\Entity\Permission $entity
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity): void
    {
        $this->clearPermissionsCache();
    }

    /**
     * Clears the permissions cache.
     * Call this explicitly after bulk operations (e.g. deleteAll) that bypass callbacks.
     *
     * @return void
     */
    public function clearPermissionsCache(): void
    {
        Cache::clear('permissions');
    }

    /**
     * Updates an existing record if one already exists (upsert).
     *
     * @param string|int $role Role id
     * @param string|int $resource Resource id
     * @param string|int $value 1 = allow, 0 = deny
     * @return bool
     */
    public function allow(int|string $role, int|string $resource, int|string $value = 1): bool
    {
        $entity = $this->find()
            ->where(['role_id' => $role, 'resource_id' => $resource])
            ->first();

        if ($entity === null) {
            $entity = $this->newEntity([
                'role_id' => $role,
                'resource_id' => $resource,
                'allowed' => $value,
            ]);
        } else {
            $entity = $this->patchEntity($entity, ['allowed' => $value]);
        }

        return $this->save($entity) !== false;
    }

    /**
     * Denies a role access to a resource.
     *
     * @param string|int $role Role id
     * @param string|int $resource Resource id
     * @return bool
     */
    public function deny(int|string $role, int|string $resource): bool
    {
        return $this->allow($role, $resource, 0);
    }

    /**
     * Removes an explicit permission record, causing the role to
     * fall back to inheriting from its parent role.
     * Returns true if the record was deleted or did not exist.
     *
     * @param string|int $role Role id
     * @param string|int $resource Resource id
     * @return bool
     */
    public function inherit(int|string $role, int|string $resource): bool
    {
        $entity = $this->find()
            ->where(['role_id' => $role, 'resource_id' => $resource])
            ->first();

        if ($entity === null) {
            return true;
        }

        return $this->delete($entity);
    }

    /**
     * Checks if a role can access a resource path.
     *
     * @param string|int $role Role id
     * @param string $resource Resource path (e.g. 'Site/System/Dashboard/index')
     * @return bool
     */
    public function check(int|string $role, int|string $resource): bool
    {
        $perms = $this->getPermissions($role);

        return isset($perms[$resource]) ? $perms[$resource]['permissions']['allowed'] : true;
    }

    /**
     * Builds the full permission map for a role, taking into account
     * the role hierarchy (parents) and resource hierarchy (parent nodes).
     *
     * Each entry contains:
     *   'id'          => resource id
     *   'alias'       => resource alias
     *   'label'       => resource human-readable label
     *   'permissions' => [
     *       'allowed'   => bool,
     *       'inherited' => bool  // true if the value came from a parent role or resource
     *   ]
     *
     * Result is cached per role_id.
     *
     * @param string|int $role Role id
     * @return array<string, array{id: int, alias: string, permissions: array{allowed: bool, inherited: bool}}>
     */
    public function getPermissions(int|string $role): array
    {
        $permissions = function () use ($role): array {
            $paths = [];
            $perms = [];

            $resources = $this->Resources->find()
                ->orderByAsc('lft')
                ->enableHydration(false)
                ->all()
                ->toList();

            $path = $this->Roles->find('path', for: $role)
                ->contain('Resources')
                ->formatResults(function (CollectionInterface $results) {
                    return $results->map(function ($row) {
                        if (!empty($row['resources'])) {
                            $row['resources'] = (new Collection($row['resources']))
                                ->indexBy('id')
                                ->toArray();
                        }

                        return $row;
                    });
                })
                ->enableHydration(false)
                ->toArray();

            // array_reverse so index 0 = current role, 1 = parent, 2 = grandparent, ...
            $roles = array_reverse($path);

            foreach ($resources as $resource) {
                // Build the full path string for this resource node
                if ($resource['parent_id'] && isset($paths[$resource['parent_id']])) {
                    $paths[$resource['id']] = $paths[$resource['parent_id']] . '/' . $resource['alias'];
                } else {
                    $paths[$resource['id']] = $resource['alias'];
                }

                $allowed = null;
                $inherited = false;

                // Walk the role chain (current role first, then parents)
                foreach ($roles as $i => $r) {
                    if (isset($r['resources'][$resource['id']])) {
                        $inherited = $i !== 0;
                        $allowed = $r['resources'][$resource['id']]['_joinData']['allowed'];
                        break;
                    }
                }

                // No explicit record anywhere in the role chain — inherit from parent resource
                if ($allowed === null) {
                    $inherited = true;
                    $allowed = isset($resource['parent_id'])
                        ? $perms[$paths[$resource['parent_id']]]['permissions']['allowed']
                        : false;
                }

                $perms[$paths[$resource['id']]] = [
                    'id' => $resource['id'],
                    'alias' => $resource['alias'],
                    'label' => $resource['label'] ?? '',
                    'permissions' => [
                        'allowed' => $allowed,
                        'inherited' => $inherited,
                    ],
                ];
            }

            return $perms;
        };

        /** @var array<string, array{id: int, alias: string, permissions: array{allowed: bool, inherited: bool}}> */
        return Cache::remember((string)$role, $permissions, 'permissions');
    }
}
