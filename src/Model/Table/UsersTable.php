<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * Users Model
 *
 * @property \App\Model\Table\RolesTable&\Cake\ORM\Association\BelongsTo $Roles
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\User>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\User> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\User>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\User> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @property \App\Model\Table\UserImagesTable&\Cake\ORM\Association\HasMany $UserFiles
 * @mixin \App\Model\Behavior\AttachmentBehavior
 * @extends \Cake\ORM\Table<array{Attachment: \App\Model\Behavior\AttachmentBehavior, Timestamp: \Cake\ORM\Behavior\TimestampBehavior}, \App\Model\Entity\User>
 */
class UsersTable extends Table
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('full_name');
        $this->setPrimaryKey('id');

        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('UserFiles', [
            'className' => 'UserImages',
            'foreignKey' => 'user_id',
            'saveStrategy' => 'replace',
            'propertyName' => 'files',
        ]);

        $this->addBehavior('Timestamp');
        $this->addBehavior('Attachment');
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
            ->scalar('first_name')
            ->maxLength('first_name', 255)
            ->requirePresence('first_name', 'create')
            ->notEmptyString('first_name');

        $validator
            ->scalar('last_name')
            ->maxLength('last_name', 255)
            ->requirePresence('last_name', 'create')
            ->notEmptyString('last_name');

        $validator
            ->scalar('alias')
            ->maxLength('alias', 150)
            ->requirePresence('alias', 'create')
            ->notEmptyString('alias');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('password')
            ->maxLength('password', 255)
            ->requirePresence('password', 'create')
            ->notEmptyString('password', null, 'create');

        $validator
            ->add('role_id', 'custom', [
                'rule' => function ($value, $context) {
                    return !($value == 1);
                },
                'message' => __('Only one root is allowed!'),
            ]);

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
        $rules->add($rules->isUnique(['email']));
        $rules->add($rules->isUnique(['alias']));
        $rules->add($rules->existsIn(['role_id'], 'Roles'));

        return $rules;
    }
}
