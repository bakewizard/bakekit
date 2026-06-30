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
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Menu>|false saveMany(iterable<\App\Model\Entity\Menu> $entities, array<string, mixed> $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Menu> saveManyOrFail(iterable<\App\Model\Entity\Menu> $entities, array<string, mixed> $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Menu>|false deleteMany(iterable<\App\Model\Entity\Menu> $entities, array<string, mixed> $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Menu> deleteManyOrFail(iterable<\App\Model\Entity\Menu> $entities, array<string, mixed> $options = [])
 * @mixin \Search\Model\Behavior\SearchBehavior
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\Menu>
 * @method \App\Model\Entity\Menu patchEntity(\App\Model\Entity\Menu $entity, array<mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\Menu> patchEntities(iterable<\App\Model\Entity\Menu> $entities, array<mixed> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Menu|false save(\App\Model\Entity\Menu $entity, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Menu saveOrFail(\App\Model\Entity\Menu $entity, array<string, mixed> $options = [])
 * @method bool delete(\App\Model\Entity\Menu $entity, array<string, mixed> $options = [])
 * @method bool deleteOrFail(\App\Model\Entity\Menu $entity, array<string, mixed> $options = [])
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
        if ($entity->isSystem()) {
            $event->stopPropagation();
        }

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
