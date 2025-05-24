<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\App;
use Cake\Routing\Router;
use ReflectionClass;
use ReflectionMethod;

class ResourcesExplorer
{
    /**
     * Discovers plugin controllers and actions.
     *
     * Returns a nested array in the format:
     * [
     *     'PluginName' => [
     *         'ControllerName' => ['actionOne', 'actionTwo', ...],
     *         'AnotherController' => ['index', 'edit', ...],
     *     ],
     *     ...
     * ]
     *
     * @param array|string $plugins List of plugins to check for controllers and actions.
     * @return array<string, array<string, array<string>>> Structured list of plugins, controllers, and actions.
     */
    public function getResources(string|array $plugins): array
    {
        $plugins = is_string($plugins) ? [$plugins] : $plugins;
        $resourceTree = [];

        foreach ($plugins as $pluginName) {
            $path = App::classPath('Controller/Admin', $pluginName === 'System' ? null : $pluginName)[0];

            if (!is_dir($path)) {
                continue;
            }

            $controllers = array_diff(
                scandir($path),
                ['.', '..', 'AppController.php', 'ErrorController.php', 'UsersController.php', 'RolesController.php'],
            );

            foreach ($controllers as $file) {
                if (is_dir($path . $file)) {
                    continue;
                }

                $position = strrpos($file, '.');
                $baseName = $position !== false ? substr($file, 0, $position) : $file;
                $controller = substr($baseName, 0, strlen($baseName) - 10);
                $className = App::className(
                    $pluginName === 'System' ? $controller : "{$pluginName}.{$controller}",
                    'Controller/Admin',
                    'Controller',
                );

                if ($className === null) {
                        continue;
                }

                $reflection = new ReflectionClass($className);
                $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                $actions = [];

                foreach ($methods as $method) {
                    if (
                        $method->class === $reflection->getName() &&
                        !in_array($method->name, ['initialize', 'beforeFilter', 'beforeRender', 'afterFilter'])
                    ) {
                        $actions[] = $method->name;
                    }
                }

                $resourceTree[$pluginName][$controller] = $actions;
            }
        }

        return $resourceTree;
    }

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
            $path = $this->getPath('View/Cell', $pluginName);

            if (!$path) {
                continue;
            }

            $files = $this->getPhpFiles($path, 'Cell.php', ['BlockCell.php']);

            foreach ($files as $file) {
                $cell = substr(pathinfo($file, PATHINFO_FILENAME), 0, -4);
                $pluginAndCell = $pluginName ? "{$pluginName}.{$cell}" : $cell;
                $className = App::className($pluginAndCell, 'View/Cell', 'Cell');

                if (!$className || !class_exists($className)) {
                    continue;
                }

                foreach ($this->getDeclaredPublicMethods($className, ['initialize']) as $method) {
                    $docBlock = $this->parseDocBlock($method);
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
            $path = $this->getPath('Controller', $plugin);

            if (!$path) {
                continue;
            }

            $files = $this->getPhpFiles($path, 'Controller.php', ['AppController.php']);

            foreach ($files as $file) {
                $controller = substr(pathinfo($file, PATHINFO_FILENAME), 0, -10);
                $className = App::className("{$plugin}.{$controller}", 'Controller', 'Controller');

                if (!$className || !class_exists($className)) {
                    continue;
                }

                foreach ($this->getDeclaredPublicMethods($className, ['initialize', 'beforeFilter', 'beforeRender', 'afterFilter']) as $method) {
                    $docBlock = $this->parseDocBlock($method);
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
            $path = $this->getPath('Controller/Admin', $plugin);

            if (!$path) {
                continue;
            }

            $files = $this->getPhpFiles($path, 'Controller.php', ['AppController.php', 'ErrorController.php']);

            foreach ($files as $file) {
                $controller = substr(pathinfo($file, PATHINFO_FILENAME), 0, -10);
                $className = App::className("{$plugin}.{$controller}", 'Controller/Admin', 'Controller');

                if (!$className || !class_exists($className)) {
                    continue;
                }

                foreach ($this->getDeclaredPublicMethods($className, ['initialize', 'beforeFilter', 'beforeRender', 'afterFilter']) as $method) {
                    if ($method->name !== 'index' && $method->getNumberOfParameters() !== 0) {
                        continue;
                    }

                    $docBlock = $this->parseDocBlock($method);

                    $data[$plugin][] = [
                        'summary' => $docBlock->getSummary(),
                        'description' => $docBlock->getDescription(),
                        'url' => Router::url([
                            'plugin' => $plugin,
                            'controller' => $controller,
                            'action' => $method->name,
                        ]),
                        'target' => '_self',
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Returns the filesystem path for a given namespace and optional plugin.
     *
     * @param string $namespace The namespace to resolve (e.g., 'Controller', 'View/Cell').
     * @param string|null $plugin The plugin name or null for the app namespace.
     * @return string|null The resolved path or null if not found.
     */
    private function getPath(string $namespace, ?string $plugin): ?string
    {
        $paths = App::classPath($namespace, $plugin);

        return $paths[0] ?? null;
    }

    /**
     * Helper method to get PHP files from a directory, excluding specified files and directories.
     *
     * @param string $path The directory path.
     * @param string $suffix The required file suffix (e.g., 'Cell.php', 'Controller.php').
     * @param array<string> $excludeFiles An array of filenames to exclude.
     * @return array<string> An array of matching PHP filenames.
     */
    private function getPhpFiles(string $path, string $suffix, array $excludeFiles = []): array
    {
        if (!is_dir($path)) {
            return [];
        }

        return array_filter(
            array_diff(scandir($path), array_merge(['.', '..'], $excludeFiles)),
            fn($file) => is_file($path . $file) && str_ends_with($file, $suffix),
        );
    }

    /**
     * Helper method to get public methods of a class, excluding common and specified methods.
     *
     * @param string $className The class name to reflect on.
     * @param array<string> $excludedMethods An array of method names to exclude.
     * @return array<\ReflectionMethod>
     */
    private function getDeclaredPublicMethods(string $className, array $excludedMethods): array
    {
        /** @var class-string $className */
        $reflection = new ReflectionClass($className);

        return array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn($method) => $method->getDeclaringClass()->getName() === $className && !in_array($method->name, $excludedMethods),
        );
    }

    /**
     * Helper method to parse DocBlock comments.
     *
     * @param \ReflectionMethod $method The reflection method to parse.
     * @return \App\Lib\DocBlockParser
     */
    private function parseDocBlock(ReflectionMethod $method): DocBlockParser
    {
        $docComment = $method->getDocComment();

        return new DocBlockParser($docComment ?: null);
    }
}
