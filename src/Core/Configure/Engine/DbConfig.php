<?php
declare(strict_types=1);

namespace App\Core\Configure\Engine;

use App\Model\Table\SettingsTable;
use Cake\Cache\Cache;
use Cake\Core\Configure\ConfigEngineInterface;
use Cake\Utility\Hash;
use Override;

/**
 * Custom Database Configuration Engine for BakeKit.
 *
 * Reads flattened dynamic settings from the database and expands them
 * into multi-dimensional arrays, blending them seamlessly into CakePHP's Configure.
 * Fully optimized for PHP 8.3 static analysis (Intelephense & PHPStan).
 */
class DbConfig implements ConfigEngineInterface
{
    /**
     * Constructor using PHP 8.x Property Promotion.
     * Automatically declares and injects the table and cache configuration.
     *
     * @param \App\Model\Table\SettingsTable $table SettingsTable instance.
     * @param string $cacheConfig Cache configuration alias (default: 'default').
     */
    public function __construct(
        protected SettingsTable $table,
        protected string $cacheConfig = 'default',
    ) {
    }

    /**
     * Reads configuration information from the database.
     *
     * @param string $key The namespace key to read. Use '*' to fetch all configurations.
     * @return array<string, mixed> An array of nested data to merge into the runtime configuration.
     */
    #[Override]
    public function read(string $key): array
    {
        $query = $this->table->find();

        if ($key !== '*') {
            $query->where([
                $this->table->aliasField('namespace') . ' IS' => $key,
            ]);
        }

        // 1. Disable hydration to fetch blazing-fast raw arrays instead of heavy entities.
        // We iterate directly over the ResultSet to keep Intelephense (P1131) perfectly happy.
        $results = $query
            ->enableHydration(false)
            ->cache($this->cacheKey($key), $this->cacheConfig)
            ->all();

        if ($results->isEmpty()) {
            return [];
        }

        // 2. Build the grouped array manually (100% type-safe, no functions called inside the loop)
        /** @var array<string, array<string, mixed>> $data */
        $data = [];
        foreach ($results as $row) {
            /** @var array<string, mixed> $row */
            $namespace = (string)($row['namespace'] ?? '');
            $path = (string)($row['path'] ?? '');

            $data[$namespace][$path] = $row['value'] ?? null;
        }

        // 3. Promote empty namespace settings to the root level
        if (isset($data[''])) {
            $data = $data[''] + $data;
            unset($data['']);
        }

        // 4. Expand the flattened dot-notation data back into a nested array structure
        if ($key !== '*' && array_key_exists($key, $data)) {
            return [$key => Hash::expand($data[$key])];
        }

        if ($key === '*') {
            $expandedData = [];
            foreach ($data as $namespace => $flatData) {
                $expandedData[$namespace] = Hash::expand($flatData);
            }

            return $expandedData;
        }

        return [];
    }

    /**
     * Writes configuration data from a namespace back to the database.
     *
     * @param string $key The identifier to write to (the namespace).
     * @param array<string, mixed> $data The multi-dimensional data to dump.
     * @return bool True on success, false on failure.
     */
    #[Override]
    public function dump(string $key, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        // Flatten the multi-dimensional array into dot-notation paths (e.g., 'images.th')
        $flattenedData = Hash::flatten($data);
        $success = true;

        foreach ($flattenedData as $path => $value) {
            if (!$this->persist($key, $path, $value)) {
                $success = false;
            }
        }

        // Invalidate the cache namespace only if all rows were persisted successfully
        if ($success) {
            Cache::delete($this->cacheKey($key), $this->cacheConfig);
        }

        return $success;
    }

    /**
     * Persists or updates a single configuration entry in the database.
     *
     * @param string $namespace The setting's namespace (e.g., 'Shop').
     * @param string $path The key path (e.g., 'productImages.th').
     * @param mixed $value The value to store.
     * @return bool True on success, false on failure.
     */
    protected function persist(string $namespace, string $path, mixed $value): bool
    {
        $entity = $this->table->find()
            ->where([
                $this->table->aliasField('namespace') => $namespace,
                $this->table->aliasField('path') => $path,
            ])
            ->first() ?? $this->table->newEmptyEntity();

        $entity = $this->table->patchEntity($entity, [
            'namespace' => $namespace,
            'path' => $path,
            'value' => $value,
        ]);

        return (bool)$this->table->save($entity);
    }

    /**
     * Generates a standardized cache key based on the namespace.
     *
     * @param string $namespace The configuration namespace.
     * @return string The generated cache key.
     */
    protected function cacheKey(string $namespace): string
    {
        return 'settings_' . strtolower($namespace);
    }
}
