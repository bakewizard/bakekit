<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Menus Model
 *
 * @property \App\Model\Table\MenuLinksTable&\Cake\ORM\Association\HasMany $MenuLinks
 *
 * @method \App\Model\Entity\Menu get($primaryKey, $options = [])
 * @method \App\Model\Entity\Menu newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Menu[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Menu|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Menu saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Menu patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Menu[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Menu findOrCreate($search, callable $callback = null, $options = [])
 */
class MenusTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    #[\Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('menus');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('MenuLinks', [
            'foreignKey' => 'menu_id',
            'order' => [
                'lft' => 'ASC'
            ]
        ]);

        $this->addBehavior('Search.Search');
    }

    /**
     * @return \Search\Manager
     */
    public function searchManager()
    {
        $searchManager = $this->behaviors()->Search->searchManager();
        $searchManager
                ->useCollection('backend')
                ->value('prefix', ['filterEmpty' => true])
                ->value('enabled', ['filterEmpty' => true]);

        return $searchManager;
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    #[\Override]
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

        $validator
                ->boolean('enabled')
                ->allowEmptyString('enabled');

        return $validator;
    }

    /**
     * beforeDelete callback
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, $options)
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
