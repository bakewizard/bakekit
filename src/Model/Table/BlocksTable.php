<?php

declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Core\App;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\TypeFactory;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior\Translate\ShadowTableStrategy;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use ReflectionClass;

TypeFactory::map('textandjson', 'App\Database\Type\TextAndJsonType');

/**
 * Blocks Model
 *
 * @property \App\Model\Table\RegionsTable|\Cake\ORM\Association\BelongsTo $Regions
 *
 * @method \App\Model\Entity\Block get($primaryKey, $options = [])
 * @method \App\Model\Entity\Block newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Block[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Block|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Block saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Block patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Block[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Block findOrCreate($search, callable $callback = null, $options = [])
 */
class BlocksTable extends Table
{

    #[\Override]
    public function getSchema(): TableSchemaInterface
    {
        $schema = parent::getSchema();
        $schema->setColumnType('params', 'textandjson');

        return $schema;
    }

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

        $this->setTable('blocks');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->belongsTo('Regions', [
            'foreignKey' => 'region_id',
            'joinType' => 'INNER'
        ]);

        $this->addBehavior('ADmad/Sequence.Sequence', [
            'scope' => ['region_id'],
        ]);

        $this->addBehavior('Translate', [
            'strategyClass' => ShadowTableStrategy::class,
            'fields' => ['title', 'params'],
            'translationTable' => 'BlocksI18n'
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
                ->scalar('alias')
                ->maxLength('alias', 255)
                ->requirePresence('alias', 'create')
                ->notEmptyString('alias')
                ->add('alias', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
                ->scalar('title')
                ->maxLength('title', 255)
                ->allowEmptyString('title');

        $validator
                ->scalar('description')
                ->maxLength('description', 500)
                ->allowEmptyString('description');

        $validator
                ->scalar('cell')
                ->maxLength('cell', 50)
                ->allowEmptyString('cell')
                ->add('cell', 'custom', [
                    'rule' => function ($cell, $context) {
                        $parts = explode('::', $cell);
                        list($pluginAndCell, $action) = count($parts) === 2 ? [$parts[0], $parts[1]] : [$parts[0], 'display'];
                        $className = App::classname($pluginAndCell, 'View/Cell', 'Cell');
                        if ($className) {
                            $class = new ReflectionClass($className);
                            if ($class->hasMethod($action) && $class->getMethod($action)->isPublic()) {
                                return true;
                            }
                        }
                        return false;
                    },
                    'message' => __('Cell doesn\'t exist')
        ]);

        $validator
                ->scalar('template')
                ->maxLength('template', 50)
                ->allowEmptyString('template');

        $validator
                ->allowEmptyString('params');

        $validator
                ->nonNegativeInteger('position')
                ->allowEmptyString('position');

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
        $rules->add($rules->existsIn(['region_id'], 'Regions'));

        return $rules;
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        foreach ($data as $key => $value) {
            $nullable = Hash::get((array) $this->getSchema()->getColumn($key), 'null');
            if ($nullable !== true) {
                continue;
            }
            if (is_string($value) && $value === '') {
                $data[$key] = null;
            }
        }

        $event->setResult($data);
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity)
    {
        if ($entity->isNew() || $entity->isDirty('cell')) {
            $configClass = App::classname($entity->cellFullName . 'CellConfig', 'Form/Cell', 'Form');
            if ($configClass) {
                $config = new $configClass;
                $fields = $config->getSchema()->fields();
                $validator = $config->getValidator();
                $data = [];
                foreach ($fields as $fieldName) {
                    if ($validator->field($fieldName)->isPresenceRequired()) {
                        $fieldAttrs = $config->getSchema()->field($fieldName);
                        $data[$fieldName] = $fieldAttrs['default'];
                    }
                }
                $entity->params = empty($data) ? null : $data;
            } else {
                $entity->params = null;
            }
        }
    }
}
