<?php
declare(strict_types=1);

namespace App\Lib;

use DirectoryIterator;
use ReflectionClass;
use ReflectionMethod;

class ResourcesExplorer
{
    /**
     * Common controller methods that should not be included in the action list.
     *
     * @var array<string>
     */
    private const COMMON_CONTROLLER_METHODS = ['initialize', 'beforeFilter', 'beforeRender', 'afterFilter'];

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
            $path = $this->resolvePath($pluginName, 'Controller/Admin');
            if (!$path) {
                continue;
            }

            $files = $this->getPhpFiles($path, 'Controller.php', ['AppController.php', 'ErrorController.php']);
            $controllers = [];

            foreach ($files as $file) {
                $controller = substr(pathinfo($file, PATHINFO_FILENAME), 0, -10);
                $fqcn = $this->fqcn($pluginName, "Controller\\Admin\\{$controller}Controller");

                if (!class_exists($fqcn)) {
                    continue;
                }

                $methods = $this->getDeclaredPublicMethods($fqcn, self::COMMON_CONTROLLER_METHODS);
                $controllers[$controller] = array_map(fn($m) => $m->name, $methods);
            }

            if ($controllers) {
                $resourceTree[$pluginName] = $controllers;
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
            $path = $this->resolvePath($plugin, 'View/Cell');
            if (!$path) {
                continue;
            }

            $files = $this->getPhpFiles($path, 'Cell.php', ['BlockCell.php']);

            foreach ($files as $file) {
                $cell = substr(pathinfo($file, PATHINFO_FILENAME), 0, -4);
                $fqcn = $this->fqcn($plugin, "View\\Cell\\{$cell}Cell");

                if (!class_exists($fqcn)) {
                    continue;
                }

                foreach ($this->getDeclaredPublicMethods($fqcn, ['initialize']) as $method) {
                    $docComment = $method->getDocComment();
                    if (!$docComment) {
                        continue;
                    }
                    $docBlock = $this->parseDocBlock($docComment);
                    $data[$plugin][] = [
                        'summary' => $docBlock->getSummary(),
                        'description' => $docBlock->getDescription(),
                        'path' => $method->name === 'display'
                            ? ($plugin != 'System' ? "{$plugin}.{$cell}" : $cell)
                            : ($plugin != 'System' ? "{$plugin}.{$cell}::{$method->name}" : "{$cell}::{$method->name}"),
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
            $path = $this->resolvePath($plugin, 'Controller');
            if (!$path) {
                continue;
            }

            $files = $this->getPhpFiles($path, 'Controller.php', ['AppController.php']);

            foreach ($files as $file) {
                $controller = substr(pathinfo($file, PATHINFO_FILENAME), 0, -10);
                $fqcn = $this->fqcn($plugin, "Controller\\{$controller}Controller");

                if (!class_exists($fqcn)) {
                    continue;
                }

                foreach ($this->getDeclaredPublicMethods($fqcn, self::COMMON_CONTROLLER_METHODS) as $method) {
                    $docComment = $method->getDocComment();
                    if (!$docComment) {
                        continue;
                    }
                    $docBlock = $this->parseDocBlock($docComment);

                    $menuTag = $docBlock->getTag('menu');

                    if ($menuTag === null) {
                        continue;
                    }

                    $paramCount = $method->getNumberOfParameters();
                    $adminController = is_array($menuTag)
                        ? trim((string)reset($menuTag))
                        : trim((string)$menuTag);

                    $isModal = $paramCount === 1;
                    $controllerName = $isModal ? ($adminController ?: $controller) : $controller;

                    $data[$plugin][] = [
                        'summary' => $docBlock->getSummary(),
                        'description' => $docBlock->getDescription(),
                        'url' => [
                            'plugin' => $plugin,
                            'prefix' => $isModal ? 'Admin' : false,
                            'controller' => $controllerName,
                            'action' => $isModal ? 'index' : $method->name,
                        ],
                        'target' => $isModal ? '_blank' : '_self',
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
            $path = $this->resolvePath($plugin, 'Controller/Admin');
            if (!$path) {
                continue;
            }

            $files = $this->getPhpFiles($path, 'Controller.php', ['AppController.php', 'ErrorController.php']);

            foreach ($files as $file) {
                $controller = substr(pathinfo($file, PATHINFO_FILENAME), 0, -10);
                $fqcn = $this->fqcn($plugin, "Controller\\Admin\\{$controller}Controller");

                if (!class_exists($fqcn)) {
                    continue;
                }

                $isDashboard = $controller === 'Dashboard';
                $allowedMethods = $isDashboard ? ['index', 'settings'] : ['index', 'add'];
                $actions = [];

                foreach ($this->getDeclaredPublicMethods($fqcn, self::COMMON_CONTROLLER_METHODS) as $method) {
                    if (
                        !in_array($method->name, $allowedMethods, true) ||
                        $method->getNumberOfRequiredParameters() !== 0
                    ) {
                        continue;
                    }

                    $actions[] = $method->name;
                }

                if (!$actions) {
                    continue;
                }

                $data[$plugin][] = [
                    'controller' => $controller,
                    'actions' => $actions,
                    'url' => [
                        'plugin' => $plugin,
                        'controller' => $controller,
                    ],
                    'target' => '_self',
                ];
            }
            if (isset($data[$plugin])) {
                usort($data[$plugin], function ($a, $b) {
                    if ($a['controller'] === 'Dashboard') {
                        return -1;
                    }
                    if ($b['controller'] === 'Dashboard') {
                        return 1;
                    }

                    return strcmp((string)$a['controller'], (string)$b['controller']);
                });
            }
        }

        return $data;
    }

    /**
     * Resolves the absolute filesystem path for a given plugin and subpath.
     *
     * For the main application (plugin = 'System'), this points to the 'src' directory under ROOT.
     * For plugins, this points to the plugin's 'src' directory under ROOT/plugins/{PluginName}/src.
     *
     * The provided subpath is appended to the base directory and directory separators are normalized.
     *
     * Example:
     *  - resolvePath('System', 'Controller/Admin')
     *    => /path/to/app/src/Controller/Admin (if it exists)
     *  - resolvePath('MyPlugin', 'Controller/Admin')
     *    => /path/to/app/plugins/MyPlugin/src/Controller/Admin (if it exists)
     *
     * @param string $plugin The plugin name, or 'System' for the main application.
     * @param string $subpath The subdirectory path relative to 'src', with '/' separators.
     * @return string|null The full directory path if it exists, or null if not found.
     */
    private function resolvePath(string $plugin, string $subpath): ?string
    {
        if ($plugin === 'System') {
            $base = ROOT . '/src/';
        } else {
            $base = ROOT . "/plugins/{$plugin}/src/";
        }

        $full = $base . str_replace('/', DIRECTORY_SEPARATOR, $subpath);

        return is_dir($full) ? $full : null;
    }

    /**
     * Builds the fully qualified class name (FQCN) for a given plugin and relative class path.
     *
     * Converts a relative class path (using slashes) into a proper PHP namespaced class name.
     * Uses 'App' as the base namespace for the main application (plugin 'System'),
     * or the plugin name as the base namespace for plugins.
     *
     * Examples:
     *  - fqcn('System', 'Controller/Admin/Users') => 'App\Controller\Admin\Users'
     *  - fqcn('MyPlugin', 'Controller/Admin/Users') => 'MyPlugin\Controller\Admin\Users'
     *
     * @param string $plugin The plugin name, or 'System' for the main application.
     * @param string $relativeClass Relative class path with '/' separators (e.g. 'Controller/Admin/Users').
     * @return string The fully qualified class name.
     */
    private function fqcn(string $plugin, string $relativeClass): string
    {
        $base = $plugin === 'System' ? 'App' : $plugin;

        return $base . '\\' . str_replace('/', '\\', $relativeClass);
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

        $files = [];
        foreach (new DirectoryIterator($path) as $fileInfo) {
            $filename = $fileInfo->getFilename();
            if (
                $fileInfo->isDot() ||
                !$fileInfo->isFile() ||
                !str_ends_with($filename, $suffix) ||
                in_array($filename, $excludeFiles, true)
            ) {
                continue;
            }

            $files[] = $filename;
        }

        return $files;
    }

    /**
     * Helper method to get public methods of a class, excluding common and specified methods.
     *
     * @param class-string $className The class name to reflect on.
     * @param array<string> $excludedMethods An array of method names to exclude.
     * @return array<\ReflectionMethod>
     */
    private function getDeclaredPublicMethods(string $className, array $excludedMethods): array
    {
        $reflection = new ReflectionClass($className);

        return array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn($method) => $method->getDeclaringClass()->getName() === $className &&
                !in_array($method->name, $excludedMethods),
        );
    }

    /**
     * Helper method to parse DocBlock comments.
     *
     * @param string $docblock DocBlock comment.
     * @return \App\Lib\DocBlockParser
     */
    private function parseDocBlock(string $docblock): DocBlockParser
    {
        return new DocBlockParser($docblock);
    }
}
