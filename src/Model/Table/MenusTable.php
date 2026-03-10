<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @property \App\Model\Table\MenuLinksTable&\Cake\ORM\Association\HasMany $MenuLinks
 * @method \App\Model\Entity\Menu newEmptyEntity()
 * @method \App\Model\Entity\Menu newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Menu> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Menu get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Menu findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Menu patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Menu> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Menu|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Menu saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Menu>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Menu> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Menu>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Menu> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Search\Model\Behavior\SearchBehavior
 * @extends \Cake\ORM\Table<array{Search: \Search\Model\Behavior\SearchBehavior}>
 */
class MenusTable extends Table
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('menus');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('MenuLinks', [
            'foreignKey' => 'menu_id',
            'sort' => [
                'lft' => 'ASC',
            ],
        ]);

        $this->addBehavior('Search.Search');

        $this->setFilters();
    }

    /**
     * Configures search filters for the Menus table.
     *
     * @return void
     */
    public function setFilters(): void
    {
        /** @var \Search\Model\Behavior\SearchBehavior $search */
        $search = $this->getBehavior('Search');
        $searchManager = $search->searchManager();

        $searchManager
            ->useCollection('backend')
            ->value('prefix', ['filterEmpty' => true]);
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->boolean('prefix')
            ->allowEmptyString('prefix');

        return $validator;
    }

    /**
     * Callback to set the tree scope for the associated MenuLinks before a Menu entity is deleted.
     *
     * This ensures that tree operations on the MenuLinks are scoped to the specific menu being deleted.
     * If the Tree behavior is already attached to MenuLinks, its configuration is updated.
     * Otherwise, the Tree behavior is attached with the appropriate scope.
     *
     * @param \Cake\Event\EventInterface $event The beforeDelete event.
     * @param \App\Model\Entity\Menu $entity The Menu entity being deleted.
     * @param \ArrayObject $options Array of options passed to the delete operation.
     * @return void
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        // Set tree scope for Links association
        $settings = [
            'scope' => [$this->MenuLinks->getAlias() . '.menu_id' => $entity->id],
        ];
        if ($this->MenuLinks->hasBehavior('Tree')) {
            $this->MenuLinks->behaviors()->get('Tree')->setConfig($settings);
        } else {
            $this->MenuLinks->addBehavior('Tree', $settings);
        }
    }
}
