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
 * @method \App\Model\Entity\Plugin newEmptyEntity()
 * @method \App\Model\Entity\Plugin newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Plugin> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Plugin get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Plugin findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Plugin patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Plugin> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Plugin|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Plugin saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Plugin>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Plugin> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Plugin>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Plugin> deleteManyOrFail(iterable $entities, array $options = [])
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
