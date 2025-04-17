<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Plugins Model
 *
 * @property \App\Model\Table\MetaTable&\Cake\ORM\Association\HasMany $Meta
 *
 * @method \App\Model\Entity\Plugin newEmptyEntity()
 * @method \App\Model\Entity\Plugin newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Plugin[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Plugin get($primaryKey, $options = [])
 * @method \App\Model\Entity\Plugin findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Plugin patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Plugin[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Plugin|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Plugin saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Plugin[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Plugin[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Plugin[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Plugin[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class PluginsTable extends Table
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

        $this->setTable('plugins');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('Meta', [
            'foreignKey' => 'plugin_id'
        ]);
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
                ->scalar('alias')
                ->maxLength('alias', 50)
                ->requirePresence('alias', 'create')
                ->notEmptyString('alias')
                ->add('alias', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
                ->scalar('description')
                ->maxLength('description', 500)
                ->allowEmptyString('description');

        $validator
                ->scalar('parent_plugin')
                ->maxLength('parent_plugin', 255)
                ->allowEmptyString('parent_plugin');

        $validator
                ->boolean('enabled')
                ->allowEmptyString('enabled');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    #[\Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['alias']));

        return $rules;
    }
}
