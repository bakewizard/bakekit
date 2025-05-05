<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * Regions Model
 *
 * @property \App\Model\Table\BlocksTable&\Cake\ORM\Association\HasMany $Blocks
 * @method \App\Model\Entity\Region get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Region newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Region[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Region|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Region saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Region patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Region[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Region findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Region newEmptyEntity()
 * @method \App\Model\Entity\Region[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Region>|false saveMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Region[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Region> saveManyOrFail(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Region[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Region>|false deleteMany(iterable $entities, array $options = [])
 * @method \App\Model\Entity\Region[]|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Region> deleteManyOrFail(iterable $entities, array $options = [])
 */
class RegionsTable extends Table
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('regions');
        $this->setDisplayField('alias');
        $this->setPrimaryKey('id');

        $this->hasMany('Blocks', [
            'foreignKey' => 'region_id',
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
                ->scalar('alias')
                ->maxLength('alias', 100)
                ->requirePresence('alias', 'create')
                ->notEmptyString('alias')
                ->add('alias', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
                ->scalar('description')
                ->maxLength('description', 255)
                ->allowEmptyString('description');

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
}
