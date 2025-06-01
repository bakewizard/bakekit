<?php
declare(strict_types=1);

namespace App\Lib;

use App\Model\Table\ResourcesTable;
use App\Model\Table\SettingsTable;
use Cake\Core\App;
use Cake\Form\Form;
use Cake\Utility\Hash;
use LogicException;
use Migrations\Migrations;

/**
 * @property \App\Model\Table\SettingsTable $Settings
 * @property \App\Model\Table\ResourcesTable $Resources
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
     * Undocumented variable
     *
     * @var \App\Model\Table\SettingsTable
     */
    private SettingsTable $settingsTable;

    private ResourcesTable $resourcesTable;

    /**
     * PluginManager constructor
     *
     * Sets plugins dir
     */
    public function __construct(ResourcesExplorer $resourcesExplorer, Migrations $migrations, SettingsTable $settingsTable, ResourcesTable $resourcesTable)
    {
        $this->resourcesExplorer = $resourcesExplorer;
        $this->migrations = $migrations;
        $this->settingsTable = $settingsTable;
        $this->resourcesTable = $resourcesTable;
        $this->pluginsDir = current(App::path('plugins')) ?: '';
    }

    /**
     * Activates a plugin by adding its migrations, settings, and resources.
     *
     * @param string $plugin Plugin name to activate.
     * @return void
     */
    public function activate(string $plugin): void
    {
        $this->addMigrations($plugin);
        $this->addSettings($plugin);
        $this->addResources($plugin);
    }

    /**
     * Uninstalls a plugin by removing its migrations, settings, and resources.
     *
     * @param string $plugin Plugin name to uninstall.
     * @return void
     */
    public function uninstall(string $plugin): void
    {
        $this->deleteMigrations($plugin);
        $this->deleteSettings($plugin);
        $this->deleteResources($plugin);
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

        return $this->migrations->migrate(['plugin' => $plugin]) &&
            $this->migrations->seed(['plugin' => $plugin]);
    }

    /**
     * Deletes migrations for the plugin
     *
     * @param string $plugin Plugin name.
     * @return void
     */
    public function deleteMigrations(string $plugin): void
    {
        if (is_dir($this->pluginsDir . $plugin . DS . 'config' . DS . 'Migrations')) {
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
    }
}
