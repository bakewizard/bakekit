<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use Override;

/**
 * Settings Model
 *
 * @method \App\Model\Entity\Setting get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Setting newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Setting> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Setting|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Setting saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Setting patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Setting> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Setting findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior}>
 * @method \App\Model\Entity\Setting newEmptyEntity()
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Setting>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Setting> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Setting>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Setting> deleteManyOrFail(iterable $entities, array $options = [])
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
                ->allowEmptyString('id', null, 'create')
                ->add('namespace', 'valid-namespace', ['rule' => ['custom', '@[a-z0-9\\\.]+@']]);

        $validator
                ->scalar('namespace')
                ->maxLength('namespace', 255)
                ->allowEmptyString('namespace');

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
