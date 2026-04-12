<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * UserImages Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \App\Model\Entity\UserImage newEmptyEntity()
 * @method \App\Model\Entity\UserImage newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\UserImage> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\UserImage get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\UserImage findOrCreate(\Cake\ORM\Query\SelectQuery|callable|array $search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\UserImage patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\UserImage> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\UserImage|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\UserImage saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserImage>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserImage> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserImage>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\UserImage> deleteManyOrFail(iterable $entities, array $options = [])
 */
class UserImagesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('user_images');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('user_id')
            ->notEmptyString('user_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('path')
            ->maxLength('path', 100)
            ->requirePresence('path', 'create')
            ->notEmptyString('path');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
