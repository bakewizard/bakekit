<?php
declare(strict_types=1);

namespace App\Core\Configure\Engine;

use App\Model\Table\SettingsTable;
use Cake\Cache\Cache;
use Cake\Core\Configure\ConfigEngineInterface;
use Cake\Utility\Hash;
use Override;

class DbConfig implements ConfigEngineInterface
{
    /**
     * The cache configuration name to use.
     */
    protected string $_cacheConfig;

    /**
     * Instance of the configurations table.
     */
    protected SettingsTable $_table;

    /**
     * Constructor to inject the table and define the cache configuration to use.
     *
     * @param \App\Model\Table\SettingsTable $table SettingsTable instance.
     * @param string $cacheConfig Cache config alias.
     */
    public function __construct(SettingsTable $table, string $cacheConfig = 'default')
    {
        $this->_table = $table;
        $this->_cacheConfig = $cacheConfig;
    }

    /**
     * Reads configuration information from the database.
     *
     * @param string $key Key to read. Use '*' to read all settings.
     * @return array<string, mixed> An array of data to merge into the runtime configuration.
     */
    #[Override]
    public function read(string $key): array
    {
        $query = $this->_table->find(
            'list',
            keyField: 'path',
            valueField: 'value',
            groupField: 'namespace',
        );

        if ($key !== '*') {
            $query->where([
                $this->_table->aliasField('namespace') . ' IS' => $key,
            ]);
        }

        $data = $query
            ->cache($this->_cacheKey($key), $this->_cacheConfig)
            ->formatResults(function ($results) {
                $resultSet = $results->toArray();
                // Promote empty namespace settings to the root level
                if (isset($resultSet[''])) {
                    $resultSet = $resultSet[''] + $resultSet; // Merge empty namespace settings first
                    unset($resultSet['']);
                }

                return $resultSet;
            })
            ->toArray();

        if (empty($data)) {
            return [];
        }

        // When reading a specific key, we expect the data to be nested under that key.
        // We also need to expand the flattened data back into a nested array.
        if ($key !== '*' && array_key_exists($key, $data)) {
            return [$key => Hash::expand($data[$key])];
        } elseif ($key === '*') {
            // If reading all keys, expand each namespace's data
            $expandedData = [];
            foreach ($data as $namespace => $flatData) {
                $expandedData[$namespace] = Hash::expand($flatData);
            }

            return $expandedData;
        }

        return [];
    }

    /**
     * Writes configuration data to the database.
     *
     * @param string $key The identifier to write to (namespace).
     * @param array<string, mixed> $data The data to dump.
     * @return bool True on success or false on failure.
     */
    #[Override]
    public function dump(string $key, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $flattenedData = Hash::flatten($data);
        $success = true;

        foreach ($flattenedData as $path => $value) {
            if (!$this->_persist($key, $path, $value)) {
                $success = false;
                // Optionally, you might want to break here or log the failure
            }
        }

        if ($success) {
            // Invalidate only the specific namespace cache
            Cache::delete($this->_cacheKey($key), $this->_cacheConfig);
        }

        return $success;
    }

    /**
     * Persists a single configuration entry to the database.
     *
     * @param string $namespace The namespace for the setting.
     * @param string $path The key path for the setting.
     * @param mixed $value The value to store.
     * @return bool True on success, false on failure.
     */
    protected function _persist(string $namespace, string $path, mixed $value): bool
    {
        $table = $this->_table;

        $entity = $table->find()
            ->where([
                $table->aliasField('namespace') => $namespace,
                $table->aliasField('path') => $path,
            ])
            ->first();

        if ($entity === null) {
            $entity = $table->newEmptyEntity();
        }

        $entity = $table->patchEntity($entity, [
            'namespace' => $namespace,
            'path' => $path,
            'value' => $value,
        ]);

        return (bool)$table->save($entity);
    }

    /**
     * Generates a cache key based on the namespace.
     *
     * @param string $namespace The namespace to generate the cache key for.
     * @return string The generated cache key.
     */
    protected function _cacheKey(string $namespace): string
    {
        return 'settings_' . strtolower($namespace);
    }
}
