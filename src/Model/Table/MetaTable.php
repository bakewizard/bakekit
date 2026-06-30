<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Behavior\Translate\ShadowTableStrategy;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Override;

/**
 * Meta Model
 *
 * @property \App\Model\Table\PluginsTable&\Cake\ORM\Association\BelongsTo $Plugins
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Metum>|false saveMany(iterable<\App\Model\Entity\Metum> $entities, array<string, mixed> $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Metum> saveManyOrFail(iterable<\App\Model\Entity\Metum> $entities, array<string, mixed> $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Metum>|false deleteMany(iterable<\App\Model\Entity\Metum> $entities, array<string, mixed> $options = [])
 * @method \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Metum> deleteManyOrFail(iterable<\App\Model\Entity\Metum> $entities, array<string, mixed> $options = [])
 * @property \Cake\ORM\Table&\Cake\ORM\Association\HasMany $MetaI18n
 * @mixin \Cake\ORM\Behavior\TranslateBehavior
 * @extends \Cake\ORM\Table<array{Translate: \Cake\ORM\Behavior\TranslateBehavior}, \App\Model\Entity\Metum>
 * @method \App\Model\Entity\Metum patchEntity(\App\Model\Entity\Metum $entity, array<mixed> $data, array<string, mixed> $options = [])
 * @method array<\App\Model\Entity\Metum> patchEntities(iterable<\App\Model\Entity\Metum> $entities, array<mixed> $data, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Metum|false save(\App\Model\Entity\Metum $entity, array<string, mixed> $options = [])
 * @method \App\Model\Entity\Metum saveOrFail(\App\Model\Entity\Metum $entity, array<string, mixed> $options = [])
 * @method bool delete(\App\Model\Entity\Metum $entity, array<string, mixed> $options = [])
 * @method bool deleteOrFail(\App\Model\Entity\Metum $entity, array<string, mixed> $options = [])
 */
class MetaTable extends Table
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('meta');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Plugins', [
            'foreignKey' => 'plugin_id',
            'joinType' => 'INNER',
        ]);

        $this->addBehavior('Translate', [
            'strategyClass' => ShadowTableStrategy::class,
            'fields' => ['title', 'description', 'seo_title', 'seo_description', 'seo_keywords'],
            'translationTable' => 'MetaI18n',
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
                ->scalar('title')
                ->maxLength('title', 255)
                ->allowEmptyString('title');

        $validator
                ->scalar('description')
                ->allowEmptyString('description');

        $validator
                ->scalar('seo_title')
                ->maxLength('seo_title', 160)
                ->allowEmptyString('seo_title');

        $validator
                ->scalar('seo_description')
                ->maxLength('seo_description', 280)
                ->allowEmptyString('seo_description');

        $validator
                ->scalar('seo_keywords')
                ->maxLength('seo_keywords', 100)
                ->allowEmptyString('seo_keywords');

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
        $rules->add($rules->existsIn(['plugin_id'], 'Plugins'));

        return $rules;
    }
}
