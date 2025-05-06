<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior\Translate\ShadowTableStrategy;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * @property \App\Model\Table\MenusTable&\Cake\ORM\Association\BelongsTo $Menus
 * @property \App\Model\Table\MenuLinksTable&\Cake\ORM\Association\BelongsTo $ParentMenuLinks
 * @property \App\Model\Table\MenuLinksTable&\Cake\ORM\Association\HasMany $ChildMenuLinks
 * @property \Cake\ORM\Table&\Cake\ORM\Association\HasMany $MenuLinksI18n
 * @method \App\Model\Entity\MenuLink newEmptyEntity()
 * @method \App\Model\Entity\MenuLink newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\MenuLink[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\MenuLink get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\MenuLink findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\MenuLink patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\MenuLink[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\MenuLink|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MenuLink saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\MenuLink[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MenuLink>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\MenuLink[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MenuLink> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\MenuLink[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MenuLink>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\MenuLink[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\MenuLink> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TreeBehavior
 * @mixin \Cake\ORM\Behavior\TranslateBehavior
 * @extends \Cake\ORM\Table<array{Translate: \Cake\ORM\Behavior\TranslateBehavior, Tree: \Cake\ORM\Behavior\TreeBehavior}>
 */
class MenuLinksTable extends Table
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('menu_links');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->belongsTo('Menus', [
            'foreignKey' => 'menu_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('ParentMenuLinks', [
            'className' => 'MenuLinks',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('ChildMenuLinks', [
            'className' => 'MenuLinks',
            'foreignKey' => 'parent_id',
        ]);

        $this->addBehavior('Tree');
        $this->addBehavior('Translate', [
            'strategyClass' => ShadowTableStrategy::class,
            'fields' => ['title'],
            'translationTable' => 'MenuLinksI18n',
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
            ->nonNegativeInteger('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('icon')
            ->maxLength('icon', 100)
            ->allowEmptyString('icon');

        $validator
            ->scalar('link')
            ->maxLength('link', 255)
            ->allowEmptyString('link');

        $validator
            ->scalar('target')
            ->maxLength('target', 10)
            ->allowEmptyString('target');

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
        $rules->add($rules->existsIn(['menu_id'], 'Menus'));
        $rules->add($rules->existsIn(['parent_id'], 'ParentMenuLinks'));

        return $rules;
    }

    /**
     * Allow to change Tree scope to a specific menu
     *
     * @param int $menuId menu id
     * @return void
     */
    public function setTreeScope(int $menuId): void
    {
        $settings = [
            'scope' => ['menu_id' => $menuId],
        ];
        if ($this->hasBehavior('Tree')) {
            $this->behaviors()
                ->get('Tree')
                ->setConfig($settings);
        } else {
            $this->addBehavior('Tree', $settings);
        }
    }

    /**
     * Calls TreeBehavior::recover when we are changing scope
     *
     * @param \Cake\Event\EventInterface $event
     * @param \App\Model\Entity\MenuLink $entity
     * @param array $options
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, array $options = []): void
    {
        if ($entity->isNew()) {
            return;
        }
        if ($entity->isDirty('menu_id')) {
            $this->setTreeScope($entity->menu_id);
            $this->recover();
            $this->setTreeScope($entity->getOriginal('menu_id'));
            $this->recover();
        }
    }
}
