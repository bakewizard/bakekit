<?php
declare(strict_types=1);

namespace App\Event;

use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Override;

abstract class AbstractFileHandler implements EventListenerInterface
{
    use InstanceConfigTrait;

    /**
     * @inheritDoc
     */
    #[Override]
    public function implementedEvents(): array
    {
        return [
            'Model.afterSave' => 'onAfterSave',
            'Model.afterDelete' => 'onAfterDelete',
        ];
    }

    /**
     * Handles file processing after entity save.
     * Removes replaced files and processes new ones.
     *
     * @param \Cake\Event\EventInterface $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @return void
     */
    public function onAfterSave(EventInterface $event, EntityInterface $entity): void
    {
        if (!$entity->isDirty('files')) {
            return;
        }

        $files = $entity->get('files');

        if (!$entity->isNew()) {
            $originalFiles = $entity->getOriginal('files') ?? [];
            $filesToRemove = array_udiff(
                $originalFiles,
                $files,
                fn($a, $b) => $a->id <=> $b->id,
            );
            if (!empty($filesToRemove)) {
                $this->removeFiles($filesToRemove);
            }
        }

        $this->processFiles($files);
    }

    /**
     * Removes physical files after entity delete.
     *
     * @param \Cake\Event\EventInterface $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @return void
     */
    public function onAfterDelete(EventInterface $event, EntityInterface $entity): void
    {
        $files = $entity->get('files');
        if (empty($files)) {
            return;
        }

        $this->removeFiles($files);
    }

    /**
     * For direct usage without events.
     *
     * @param array<mixed> $files
     * @return void
     */
    public function handle(array $files): void
    {
        $this->processFiles($files);
    }

    /**
     * For direct usage without events.
     *
     * @param array<mixed> $files
     * @return void
     */
    public function remove(array $files): void
    {
        $this->removeFiles($files);
    }

    /**
     * Process and store uploaded files.
     *
     * @param array<mixed> $files
     * @return void
     */
    abstract protected function processFiles(array $files): void;

    /**
     * Remove physical files from storage.
     *
     * @param array<mixed> $files
     * @return void
     */
    abstract protected function removeFiles(array $files): void;
}
