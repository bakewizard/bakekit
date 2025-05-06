<?php
declare(strict_types=1);

namespace App\Event;

use Cake\Cache\Cache;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\I18n\I18n;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Override;

class RegionChangeListener implements EventListenerInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function implementedEvents(): array
    {
        return [
            'Model.afterSave' => 'createCache',
            'Model.afterDelete' => 'createCache',
        ];
    }

    /**
     * Creates or updates the regions cache.
     *
     * This method is triggered after a save or delete operation on a model.
     * It fetches all regions with their enabled and ordered blocks (with translations)
     * and stores them in the cache. It specifically sets the default locale for
     * the 'Blocks' table to ensure correct translation retrieval.
     *
     * @param \Cake\Event\EventInterface $event The event object.
     * @return void
     */
    public function createCache(EventInterface $event): void
    {
        $regions = TableRegistry::getTableLocator()->get('Regions');

        $table = $event->getSubject();

        if ($table->getAlias() === 'Blocks') {
            $table->setLocale(I18n::getDefaultLocale());
        }

        $cache = $regions
                ->find()
                ->contain('Blocks', function (SelectQuery $q) {
                    return $q->find('translations')
                                    ->where(['Blocks.enabled' => 1])
                                    ->orderBy(['Blocks.position' => 'ASC']);
                })
                ->all()
                ->indexBy('alias')
                ->toArray();

        Cache::write('regions', $cache, 'cms');
    }
}
