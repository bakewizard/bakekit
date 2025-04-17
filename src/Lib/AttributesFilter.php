<?php

declare(strict_types=1);

namespace App\Lib;

use Cake\Cache\Cache;
use KSamuel\FacetedSearch\Filter\RangeFilter;
use KSamuel\FacetedSearch\Filter\ValueFilter;
use KSamuel\FacetedSearch\Index\Factory;
use KSamuel\FacetedSearch\Indexer\Number\RangeIndexer;
use KSamuel\FacetedSearch\Query\AggregationQuery;
use KSamuel\FacetedSearch\Query\SearchQuery;

class AttributesFilter
{

    protected $cacheConfig = 'default';
    private array $rangeAttributes = [];

    public function setCacheConfig($cacheConfig): void
    {
        $this->cacheConfig = $cacheConfig;
    }

    /**
     * Converts url path into attributes array
     * 
     * Path: /some-attribute-1_some-val-1_some-val-2/
     * 
     * @param string $path
     * @return array
     */
    public function parsePath(string $path): array
    {
        $attributes = [];

        $segments = explode('/', trim($path, '/'));
        foreach ($segments as $segment) {
            if (strpos($segment, '_') !== false) {
                [$key, $values] = explode('_', trim($segment), 2);
                // Handle range attributes like "price_min-12_max-24"
                if (strpos($values, 'min-') !== false || strpos($values, 'max-') !== false) {
                    $range = [];
                    foreach (explode('_', $values) as $part) {
                        [$rangeKey, $rangeValue] = explode('-', $part, 2);
                        $range[trim($rangeKey)] = trim($rangeValue);
                    }
                    $attributes[trim($key)] = $range;
                } else {
                    // Handle regular attributes
                    $attributes[trim($key)] = array_map('trim', explode('_', $values));
                }
            }
        }

        return $attributes;
    }

    /**
     * Fetch filtered product IDs and active filters based on attributes.
     *
     * @param int|null $categoryId
     * @param array|string $attributes
     * @return array [ids, activeFilters, attributes]
     */
    public function fetch(int|string $categoryId, array|string $attributes): array
    {
        if (is_string($attributes)) {
            $attributes = $this->parsePath($attributes);
        }

        $index = Cache::read((string) $categoryId . '_idx', $this->cacheConfig);

        if (!$index) {
            return [null, [], null];
        }

        $search = (new Factory)->create(Factory::ARRAY_STORAGE);
        $search->setData($index);

        $filters = [];
        foreach ($attributes as $attribute => $options) {
            if (isset($options['min'], $options['max'])) {
                $filters[] = new RangeFilter($attribute, $options);
            } else {
                $filters[] = new ValueFilter($attribute, $options);
            }
        }

        $aggregationQuery = (new AggregationQuery())->filters($filters)->countItems()->sort();
        $activeOptions = $search->aggregate($aggregationQuery);

        $ids = [];
        if ($attributes) {
            $searchQuery = (new SearchQuery())->filters($filters);
            $ids = $search->query($searchQuery);
        }

        return [$ids, $activeOptions, $attributes];
    }

    public function setRangeAttributes(array $attributes): void
    {
        $this->rangeAttributes = $attributes;
    }

    public function createIndex(int|string $categoryId, array &$entities): void
    {
        $search = (new Factory)->create(Factory::ARRAY_STORAGE);
        $storage = $search->getStorage();

        if (!empty($this->rangeAttributes)) {
            foreach ($this->rangeAttributes as $name => $step) {
                $storage->addIndexer($name, new RangeIndexer($step));
            }
        }

        foreach ($entities as $entity) {
            $storage->addRecord($entity['id'], $entity['attributes']);
        }

        $storage->optimize();

        Cache::write(strval($categoryId) . '_idx', $storage->export(), $this->cacheConfig);
    }
}
