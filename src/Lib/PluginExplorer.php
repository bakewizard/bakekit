<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\App;
use Cake\Routing\Router;
use ReflectionClass;
use ReflectionMethod;

class PluginExplorer
{
    /**
     * Gets all available cells from all loaded plugins.
     *
     * @param array<string> $plugins List of plugins to check for cells.
     * @return array<string, mixed>
     */
    public function getCells(array $plugins): array
    {
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
                if ($className === null) {
                    continue;
                }
                $reflection = new ReflectionClass($className);
                $declaredMethods = array_filter(
                    $reflection->getMethods(
                        ReflectionMethod::IS_PUBLIC,
                    ),
                    fn($method) => $method->getDeclaringClass()->getName() === $className && $method->name !== 'initialize',
                );

                // Iterate through each declared method to extract its documentation and construct
                // an array containing the method's summary, description, and path for the plugin.
                foreach ($declaredMethods as $method) {
                    $docComment = $method->getDocComment();
                    $docBlock = new DocBlockParser($docComment !== false ? $docComment : null);
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
     * @param array<string> $plugins List of plugins to check for cells.
     * @return array<string, mixed>
     */
    public function getLinks(array $plugins): array
    {
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
                if ($className === null) {
                    continue;
                }
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
     * @param array<string> $plugins List of plugins to check for cells.
     * @return array<string, mixed>
     */
    public function getAdminLinks(array $plugins): array
    {
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
                if ($className === null) {
                    continue;
                }
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
}
