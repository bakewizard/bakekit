<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Resource;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @property \App\Model\Table\ResourcesTable&\Cake\ORM\Association\BelongsTo $ParentResources
 * @property \App\Model\Table\ResourcesTable&\Cake\ORM\Association\HasMany $ChildResources
 * @property \App\Model\Table\PermissionsTable&\Cake\ORM\Association\HasMany $Permissions
 * @property \App\Model\Table\RolesTable&\Cake\ORM\Association\BelongsToMany $Roles
 * @method \App\Model\Entity\Resource newEmptyEntity()
 * @method \App\Model\Entity\Resource newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Resource> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Resource get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Resource findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Resource patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Resource> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Resource|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Resource saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Resource>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Resource> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Resource>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Resource> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TreeBehavior
 * @extends \Cake\ORM\Table<array{Tree: \Cake\ORM\Behavior\TreeBehavior}>
 */
class ResourcesTable extends Table
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('resources');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('ParentResources', [
            'className' => 'Resources',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('ChildResources', [
            'className' => 'Resources',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('Permissions', [
            'foreignKey' => 'resource_id',
        ]);
        $this->belongsToMany('Roles', [
            'through' => 'Permissions',
        ]);

        $this->addBehavior('Tree');
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
                ->nonNegativeInteger('id')
                ->allowEmptyString('id', null, 'create');

        $validator
                ->scalar('alias')
                ->maxLength('alias', 255)
                ->requirePresence('alias', 'create')
                ->notEmptyString('alias');

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
        $rules->add($rules->existsIn(['parent_id'], 'ParentResources'), ['errorField' => 'parent_id']);

        return $rules;
    }

    /**
     * Creates a resource node.
     *
     * @param string $path The path to resource.
     * @param int $parentId The parent id to use when creating.
     * @return \App\Model\Entity\Resource
     */
    public function createNode(string $path, ?int $parentId = null): Resource
    {
        $node = null;
        $aliases = explode('/', $path);

        foreach ($aliases as $alias) {
            $parentId = !empty($node) ? $node->id : $parentId;
            $entity = $this->newEntity([
                'parent_id' => $parentId,
                'alias' => $alias,
            ]);
            $node = $this->save($entity);
        }

        return $node;
    }

    /**
     * Checks if node exists.
     *
     * @param string $alias Resource alias.
     * @param int $parentId Parent resource id.
     * @return \App\Model\Entity\Resource|null Node if exists or null.
     */
    public function checkNode(string $alias, ?int $parentId = null): ?Resource
    {
        $node = $this->find()->where(['alias' => $alias, 'parent_id is' => $parentId])->first();

        return !empty($node) ? $node : null;
    }
}
