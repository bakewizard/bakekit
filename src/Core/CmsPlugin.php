<?php
declare(strict_types=1);

namespace App\Core;

use App\Application;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\Routing\RouteBuilder;
use Closure;
use Override;

class CmsPlugin extends BasePlugin
{
    /**
     * Application instance
     *
     * @var \App\Application
     */
    protected Application $app;

    /**
     * The alias of this plugin
     *
     * @var string
     */
    protected ?string $alias;

    /**
     * Constructor for the plugin.
     *
     * @param array<string, mixed> $options Array of options/configuration.
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->alias = $options['alias'] ?? null;
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

        // Multilingual paths
        foreach ($languages as $i => $lang) {
            if ($i !== 0) {
                $routes->plugin($this->name, ['path' => "/{$lang}{$pluginPath}", 'lang' => $lang], $callback);
            }
        }

        // Base route without language
        $routes->plugin($this->name, ['path' => $pluginPath], $callback);
    }
}
