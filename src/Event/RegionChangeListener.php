<?php
declare(strict_types=1);

namespace App\Event;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\I18n\I18n;
use Cake\ORM\Query\SelectQuery;
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
            'Region.rebuild' => 'createCache',
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
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The event object.
     * @return void
     */
    public function createCache(EventInterface $event): void
    {
        $table = $event->getSubject();

        if ($table->getAlias() === 'Blocks') {
            /** @var \Cake\ORM\Behavior\TranslateBehavior $translate */
            $translate = $table->getBehavior('Translate');
            $translate->setLocale(I18n::getDefaultLocale());
            $regions = $table->getAssociation('Regions')->getTarget();
        } else {
            $regions = $table;
        }

        $theme = $event->getData('theme') ?? Configure::read('System.theme');

        $cache = $regions
            ->find()
            ->where(['theme IS' => $theme])
            ->contain('Blocks', function (SelectQuery $q) {
                return $q->find('translations')
                    ->where(['Blocks.enabled' => 1])
                    ->orderBy(['Blocks.position' => 'ASC']);
            })
            ->all()
            ->indexBy('alias')
            ->toArray();

        Cache::write('regions', $cache, 'system');
    }
}
