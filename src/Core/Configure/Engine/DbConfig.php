<?php

declare(strict_types=1);

namespace App\Core\Configure\Engine;

use Cake\Core\Configure\ConfigEngineInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Hash;

class DbConfig implements ConfigEngineInterface
{

    const TABLE = 'Settings';

    /**
     * Cache configuration key.
     *
     * @var string
     */
    protected $_cacheConfig;

    /**
     * Instance of the configurations table.
     *
     * @var \Cake\ORM\Table
     */
    protected $_table;

    /**
     * Constructor to inject the table and define the cache configuration to use.
     *
     * @param \Cake\ORM\Table|string|null $table Table alias or instance.
     * @param string $cacheConfig Cache config alias.
     */
    public function __construct($table = null, $cacheConfig = 'default')
    {
        if (empty($table)) {
            $table = self::TABLE;
        }
        if (is_string($table)) {
            $table = FactoryLocator::get('Table')->get($table);
        }

        $this->_cacheConfig = $cacheConfig;
        $this->_table = $table;
    }

    /**
     * Read method is used for reading configuration information from sources.
     * These sources can either be static resources like files, or dynamic ones like
     * a database, or other datasource.
     *
     * @param string $key Key to read.
     * @return array An array of data to merge into the runtime configuration
     */
    #[\Override]
    public function read(string $key): array
    {
        $query = $this->_table->find('list',
                        keyField: 'path',
                        valueField: 'value',
                        groupField: 'namespace')
                ->formatResults(function ($results) {
                    $resultSet = $results->toArray();
                    if (isset($resultSet[''])) {
                        $resultSet += $resultSet[''];
                        unset($resultSet['']);
                    }
                    return $resultSet;
                });

        if ($key !== '*') {
            $query->where([
                $this->_table->aliasField('namespace') . ' IS' => $key
            ]);
        }

        $data = $query->cache('settings', $this->_cacheConfig)->toArray();

        return $data ? [$key => Hash::expand($data[$key])] : [];
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key The identifier to write to.
     * @param array $data The data to dump.
     * @return bool True on success or false on failure.
     */
    #[\Override]
    public function dump(string $key, array $data): bool
    {
        $data = Hash::flatten($data);
        array_walk($data, [$this, '_persist'], $key);
        array_filter($data);
        return (bool) $data;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value Value.
     * @param string $path Key path.
     * @param string $namespace Namespace.
     * @return bool
     */
    protected function _persist($value, $path, $namespace)
    {
        $table = $this->_table;

        $entity = $table->find()->where([
                    $table->aliasField('namespace') => $namespace,
                    $table->aliasField('path') => $path,
                ])->first();

        if (empty($entity)) {
            $entity = $table->newEmptyEntity();
        }

        return $table->patchEntity($entity, compact('namespace', 'path', 'value')) && $table->save($entity);
    }
}
