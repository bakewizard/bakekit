<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * Roles Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\HasMany $Users
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Role>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Role> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Role>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Role> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @mixin \Cake\ORM\Behavior\TreeBehavior
 * @property \App\Model\Table\RolesTable&\Cake\ORM\Association\BelongsTo $ParentRoles
 * @property \App\Model\Table\RolesTable&\Cake\ORM\Association\HasMany $ChildRoles
 * @property \App\Model\Table\PermissionsTable&\Cake\ORM\Association\HasMany $Permissions
 * @property \App\Model\Table\ResourcesTable&\Cake\ORM\Association\BelongsToMany $Resources
 * @extends \Cake\ORM\Table<array{Timestamp: \Cake\ORM\Behavior\TimestampBehavior, Tree: \Cake\ORM\Behavior\TreeBehavior}, \App\Model\Entity\Role>
 */
class RolesTable extends Table
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('roles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->belongsTo('ParentRoles', [
            'className' => 'Roles',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('ChildRoles', [
            'className' => 'Roles',
            'foreignKey' => 'parent_id',
        ]);
        $this->hasMany('Users', [
            'foreignKey' => 'role_id',
        ]);
        $this->hasMany('Permissions', [
            'foreignKey' => 'resource_id',
        ]);
        $this->belongsToMany('Resources', [
            'through' => 'Permissions',
        ]);

        $this->addBehavior('Tree');
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
                ->scalar('name')
                ->maxLength('name', 100)
                ->requirePresence('name', 'create')
                ->notEmptyString('name');

        $validator
                ->scalar('alias')
                ->maxLength('alias', 150)
                ->requirePresence('alias', 'create')
                ->notEmptyString('alias');

        $validator
                ->add('parent_id', 'custom', [
                    'rule' => function ($value, $context) {
                        return !empty($value);
                    },
                    'message' => __('Choose parent category!'),
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
        $rules->add($rules->isUnique(['alias']), ['errorField' => 'alias']);
        $rules->add($rules->existsIn(['parent_id'], 'ParentRoles'), ['errorField' => 'parent_id']);

        return $rules;
    }
}
