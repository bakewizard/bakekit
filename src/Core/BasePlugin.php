<?php
declare(strict_types=1);

namespace App\Core;

use App\Application;
use Cake\Core\BasePlugin as CakeBasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\Routing\RouteBuilder;
use Cake\Utility\Inflector;
use Closure;
use Override;

abstract class BasePlugin extends CakeBasePlugin
{
    /**
     * Application instance
     *
     * @var \App\Application
     */
    protected ?Application $app = null;

    /**
     * The alias of this plugin
     *
     * @var string
     */
    protected string $alias;

    /**
     * Constructor for the plugin.
     *
     * @param array<string, mixed> $options Array of options/configuration.
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->alias = $options['alias'] ?? Inflector::dasherize($this->getName());
    }

    /**
     * @param \Cake\Core\PluginApplicationInterface<\App\Application> $app The application instance.
     */
    #[Override]
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        if ($app instanceof Application) {
            $this->app = $app;
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function routes(RouteBuilder $routes): void
    {
        if (!is_string($this->name)) {
            return;
        }

        $routesConfig = $this->getConfigPath() . 'routes.php';
        if (!is_file($routesConfig)) {
            return;
        }

        $loaded = require $routesConfig;
        if (!is_callable($loaded)) {
            return;
        }

        $callback = Closure::fromCallable($loaded);

        $languages = Configure::read('App.languages') ?? [];
        $pluginPath = '/' . $this->alias;

        // Register routes for each language, except the default one
        foreach ($languages as $i => $lang) {
            // Skip the default language — it's handled by the base route below
            if ($i !== 0) {
                $routes->plugin($this->name, ['path' => "/{$lang}{$pluginPath}", 'lang' => $lang], $callback);
            }
        }

        // Base route without language
        $routes->plugin($this->name, ['path' => $pluginPath], $callback);
    }
}
