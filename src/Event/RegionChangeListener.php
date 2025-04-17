<?php

declare(strict_types=1);

namespace App\Event;

use Cake\Cache\Cache;
use Cake\Datasource\FactoryLocator;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\I18n\I18n;
use Cake\ORM\Query\SelectQuery;

class RegionChangeListener implements EventListenerInterface
{

    #[\Override]
    public function implementedEvents(): array
    {
        return [
            'Model.afterSave' => 'createCache',
            'Model.afterDelete' => 'createCache'
        ];
    }

    public function createCache(EventInterface $event)
    {
        $regions = FactoryLocator::get('Table')->get('Regions');

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
