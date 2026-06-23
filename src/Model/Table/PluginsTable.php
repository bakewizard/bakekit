<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * Plugins Model
 *
 * @property \App\Model\Table\MetaTable&\Cake\ORM\Association\HasMany $Meta
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Plugin>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Plugin> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Plugin>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Plugin> deleteManyOrFail(iterable $entities, array $options = [])
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\Plugin>
 */
class PluginsTable extends Table
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('plugins');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('Meta', [
            'foreignKey' => 'plugin_id',
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
                ->notEmptyString('enabled');

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
        $rules->add($rules->isUnique(['alias']));

        return $rules;
    }

    /**
     * Gets a list of loaded plugins.
     *
     * @param bool $includeSystem Include System plugin if true.
     * @param bool $includeSubPlugins Include sub plugins if true.
     * @return array<string> List of plugin names.
     */
    public function getActivePlugins(bool $includeSystem = false, bool $includeSubPlugins = false): array
    {
        $query = $this->find()->select('name')->where(['enabled' => true])->orderByAsc('name');

        if (!$includeSubPlugins) {
            $query->where(['parent_plugin is' => null]);
        }

        $plugins = $query->all()->extract('name')->toList();

        if ($includeSystem) {
            array_unshift($plugins, 'System');
        }

        return $plugins;
    }
}
