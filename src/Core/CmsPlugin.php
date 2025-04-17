<?php

declare(strict_types=1);

namespace App\Core;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Routing\RouteBuilder;

class CmsPlugin extends BasePlugin
{

    /**
     * Application instance
     * 
     * @var App\Application
     */
    protected $app;

    /**
     * The alias of this plugin
     *
     * @var string
     */
    protected $alias;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->alias = $options['alias'] ?? null;
    }

    #[\Override]
    public function bootstrap(PluginApplicationInterface $app): void
    {
        $this->app = $app;

        parent::bootstrap($app);
    }

    #[\Override]
    public function routes(RouteBuilder $routes): void
    {
        $path = $this->getConfigPath() . 'routes.php';
        if (is_file($path)) {
            $return = require $path;
            if (is_callable($return)) {
                $languages = $this->app->getConfig('I18n.languages');
                foreach ($languages as $i => $lang) {
                    if ($i !== 0) {
                        $routes->plugin($this->name, ['path' => ('/' . $lang . '/' . $this->alias), 'lang' => $lang], $return($routes));
                    }
                }
                $routes->plugin($this->name, ['path' => '/' . $this->alias], $return($routes));
            }
        }
    }
}
