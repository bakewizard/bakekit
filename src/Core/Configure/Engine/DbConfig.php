<?php
declare(strict_types=1);

namespace App\Core\Configure\Engine;

use Cake\Cache\Cache;
use Cake\Core\Configure\ConfigEngineInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Override;

/**
 * Configuration engine to store and retrieve settings from a database table.
 */
class DbConfig implements ConfigEngineInterface
{
    public const TABLE = 'Settings';

    /**
     * Cache configuration key.
     */
    protected string $_cacheConfig;

    /**
     * Instance of the settings table.
     */
    protected Table $_table;

    /**
     * Constructor
     *
     * @param \Cake\ORM\Table|string|null $table Table alias or instance.
     * @param string $cacheConfig Cache config alias.
     */
    public function __construct(Table|string|null $table = null, string $cacheConfig = 'default')
    {
        $table ??= self::TABLE;

        if (is_string($table)) {
            $table = TableRegistry::getTableLocator()->get($table);
        }

        $this->_table = $table;
        $this->_cacheConfig = $cacheConfig;
    }

    /**
     * Read configuration data from the database.
     *
     * @param string $key Configuration key to read. Use '*' to read all settings.
     * @return array Associative array of settings, with keys as paths and values as setting values.
     */
    #[Override]
    public function read(string $key): array
    {
        $query = $this->_table->find(
            'list',
            keyField: 'path',
            valueField: 'value',
            groupField: 'namespace',
        )->formatResults(function ($results) {
            $resultSet = $results->toArray();

            if (isset($resultSet[''])) {
                $resultSet += $resultSet[''];
                unset($resultSet['']);
            }

            return $resultSet;
        });

        if ($key !== '*') {
            $query->where([$this->_table->aliasField('namespace') . ' IS' => $key]);
        }

        $cacheKey = $key === '*' ? 'settings_all' : "settings_$key";

        $data = $query->cache($cacheKey, $this->_cacheConfig)->toArray();

        if ($key === '*') {
            return array_map([Hash::class, 'expand'], $data);
        }

        return isset($data[$key]) ? [$key => Hash::expand($data[$key])] : [];
    }

    /**
     * Write configuration data to the database.
     *
     * @param string $key Configuration namespace to write.
     * @param array $data Associative array of settings, with keys as paths and values as setting values.
     * @return bool True on success, false on failure.
     */
    #[Override]
    public function dump(string $key, array $data): bool
    {
        $flatData = Hash::flatten($data);
        $successCount = 0;

        foreach ($flatData as $path => $value) {
            if ($this->_persist($value, $path, $key)) {
                $successCount++;
            }
        }

        // Invalidate cache
        Cache::delete("settings_$key", $this->_cacheConfig);
        if ($key === '*') {
            Cache::delete('settings_all', $this->_cacheConfig);
        }

        return $successCount > 0;
    }

    /**
     * Persist a single setting into the database.
     *
     * @param mixed $value Setting value.
     * @param string $path Dot-notated path key.
     * @param string $namespace Configuration namespace.
     * @return bool True on success, false on failure.
     */
    protected function _persist(mixed $value, string $path, string $namespace): bool
    {
        $entity = $this->_table->find()
            ->where(['namespace' => $namespace, 'path' => $path])
            ->first();

        if (!$entity) {
            $entity = $this->_table->newEntity([
                'namespace' => $namespace,
                'path' => $path,
                'value' => $value === '' ? null : $value,
            ]);
        } else {
            // Ensure the value is updated even if it's not dirty
            $entity->set('value', $value === '' ? null : $value, ['guard' => false]);
        }

        return (bool)$this->_table->save($entity);
    }
}
