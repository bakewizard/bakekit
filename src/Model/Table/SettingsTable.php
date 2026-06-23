<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use Override;

/**
 * Settings Model
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}, \App\Model\Entity\Setting>
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Setting>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Setting> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Setting>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Setting> deleteManyOrFail(iterable $entities, array $options = [])
 */
class SettingsTable extends Table
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('settings');
        $this->setDisplayField('value');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
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
            ->scalar('namespace')
            ->maxLength('namespace', 255)
            ->requirePresence('namespace', 'create')
            ->notEmptyString('namespace')
            ->add('namespace', 'valid-namespace', ['rule' => ['custom', '/^[A-Z][a-zA-Z0-9]+$/']]);

        $validator
            ->scalar('path')
            ->maxLength('path', 255)
            ->requirePresence('path', 'create')
            ->notEmptyString('path');

        $validator
            ->scalar('value')
            ->maxLength('value', 255)
            ->allowEmptyString('value');

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
        $rules->add($rules->isUnique(['namespace', 'path']));

        return $rules;
    }

    /**
     * beforeMarshal callback.
     *
     * Used to convert empty strings to null for nullable fields.
     *
     * @param \Cake\Event\EventInterface $event The beforeMarshal event.
     * @param \ArrayObject $data The data being marshaled.
     * @param \ArrayObject $options The options for marshalling.
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        foreach ($data as $key => $value) {
            $nullable = Hash::get((array)$this->getSchema()->getColumn($key), 'null');
            if ($nullable !== true) {
                continue;
            }
            if (is_string($value) && $value === '') {
                $data[$key] = null;
            }
        }

        $event->setResult($data);
    }
}
