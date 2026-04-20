<?php
declare(strict_types=1);

namespace App\Lib;

use App\Model\Table\ResourcesTable;
use App\Model\Table\SettingsTable;
use Cake\Cache\Cache;
use Cake\Form\Form;
use Cake\Utility\Hash;
use Exception;
use LogicException;
use Migrations\Migrations;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Manages the full lifecycle of plugins: discovery, installation,
 * activation, deactivation, and uninstallation.
 */
class PluginManager
{
    /**
     * Directory where plugins are stored
     *
     * @var string
     */
    private string $pluginsDir;

    /**
     * ExtensionHandler instance
     *
     * @var \App\Lib\ExtensionHandler
     */
    private ExtensionHandler $extensionHandler;

    /**
     * ResourcesExplorer instance
     *
     * @var \App\Lib\ResourcesExplorer
     */
    private ResourcesExplorer $resourcesExplorer;

    /**
     * Migrations instance
     *
     * @var \Migrations\Migrations
     */
    private Migrations $migrations;

    /**
     * SettingsTable instance
     *
     * @var \App\Model\Table\SettingsTable
     */
    private SettingsTable $settingsTable;

    /**
     * ResourcesTable instance
     *
     * @var \App\Model\Table\ResourcesTable
     */
    private ResourcesTable $resourcesTable;

    /**
     * PluginManager constructor
     *
     * Sets plugins dir
     */
    public function __construct(
        ExtensionHandler $extensionHandler,
        ResourcesExplorer $resourcesExplorer,
        Migrations $migrations,
        SettingsTable $settingsTable,
        ResourcesTable $resourcesTable,
    ) {
        $this->extensionHandler = $extensionHandler;
        $this->resourcesExplorer = $resourcesExplorer;
        $this->migrations = $migrations;
        $this->settingsTable = $settingsTable;
        $this->resourcesTable = $resourcesTable;
        $this->pluginsDir = ROOT . DS . 'plugins' . DS;
    }

    /**
     * Reads the composer.json file from the plugin directory.
     *
     * @return array<string, mixed> Parsed composer.json data.
     */
    public function list(): array
    {
        return $this->extensionHandler->discover($this->pluginsDir);
    }

    /**
     * Installs a plugin by loading it from an uploaded file.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file Uploaded file containing the plugin.
     * @return string Installed name (folder)
     * @throws \Exception If the plugin cannot be loaded.
     */
    public function install(UploadedFileInterface $file): string
    {
        return $this->extensionHandler->load($file, $this->pluginsDir);
    }

    /**
     * Uninstalls a plugin by removing its migrations, settings, and resources.
     *
     * @param string $plugin Plugin name to uninstall.
     * @param bool $isActive Whether the plugin is currently active.
     * @return void
     */
    public function uninstall(string $plugin, bool $isActive = false): void
    {
        if ($isActive) {
            $this->deleteMigrations($plugin);
            $this->deleteSettings($plugin);
            $this->deleteResources($plugin);
        }

        $this->extensionHandler->unload($plugin, $this->pluginsDir);
    }

    /**
     * Activates a plugin by adding its migrations, settings, and resources.
     *
     * @param string $plugin Plugin name to activate.
     * @return array
     */
    public function activate(string $plugin): array
    {
        $migrationsAdded = false;
        $settingsAdded = false;
        $resourcesAdded = false;

        try {
            $this->addMigrations($plugin);
            $migrationsAdded = true;

            $this->addSettings($plugin);
            $settingsAdded = true;

            $this->addResources($plugin);
            $resourcesAdded = true;

            return $this->extensionHandler->readComposerConfig($this->pluginsDir . $plugin);
        } catch (Exception $e) {
            if ($resourcesAdded) {
                $this->deleteResources($plugin);
            }
            if ($settingsAdded) {
                $this->deleteSettings($plugin);
            }
            if ($migrationsAdded) {
                $this->deleteMigrations($plugin);
            }

            throw $e;
        }
    }

    /**
     * Adds migrations for the plugin.
     *
     * If the plugin has a 'config/Migrations' directory, it runs the migrations and seeds.
     *
     * @param string|null $plugin Plugin name or null if it's a System plugin.
     * @return bool
     */
    public function addMigrations(?string $plugin = null): bool
    {
        $migrationsPath = 'config' . DS . 'Migrations';
        $path = $plugin === null
            ? ROOT . DS . $migrationsPath
            : $this->pluginsDir . $plugin . DS . $migrationsPath;

        if (!is_dir($path)) {
            return false;
        }

        $migrated = $this->migrations->migrate(['plugin' => $plugin]);
        if (!$migrated) {
            throw new Exception(__('Failed to run migrations for plugin: {0}', $plugin));
        }

        $seeded = $this->migrations->seed(['plugin' => $plugin]);
        if (!$seeded) {
            throw new Exception(__('Failed to seed migrations for plugin: {0}', $plugin));
        }

        return true;
    }

    /**
     * Deletes migrations for the plugin or system if no plugin is specified.
     *
     * @param string|null $plugin Plugin name or null for system migrations.
     * @return void
     */
    public function deleteMigrations(?string $plugin = null): void
    {
        $migrationsPath = 'config' . DS . 'Migrations';
        $path = $plugin === null
            ? ROOT . DS . $migrationsPath
            : $this->pluginsDir . $plugin . DS . $migrationsPath;

        if (is_dir($path)) {
            $this->migrations->rollback(['plugin' => $plugin]);
        }
    }

    /**
     * Saves plugin default settings into db.
     *
     * @param string|null $plugin Plugin name or null if it's a System plugin.
     * @return void
     */
    public function addSettings(?string $plugin = null): void
    {
        $configClass = isset($plugin) ? "\\{$plugin}\\Form\\ConfigForm" : '\\App\\Form\\ConfigForm';

        if (!class_exists($configClass)) {
            return;
        }

        $config = new $configClass();

        if (!$config instanceof Form) {
            throw new LogicException(sprintf('Expected an instance of %s, got %s', Form::class, get_class($config)));
        }

        $fields = $config->getSchema()->fields();
        $data = [];
        foreach ($fields as $fieldName) {
            $fieldAttrs = $config->getSchema()->field($fieldName);
            if (is_array($fieldAttrs) && array_key_exists('default', $fieldAttrs)) {
                $data[$fieldName] = $fieldAttrs['default'];
            }
        }
        $config->execute(Hash::expand($data));
    }

    /**
     * Removes plugin settings from db.
     *
     * @param string $plugin Plugin name.
     */
    public function deleteSettings(string $plugin): void
    {
        $this->settingsTable->deleteAll(['namespace' => $plugin]);
    }

    /**
     * Scans plugin for resources (controllers, actions).
     * Adds every found resource into db.
     *
     * @param string $plugin Plugin name.
     * @return void
     */
    public function addResources(string $plugin): void
    {
        $resources = $this->resourcesExplorer->getResources($plugin);
        $this->resourcesTable->addResources($resources);
        Cache::clear('permissions');
    }

    /**
     * Deletes plugin resources.
     *
     * @param string $plugin Plugin name.
     * @return void
     */
    public function deleteResources(string $plugin): void
    {
        $this->resourcesTable->deleteResources($plugin);
        Cache::clear('permissions');
    }
}
