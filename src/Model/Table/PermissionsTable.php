<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Cache\Cache;
use Cake\Collection\CollectionInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * Permissions Model
 *
 * @property \App\Model\Table\RolesTable&\Cake\ORM\Association\BelongsTo $Roles
 * @property \App\Model\Table\ResourcesTable&\Cake\ORM\Association\BelongsTo $Resources
 * @method \App\Model\Entity\Permission newEmptyEntity()
 * @method \App\Model\Entity\Permission newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Permission[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Permission get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Permission findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Permission patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Permission[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Permission|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Permission saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Permission[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Permission>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Permission[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Permission> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Permission[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Permission>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Permission[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Permission> deleteManyOrFail(iterable $entities, array $options = [])
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
     * Allows a role to access a resource
     *
     * @param int $role
     * @param int $resource
     * @param int $value
     * @return bool
     */
    public function allow(int $role, int $resource, int $value = 1): bool
    {
        $entity = $this->newEntity([
            'role_id' => $role,
            'resource_id' => $resource,
            'allowed' => $value,
        ]);

        return $this->save($entity) !== false;
    }

    /**
     * Denies a role to access a resource
     *
     * @param int $role
     * @param int $resource
     * @return bool
     */
    public function deny(int $role, int $resource): bool
    {
        return $this->allow($role, $resource, 0);
    }

    /**
     * Inherits role permission
     *
     * @param int $role
     * @param int $resource
     * @return bool
     */
    public function inherit(int $role, int $resource): bool
    {
        $entity = $this->find()->where(['role_id' => $role, 'resource_id' => $resource])->first();

        return $this->delete($entity) !== false;
    }

    /**
     * Checks if a role can access a resource
     *
     * @param int $role
     * @param int $resource
     * @return bool
     */
    public function check(int $role, int $resource): bool
    {
        $perms = $this->getPermissions($role);

        return isset($perms[$resource]) ? $perms[$resource]['permissions'][0] : true;
    }

    /**
     * Receives role permissions
     *
     * @param int $role
     * @return mixed
     */
    public function getPermissions(int $role): mixed
    {
        $permissions = function () use ($role) {

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
                                $row['resources'] = collection($row['resources'])->indexBy('id')->toArray();
                            }

                            return $row;
                        });
                    })
                    ->enableHydration(false)
                    ->toArray();

            $roles = array_reverse($path);

            foreach ($resources as $resource) {
                // Generate path
                if ($resource['parent_id'] && isset($paths[$resource['parent_id']])) {
                    $paths[$resource['id']] = $paths[$resource['parent_id']] . '/' . $resource['alias'];
                } else {
                    $paths[$resource['id']] = $resource['alias'];
                }

                $allowed = null;
                $inherited = false;
                $blocked = false;

                foreach ($roles as $i => $r) {
                    if (isset($r['resources'][$resource['id']])) {
                        $inherited = $i === 0 ? false : true;
                        $allowed = $r['resources'][$resource['id']]['_joinData']['allowed'];
                        $blocked = $i === 0 ? false : !$allowed;
                        break;
                    }
                }

                if (is_null($allowed)) {
                    $inherited = true;
                    $allowed = isset($resource['parent_id']) ? $perms[$paths[$resource['parent_id']]]['permissions'][0] : false;
                    $blocked = false;
                    if (isset($resource['parent_id'])) {
                        $parentPermissions = $perms[$paths[$resource['parent_id']]]['permissions'];
                        $blocked = $parentPermissions[1] && !$parentPermissions[0];
                    }
                }

                $perms[$paths[$resource['id']]] = [
                    'id' => $resource['id'],
                    'alias' => $resource['alias'],
                    'permissions' => [$allowed, $inherited, $blocked],
                ];
            }

            return $perms;
        };

        return Cache::remember((string)$role, $permissions, 'permissions');
    }
}
