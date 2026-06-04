<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Block;
use ArrayObject;
use Cake\Core\App;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Event\EventInterface;
use Cake\Form\Form;
use Cake\ORM\Behavior\Translate\ShadowTableStrategy;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use LogicException;
use Override;
use ReflectionClass;

/**
 * Blocks Model
 *
 * @property \App\Model\Table\RegionsTable&\Cake\ORM\Association\BelongsTo $Regions
 * @property \Cake\ORM\Table&\Cake\ORM\Association\HasMany $BlocksI18n
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Block>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Block> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Block>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\App\Model\Entity\Block> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \ADmad\Sequence\Model\Behavior\SequenceBehavior
 * @mixin \Cake\ORM\Behavior\TranslateBehavior
 * @extends \Cake\ORM\Table<array{Sequence: \ADmad\Sequence\Model\Behavior\SequenceBehavior, Translate: \Cake\ORM\Behavior\TranslateBehavior}, \App\Model\Entity\Block>
 */
class BlocksTable extends Table
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function getSchema(): TableSchemaInterface
    {
        return parent::getSchema()->setColumnType('params', 'textandjson');
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('blocks');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->belongsTo('Regions', [
            'foreignKey' => 'region_id',
            'joinType' => 'INNER',
        ]);

        $this->addBehavior('ADmad/Sequence.Sequence', [
            'scope' => ['region_id'],
        ]);

        $this->addBehavior('Translate', [
            'strategyClass' => ShadowTableStrategy::class,
            'fields' => ['title', 'params'],
            'translationTable' => 'BlocksI18n',
        ]);
    }

    /**
     * @inheritDoc
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
                ->maxLength('description', 500)
                ->allowEmptyString('description');

        $validator
                ->scalar('cell')
                ->maxLength('cell', 50)
                ->allowEmptyString('cell')
                ->add('cell', 'custom', [
                    'rule' => function ($cell, $context) {
                        $parts = explode('::', $cell);
                        [$pluginAndCell, $action] = count($parts) === 2 ? [$parts[0], $parts[1]] : [$parts[0], 'display'];
                        $className = App::classname($pluginAndCell, 'View/Cell', 'Cell');
                        if ($className) {
                            $class = new ReflectionClass($className);
                            if ($class->hasMethod($action) && $class->getMethod($action)->isPublic()) {
                                return true;
                            }
                        }

                        return false;
                    },
                    'message' => __('Cell doesn\'t exist'),
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
                ->notEmptyString('enabled');

        return $validator;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['region_id'], 'Regions'));

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

    /**
     * beforeSave callback.
     *
     * Initializes the 'params' field with default values from the associated cell's config form, if it exists.
     * This is done when a new block is created or when the 'cell' field is modified.
     *
     * @param \Cake\Event\EventInterface $event The beforeSave event.
     * @param \App\Model\Entity\Block $entity The entity being saved.
     * @return void
     */
    public function beforeSave(EventInterface $event, Block $entity): void
    {
        if ($entity->isNew() || $entity->isDirty('cell')) {
            $configClass = App::classname($entity->cell_full_name . 'CellConfig', 'Form/Cell', 'Form');
            if ($configClass) {
                $config = new $configClass();
                if (!$config instanceof Form) {
                    throw new LogicException(sprintf('Expected an instance of %s, got %s', Form::class, get_class($config)));
                }
                $fields = $config->getSchema()->fields();
                $validator = $config->getValidator();
                $data = [];
                foreach ($fields as $fieldName) {
                    if ($validator->field($fieldName)->isPresenceRequired()) {
                        $fieldAttrs = $config->getSchema()->field($fieldName);
                        if (is_array($fieldAttrs) && array_key_exists('default', $fieldAttrs)) {
                            $data[$fieldName] = $fieldAttrs['default'];
                        }
                    }
                }
                // @phpstan-ignore-next-line
                $entity->params = empty($data) ? null : $data;
            } else {
                $entity->params = null;
            }
        }
    }
}
