<?php

declare(strict_types=1);

namespace App\Model\Behavior;

use App\Lib\UploadHandlerInterface;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;

/**
 * Image behavior
 */
class UploadBehavior extends Behavior
{

    private $_uploadHandler = null;
    private $tableAlias = 'Files';

    /**
     * Default configuration.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'maxFiles' => 10,
        'allowedFiles' => ['image/jpeg', 'image/png', 'image/gif'],
        'multiple' => false,
        'modelPath' => null,
        'dirDepth' => 2,
        'uploadHandler' => [
            'class' => '\\App\\Lib\\DefaultUploadHandler'
        ]
    ];

    #[\Override]
    public function initialize(array $config): void
    {
        list($plugin, $table) = pluginSplit(strtolower($this->_table->getRegistryAlias()));
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
            'table' => Inflector::singularize($this->_table->getTable()) . '_' . ($config['tablePostfix'] ?? 'files')
        ]);

        if ($this->_config['multiple']) {
            $this->_table->hasMany($this->tableAlias, [
                'targetTable' => $filesTable,
                'saveStrategy' => 'replace',
                'foreignKey' => $foreignKey,
                'propertyName' => 'files',
                'sort' => 'sort_order asc'
            ]);
        } else {
            $this->_table->hasMany($this->tableAlias, [
                'targetTable' => $filesTable,
                'saveStrategy' => 'replace',
                'foreignKey' => $foreignKey,
                'propertyName' => 'files'
            ]);
        }
    }

    public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, $primary)
    {
        $query
                ->select('id')
                ->enableAutoFields(true)
                ->contain($this->tableAlias);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
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

                    if ($upload->getError() == \UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    $data['files'][$i]['name'] = $upload->getClientFilename();
                    $data['files'][$i]['path'] = $this->getConfig('modelPath') . $this->generatePath();
                    $data['files'][$i]['format'] = $this->_uploadHandler->getConfig('format');
                    $data['files'][$i]['tmp_name'] = $upload->getStream()->getMetadata('uri');
                }
            }
        } else {
            if ($uploads[0]->getSize() > 0) {
                $data['files'][0]['name'] = $uploads[0]->getClientFilename();
                $data['files'][0]['path'] = $this->getConfig('modelPath') . $this->generatePath();
                $data['files'][0]['format'] = $this->_uploadHandler->getConfig('format');
                $data['files'][0]['tmp_name'] = $uploads[0]->getStream()->getMetadata('uri');
            }
        }
    }

    public function afterSave(EventInterface $event, EntityInterface $entity)
    {
        if ($entity->isDirty('files')) {
            $files = $entity->get('files');

            if (!$entity->isNew()) {
                $filesToRemove = array_udiff($entity->getOriginal('files'), $files, fn($a, $b) => $a->id <=> $b->id);
                if (!empty($filesToRemove)) {
                    $this->_uploadHandler->remove($filesToRemove);
                }
            }

            $this->_uploadHandler->handle($files);
        }
    }

    public function afterDelete(EventInterface $event, EntityInterface $entity)
    {
        $this->_uploadHandler->remove($entity->files);
    }

    public function remove($files)
    {
        $fileIds = array_map(fn($file) => $file->id, $files);
        $this->_table->{$this->tableAlias}->deleteAll(['id IN' => $fileIds]);

        $this->_uploadHandler->remove($files);
    }

    public function getUploadHandler(): UploadHandlerInterface
    {
        return $this->_uploadHandler;
    }

    private function loadUploadHandler()
    {
        $uploadHandlerClass = $this->_config['uploadHandler']['class'];
        unset($this->_config['uploadHandler']['class']);
        $config = $this->_config['uploadHandler'];

        $uploadHandler = new $uploadHandlerClass($config);

        if ($uploadHandler instanceof UploadHandlerInterface) {
            $this->_uploadHandler = $uploadHandler;
        }
    }

    private function generatePath()
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
