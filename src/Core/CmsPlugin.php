<?php
declare(strict_types=1);

namespace App\Core;

use App\Application;
use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Routing\RouteBuilder;
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
    protected string $alias;

    /**
     * Constructor for the plugin.
     *
     * @param array $options Array of options/configuration.
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->alias = $options['alias'] ?? null;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function bootstrap(PluginApplicationInterface $app): void
    {
        $this->app = $app;

        parent::bootstrap($app);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function routes(RouteBuilder $routes): void
    {
        $path = $this->getConfigPath() . 'routes.php';
        if (is_file($path)) {
            $return = require $path;
            if (is_callable($return)) {
                $languages = $this->app->getConfig('App.languages');
                if ($languages) {
                    foreach ($languages as $i => $lang) {
                        if ($i !== 0) {
                            $routes->plugin($this->name, ['path' => ('/' . $lang . '/' . $this->alias), 'lang' => $lang], $return($routes));
                        }
                    }
                }
                $routes->plugin($this->name, ['path' => '/' . $this->alias], $return($routes));
            }
        }
    }
}
