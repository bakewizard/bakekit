<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use App\Lib\AbstractUploadHandler;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;
use Override;
use const UPLOAD_ERR_NO_FILE;

/**
 * Image behavior
 */
class UploadBehavior extends Behavior
{
    private ?AbstractUploadHandler $uploadHandler = null;
    private string $tableAlias = 'Files';

    /**
     * @inheritDoc
     */
    protected array $_defaultConfig = [
        'maxFiles' => 10,
        'allowedFiles' => ['image/jpeg', 'image/png', 'image/gif'],
        'multiple' => false,
        'modelPath' => null,
        'dirDepth' => 2,
        'uploadHandler' => [
            'class' => '\\App\\Lib\\DefaultUploadHandler',
        ],
    ];

    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        [$plugin, $table] = pluginSplit(strtolower($this->_table->getRegistryAlias()));
        $modelPath = '/' . ($plugin ?? 'main') . '/' . $table;

        if (!isset($config['modelPath'])) {
            $this->setConfig('modelPath', $modelPath);
        } else {
            $this->setConfig('modelPath', '/' . $config['modelPath']);
        }

        $this->loadUploadHandler();

        $singularName = Inflector::singularize($this->_table->getAlias());

        $this->tableAlias = $singularName . 'Files';

        $foreignKey = strtolower($singularName) . '_id';

        $filesTable = FactoryLocator::get('Table')->get($this->tableAlias, [
            'table' => Inflector::singularize($this->_table->getTable()) . '_' . ($config['tablePostfix'] ?? 'files'),
        ]);

        if ($this->_config['multiple']) {
            // @phpstan-ignore-next-line
            $this->_table->hasMany($this->tableAlias, [
                'targetTable' => $filesTable,
                'saveStrategy' => 'replace',
                'foreignKey' => $foreignKey,
                'propertyName' => 'files',
                'sort' => 'sort_order asc',
            ]);
        } else {
            // @phpstan-ignore-next-line
            $this->_table->hasMany($this->tableAlias, [
                'targetTable' => $filesTable,
                'saveStrategy' => 'replace',
                'foreignKey' => $foreignKey,
                'propertyName' => 'files',
            ]);
        }
    }

    /**
     * Modifies the find query to contain associated files.
     *
     * @template TSubject of \Cake\Datasource\EntityInterface
     * @param \Cake\Event\EventInterface<TSubject> $event The beforeFind event.
     * @param \Cake\ORM\Query\SelectQuery<TSubject> $query The query object.
     * @param \ArrayObject<string, mixed> $options The options passed to the find method.
     * @param bool $primary Whether this is the primary query.
     * @return void
     */
    public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void
    {
        $query
                ->select('id')
                ->enableAutoFields(true)
                ->contain($this->tableAlias);
    }

    /**
     * Processes uploaded files before marshalling data into an entity.
     *
     * Extracts file information from the 'uploads' data and prepares the 'files'
     * data structure for entity creation or patching.
     *
     * @template TSubject of \Cake\Datasource\EntityInterface
     * @template TKey of array-key
     * @template TValue
     * @param \Cake\Event\EventInterface<TSubject> $event The beforeMarshal event.
     * @param \ArrayObject<TKey, TValue> $data The data being marshalled.
     * @param \ArrayObject<TKey, TValue> $options The options passed to the marshaller.
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (!isset($data['uploads'])) {
            return;
        }

        $uploads = $data['uploads'];

        if ($this->_config['multiple']) {
            if (!isset($data['files'])) {
                $data['files'] = [];
            }
            foreach ($data['files'] as $i => $file) {
                if (!isset($file['id'])) {
                    $upload = array_shift($uploads);

                    if ($upload->getError() == UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    $data['files'][$i]['name'] = $upload->getClientFilename();
                    $data['files'][$i]['path'] = $this->getConfig('modelPath') . $this->generatePath();
                    $data['files'][$i]['format'] = $this->uploadHandler->getConfig('format');
                    $data['files'][$i]['tmp_name'] = $upload->getStream()->getMetadata('uri');
                }
            }
        } else {
            if ($uploads[0]->getSize() > 0) {
                $data['files'][0]['name'] = $uploads[0]->getClientFilename();
                $data['files'][0]['path'] = $this->getConfig('modelPath') . $this->generatePath();
                $data['files'][0]['format'] = $this->uploadHandler->getConfig('format');
                $data['files'][0]['tmp_name'] = $uploads[0]->getStream()->getMetadata('uri');
            }
        }
    }

    /**
     * Handles file uploads after the entity is saved.
     *
     * If the 'files' property of the entity has been modified, it processes
     * the uploaded files using the configured upload handler. It also handles
     * removal of previously associated files if the entity is being updated.
     *
     * @template TSubject of \Cake\Datasource\EntityInterface
     * @param \Cake\Event\EventInterface<TSubject> $event The afterSave event.
     * @param \Cake\Datasource\EntityInterface $entity The saved entity.
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity): void
    {
        if ($entity->isDirty('files')) {
            $files = $entity->get('files');

            if (!$entity->isNew()) {
                $filesToRemove = array_udiff($entity->getOriginal('files'), $files, fn($a, $b) => $a->id <=> $b->id);
                if (!empty($filesToRemove)) {
                    $this->uploadHandler->remove($filesToRemove);
                }
            }

            $this->uploadHandler->handle($files);
        }
    }

    /**
     * Handles the removal of associated files after the entity is deleted.
     *
     * @template TSubject of \Cake\Datasource\EntityInterface
     * @param \Cake\Event\EventInterface<TSubject> $event The afterDelete event.
     * @param \Cake\Datasource\EntityInterface $entity The deleted entity.
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity): void
    {
        $this->uploadHandler->remove($entity->get('files'));
    }

    /**
     * Removes loaded files
     *
     * @param array<object> $files An array of objects to remove (each expected to have an 'id' property).
     * @return void
     */
    public function remove(array $files): void
    {
        $fileIds = array_map(fn($file) => $file->id, $files);
        $this->_table->{$this->tableAlias}->deleteAll(['id IN' => $fileIds]);

        $this->uploadHandler->remove($files);
    }

    /**
     * Returns upload handler
     *
     * @return \App\Lib\AbstractUploadHandler
     */
    public function getUploadHandler(): AbstractUploadHandler
    {
        return $this->uploadHandler;
    }

    /**
     * Loads upload handler
     *
     * @return void
     */
    private function loadUploadHandler(): void
    {
        $uploadHandlerClass = $this->_config['uploadHandler']['class'];
        unset($this->_config['uploadHandler']['class']);
        $config = $this->_config['uploadHandler'];

        $uploadHandler = new $uploadHandlerClass($config);

        if ($uploadHandler instanceof AbstractUploadHandler) {
            $this->uploadHandler = $uploadHandler;
        }
    }

    /**
     * Generates random path
     *
     * @return string
     */
    private function generatePath(): string
    {
        $sublevels = $this->getConfig('dirDepth');
        $chunkLength = 2;
        if ($sublevels == 0) {
            return '';
        }
        $bytes = intval(ceil($chunkLength * $sublevels / 2));
        $hash = bin2hex(random_bytes($bytes));
        $path = '/' . implode('/', str_split(substr($hash, 0, $sublevels * $chunkLength), $chunkLength));

        return $path;
    }
}
