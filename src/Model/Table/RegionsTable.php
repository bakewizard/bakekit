<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * Regions Model
 *
 * @property \App\Model\Table\BlocksTable&\Cake\ORM\Association\HasMany $Blocks
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Region>|false saveMany(iterable<\App\Model\Entity\Region> $entities, array<string, mixed> $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Region> saveManyOrFail(iterable<\App\Model\Entity\Region> $entities, array<string, mixed> $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Region>|false deleteMany(iterable<\App\Model\Entity\Region> $entities, array<string, mixed> $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Region> deleteManyOrFail(iterable<\App\Model\Entity\Region> $entities, array<string, mixed> $options = [])
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\Region>
 * @method \App\Model\Entity\Region patchEntity(\App\Model\Entity\Region $entity, array<mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\Region> patchEntities(iterable<\App\Model\Entity\Region> $entities, array<mixed> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Region|false save(\App\Model\Entity\Region $entity, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Region saveOrFail(\App\Model\Entity\Region $entity, array<string, mixed> $options = [])
 * @method bool delete(\App\Model\Entity\Region $entity, array<string, mixed> $options = [])
 * @method bool deleteOrFail(\App\Model\Entity\Region $entity, array<string, mixed> $options = [])
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
            ->notEmptyString('alias');

        $validator
            ->scalar('theme')
            ->maxLength('theme', 100)
            ->requirePresence('theme', 'create')
            ->notEmptyString('theme');

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
        $rules->add($rules->isUnique(['alias', 'theme']));

        return $rules;
    }

    /**
     * Returns regions for the active theme.
     *
     * @param string $theme Active theme name.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByTheme(string $theme): SelectQuery
    {
        return $this->find()->where(['theme' => $theme]);
    }
}
