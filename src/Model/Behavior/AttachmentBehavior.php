<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use App\Lib\AbstractFileHandler;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;
use Override;
use RuntimeException;
use const UPLOAD_ERR_NO_FILE;

/**
 * Attachment behavior
 */
class AttachmentBehavior extends Behavior
{
    private AbstractFileHandler $uploadHandler;
    private string $tableAlias = 'Files';

    /**
     * @inheritDoc
     */
    protected array $_defaultConfig = [
        'modelPath' => null,
        'dirDepth' => 0,
    ];

    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        [$plugin, $table] = pluginSplit(strtolower($this->_table->getRegistryAlias()));

        if (!isset($config['modelPath'])) {
            $this->setConfig('modelPath', '/' . ($plugin ?? 'system') . '/' . $table);
        } else {
            $this->setConfig('modelPath', '/' . $config['modelPath']);
        }

        $this->tableAlias = Inflector::singularize($this->_table->getAlias()) . 'Files';
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

        if (!isset($data['files'])) {
            $data['files'] = [];
        }

        foreach ($data['files'] as $i => $file) {
            if (!empty($file['id'])) {
                continue;
            }

            $upload = array_shift($uploads);

            if ($upload->getError() == UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $data['files'][$i]['name'] = $upload->getClientFilename();
            $data['files'][$i]['path'] = $this->getConfig('modelPath') . $this->generatePath();
            $data['files'][$i]['format'] = $this->uploadHandler->getConfig('format');
            $data['files'][$i]['tmp_name'] = $upload->getStream()->getMetadata('uri');
        }

        foreach ($uploads as $upload) {
            if ($upload->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $data['files'][] = [
                'name' => $upload->getClientFilename(),
                'path' => $this->getConfig('modelPath') . $this->generatePath(),
                'format' => $this->uploadHandler->getConfig('format'),
                'tmp_name' => $upload->getStream()->getMetadata('uri'),
            ];
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
     * @param array<\Cake\ORM\Entity> $files An array of objects to remove (each expected to have an 'id' property).
     * @return void
     */
    public function remove(array $files): void
    {
        if ($files) {
            $fileIds = array_map(fn($file) => $file->id, $files);
            $this->_table->{$this->tableAlias}->deleteAll(['id IN' => $fileIds]);

            $this->uploadHandler->remove($files);
        }
    }

    /**
     * Sets upload handler
     *
     * @return void
     */
    public function setUploadHandler(AbstractFileHandler $uploadHandler): void
    {
        $this->uploadHandler = $uploadHandler;
    }

    /**
     * Returns upload handler
     *
     * @return \App\Lib\AbstractFileHandler
     */
    public function getUploadHandler(): AbstractFileHandler
    {
        if (!isset($this->uploadHandler)) {
            throw new RuntimeException(
                'UploadHandler is not set. Use setUploadHandler() before calling this method.',
            );
        }

        return $this->uploadHandler;
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
        $hash = bin2hex(random_bytes(max(1, $bytes)));
        $path = '/' . implode('/', str_split(substr($hash, 0, $sublevels * $chunkLength), $chunkLength));

        return $path;
    }
}
