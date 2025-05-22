<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\App;
use Cake\Datasource\ModelAwareTrait;
use Cake\Form\Form;
use Cake\Utility\Hash;
use LogicException;
use Migrations\Migrations;
use ReflectionClass;
use ReflectionMethod;

/**
 * @property \App\Model\Table\PluginsTable $Plugins
 * @property \App\Model\Table\ResourcesTable $Resources
 * @property \App\Model\Table\SettingsTable $Settings
 */
class PluginManager
{
    use ModelAwareTrait;

    private string $pluginsDir;

    /**
     * PluginManager constructor
     *
     * Sets plugins dir
     */
    public function __construct()
    {
        $this->pluginsDir = current(App::path('plugins')) ?: '';
    }

    /**
     * Gets a list of loaded plugins.
     *
     * @param bool $includeSystem Include System plugin if true.
     * @param bool $includeSubPlugins Include sub plugins if true.
     * @return array<string> List of plugin names.
     */
    public function getPlugins(bool $includeSystem = false, bool $includeSubPlugins = false): array
    {
        /** @var \App\Model\Table\PluginsTable $table */
        $table = $this->fetchModel('Plugins');
        $query = $table->find()->select('name')->where(['enabled' => true])->orderByAsc('name');

        if (!$includeSubPlugins) {
            $query->where(['parent_plugin is' => null]);
        }

        $plugins = $query->all()->extract('name')->toList();

        if ($includeSystem) {
            array_unshift($plugins, 'System');
        }

        return $plugins;
    }

    /**
     * Adds migrations for the plugin
     *
     * @param string $plugin
     * @return void
     */
    public function addMigrations(string $plugin): void
    {
        if (is_dir($this->pluginsDir . $plugin . DS . 'config' . DS . 'Migrations')) {
            $migration = new Migrations(['plugin' => $plugin]);
            $migration->migrate();
            $migration->seed();
        }
    }

    /**
     * Deletes migrations for the plugin
     *
     * @param string $plugin
     * @return void
     */
    public function deleteMigrations(string $plugin): void
    {
        if (is_dir($this->pluginsDir . $plugin . DS . 'config' . DS . 'Migrations')) {
            $migration = new Migrations(['plugin' => $plugin]);
            $migration->rollback();
        }
    }

    /**
     * Scans plugin for resources (controllers, actions).
     * Adds every found resource into db.
     *
     * @param string|null $plugin Plugin name, null if all plugins are needed.
     * @return void
     */
    public function addResources(?string $plugin = null): void
    {
        if (is_null($plugin)) {
            $plugins = $this->getPlugins(true, true);
        } else {
            $plugins = [$plugin];
        }
        /** @var \App\Model\Table\ResourcesTable $table */
        $table = $this->fetchModel('Resources');
        /** @var \App\Model\Entity\Resource $rootNode */
        $rootNode = $table->checkNode('Site', null);

        if ($rootNode === null) {
            $rootNode = $table->createNode('Site', null);
        }

        foreach ($plugins as $plugin) {
            /** @var \App\Model\Entity\Resource $rootNode */
            $pluginNode = $table->createNode($plugin, $rootNode->id);

            if ($pluginNode === false) {
                continue;
            }

            if ($plugin === 'System') {
                $plugin = null;
            }

            $path = App::classPath('Controller/Admin', $plugin)[0];

            if (!is_dir($path)) {
                continue;
            }

            $files = array_diff(scandir($path), ['.', '..', 'AppController.php', 'ErrorController.php', 'UsersController.php', 'RolesController.php']);

            foreach ($files as $file) {
                if (is_dir($path . $file)) {
                    continue;
                }

                $position = strrpos($file, '.');
                $baseName = $position !== false ? substr($file, 0, $position) : $file;
                $controller = substr($baseName, 0, strlen($baseName) - 10);
                $className = App::className($plugin ? "{$plugin}.{$controller}" : $controller, 'Controller/Admin', 'Controller');
                if ($className === null) {
                    continue;
                }
                $reflection = new ReflectionClass($className);
                $actions = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

                $controllerNode = $table->createNode($controller, $pluginNode->id);

                if ($controllerNode === false) {
                    continue;
                }
                foreach ($actions as $action) {
                    if ($action->class === $reflection->getName() && !in_array($action->name, ['initialize', 'beforeFilter', 'beforeRender', 'afterFilter'])) {
                        $table->createNode($action->name, $controllerNode->id);
                    }
                }
            }
        }
    }

    /**
     * Deletes plugin resources.
     *
     * @param string $plugin Plugin name.
     * @return void
     */
    public function deleteResources(string $plugin): void
    {
        $table = $this->fetchModel('Resources');

        $resources = $table->find()
            ->where(['alias is' => $plugin, 'parent_id' => 1])
            ->first();

        if (!empty($resources)) {
            $table->delete($resources);
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
        $class = isset($plugin) ? $plugin . '.Config' : 'Config';
        $configClass = App::className($class, 'Form', 'Form');
        if ($configClass) {
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
    }

    /**
     * Removes plugin settings from db.
     *
     * @param string $plugin Plugin name.
     */
    public function deleteSettings(string $plugin): void
    {
        $settings = $this->fetchModel('Settings');
        $settings->deleteAll(['namespace' => $plugin]);
    }
}
