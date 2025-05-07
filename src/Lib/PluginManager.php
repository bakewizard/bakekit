<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\App;
use Cake\Datasource\ModelAwareTrait;
use Cake\Routing\Router;
use Cake\Utility\Hash;
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
        $this->pluginsDir = current(App::path('plugins'));
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
     * Gets all available cells from all loaded plugins.
     *
     * @return array<string, mixed>
     */
    public function getCells(): array
    {
        $plugins = $this->getPlugins(true);
        $data = [];
        foreach ($plugins as $plugin) {
            $pluginName = $plugin === 'System' ? null : $plugin;
            $path = App::classPath('View/Cell', $pluginName)[0];

            if (!is_dir($path)) {
                continue;
            }

            $files = array_diff(scandir($path), ['.', '..', 'BlockCell.php']);

            foreach ($files as $file) {
                if (is_dir($path . $file) || !str_ends_with($file, 'Cell.php')) {
                    continue;
                }

                $cell = substr(pathinfo($file, PATHINFO_FILENAME), 0, -4);
                $pluginAndCell = is_null($pluginName) ? $cell : "{$pluginName}.{$cell}";
                $className = App::className($pluginAndCell, 'View/Cell', 'Cell');
                $reflection = new ReflectionClass($className);
                $declaredMethods = array_filter(
                    $reflection->getMethods(
                        ReflectionMethod::IS_PUBLIC,
                    ),
                    fn($method) => $method->getDeclaringClass()->getName() === $className && $method->name !== 'initialize',
                );

                foreach ($declaredMethods as $method) {
                    $docBlock = new DocBlockParser($method->getDocComment());
                    $data[$plugin][] = [
                        'summary' => $docBlock->getSummary(),
                        'description' => $docBlock->getDescription(),
                        'path' => $method->name === 'display' ? $pluginAndCell : "$pluginAndCell::{$method->name}",
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Gets all available links from all loaded plugins.
     *
     * @return array<string, mixed>
     */
    public function getLinks(): array
    {
        $plugins = $this->getPlugins();
        $data = [];
        foreach ($plugins as $plugin) {
            $path = App::classPath('Controller', $plugin)[0];

            if (!is_dir($path)) {
                continue;
            }

            $files = array_diff(scandir($path), ['.', '..', 'AppController.php']);

            foreach ($files as $file) {
                if (is_dir($path . $file) || !str_ends_with($file, 'Controller.php')) {
                    continue;
                }

                $controller = substr(pathinfo($file, PATHINFO_FILENAME), 0, -10);
                $className = App::className("{$plugin}.{$controller}", 'Controller', 'Controller');
                $reflection = new ReflectionClass($className);
                $declaredMethods = array_filter(
                    $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
                    fn($method) => $method->getDeclaringClass()->getName() === $className
                        && !in_array($method->name, ['initialize', 'beforeFilter', 'beforeRender', 'afterFilter']),
                );

                foreach ($declaredMethods as $method) {
                    $docComment = $method->getDocComment();
                    if ($docComment) {
                        $docBlock = new DocBlockParser($docComment);
                        $showModal = $method->getNumberOfParameters() === 1;

                        $data[$plugin][] = [
                            'summary' => $docBlock->getSummary(),
                            'description' => $docBlock->getDescription(),
                            'url' => Router::url([
                                'plugin' => $plugin,
                                'prefix' => $showModal ? 'Admin' : false,
                                'controller' => $showModal ? $docBlock->getTag('items') : $controller,
                                'action' => $showModal ? 'index' : $method->name,
                            ]),
                            'target' => $showModal ? '_blank' : '_self',
                        ];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Gets all available admin links from all loaded plugins.
     *
     * @return array<string, mixed>
     */
    public function getAdminLinks(): array
    {
        $plugins = $this->getPlugins();
        $data = [];
        foreach ($plugins as $plugin) {
            $path = App::classPath('Controller/Admin', $plugin)[0];

            if (!is_dir($path)) {
                continue;
            }

            $files = array_diff(scandir($path), ['.', '..', 'AppController.php', 'ErrorController.php']);

            foreach ($files as $file) {
                if (is_dir($path . $file) || !str_ends_with($file, 'Controller.php')) {
                    continue;
                }

                $controller = substr(pathinfo($file, PATHINFO_FILENAME), 0, -10);
                $className = App::className("{$plugin}.{$controller}", 'Controller/Admin', 'Controller');
                $reflection = new ReflectionClass($className);

                if (!$reflection->hasMethod('index')) {
                    continue;
                }

                $declaredMethods = array_filter(
                    $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
                    fn($method) => $method->getDeclaringClass()->getName() === $className
                        && !in_array(
                            $method->name,
                            ['initialize', 'beforeFilter', 'beforeRender', 'afterFilter'],
                        ),
                );

                foreach ($declaredMethods as $method) {
                    if ($method->name == 'index' || $method->getNumberOfParameters() === 0) {
                        $docComment = $method->getDocComment();
                        if ($docComment) {
                            $docBlock = new DocBlockParser($docComment);
                            $data[$plugin][] = [
                                'summary' => $docBlock->getSummary(),
                                'description' => $docBlock->getDescription(),
                                'url' => Router::url(['plugin' => $plugin, 'controller' => $controller, 'action' => $method->name]),
                                'target' => '_self',
                            ];
                        }
                    }
                }
            }
        }

        return $data;
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

        $rootNode = $table->checkNode('Site', null) ?? $table->createNode('Site', null);

        foreach ($plugins as $plugin) {
            $pluginNode = $table->createNode($plugin, $rootNode->id);

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

                $controller = substr(substr($file, 0, strrpos($file, '.')), 0, -10);
                $className = App::className($plugin ? "{$plugin}.{$controller}" : $controller, 'Controller/Admin', 'Controller');
                $reflection = new ReflectionClass($className);
                $actions = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

                $controllerNode = $table->createNode($controller, $pluginNode->id);

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
            $fields = $config->getSchema()->fields();
            $data = [];
            foreach ($fields as $fieldName) {
                $fieldAttrs = $config->getSchema()->field($fieldName);
                $data[$fieldName] = $fieldAttrs['default'];
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
