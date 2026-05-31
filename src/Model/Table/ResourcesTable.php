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
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Resource>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Resource> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Resource>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Resource> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TreeBehavior
 * @extends \Cake\ORM\Table<array{Tree: \Cake\ORM\Behavior\TreeBehavior}, \App\Model\Entity\Resource>
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

        $validator
            ->scalar('label')
            ->maxLength('label', 255)
            ->allowEmptyString('label');

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
     * @param int|null $parentId The parent id to use when creating.
     * @param string $label Optional human-readable label shown in the permissions UI.
     * @return \App\Model\Entity\Resource|null The created resource node or null on failure.
     */
    public function createNode(string $path, ?int $parentId = null, string $label = ''): ?Resource
    {
        $node = null;
        $segments = explode('/', $path);
        $lastIndex = count($segments) - 1;

        foreach ($segments as $i => $alias) {
            $entity = $this->newEntity([
                'parent_id' => $node->id ?? $parentId,
                'alias' => $alias,
                'label' => $i === $lastIndex ? $label : '',
            ]);

            $node = $this->save($entity);
            if (!$node) {
                return null;
            }
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
        return $this->find()
            ->where(['alias' => $alias, 'parent_id IS' => $parentId])
            ->first() ?: null;
    }

    /**
     * Saves plugin resource structure to the database using nested nodes.
     *
     * @param array<string, array<string, array<string, string>>> $resourceTree Structured plugin resource tree.
     *        Format: [plugin => [controller => [action => label]]]
     * @return void
     */
    public function addResources(array $resourceTree): void
    {
        $this->getConnection()->transactional(function () use ($resourceTree): void {

            $rootNode = $this->checkNode('Site', null) ?? $this->createNode('Site', null);

            if (!$rootNode instanceof Resource) {
                return;
            }

            foreach ($resourceTree as $plugin => $controllers) {
                // Skip plugins where no controller has any actions
                $controllers = array_filter($controllers, fn($actions) => !empty($actions));
                if (empty($controllers)) {
                    continue;
                }

                $pluginNode = $this->createNode($plugin, $rootNode->id);
                if (!$pluginNode) {
                    continue;
                }

                foreach ($controllers as $controller => $actions) {
                    $controllerNode = $this->createNode($controller, $pluginNode->id);
                    if (!$controllerNode) {
                        continue;
                    }

                    foreach ($actions as $action => $label) {
                        $this->createNode($action, $controllerNode->id, $label);
                    }
                }
            }
        });
    }

    /**
     * Deletes resources.
     *
     * @param string $alias Resource alias.
     * @return void
     */
    public function deleteResources(string $alias): void
    {
        $resources = $this->find()
            ->where(['alias is' => $alias, 'parent_id' => 1])
            ->first();

        if (!empty($resources)) {
            $this->delete($resources);
        }
    }
}
