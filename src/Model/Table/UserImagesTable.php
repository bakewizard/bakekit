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
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\UserImage>|false saveMany(iterable<\App\Model\Entity\UserImage> $entities, array<string, mixed> $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\UserImage> saveManyOrFail(iterable<\App\Model\Entity\UserImage> $entities, array<string, mixed> $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\UserImage>|false deleteMany(iterable<\App\Model\Entity\UserImage> $entities, array<string, mixed> $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\UserImage> deleteManyOrFail(iterable<\App\Model\Entity\UserImage> $entities, array<string, mixed> $options = [])
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\UserImage>
 * @method \App\Model\Entity\UserImage patchEntity(\App\Model\Entity\UserImage $entity, array<mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\UserImage> patchEntities(iterable<\App\Model\Entity\UserImage> $entities, array<mixed> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\UserImage|false save(\App\Model\Entity\UserImage $entity, array<string, mixed> $options = [])
 * @method \App\Model\Entity\UserImage saveOrFail(\App\Model\Entity\UserImage $entity, array<string, mixed> $options = [])
 * @method bool delete(\App\Model\Entity\UserImage $entity, array<string, mixed> $options = [])
 * @method bool deleteOrFail(\App\Model\Entity\UserImage $entity, array<string, mixed> $options = [])
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
