<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Lib\PluginManager;

class DashboardController extends AppController
{

    public function index()
    {
        
    }

    public function info()
    {
        ob_start();
        phpinfo();

        return new \Cake\Http\Response(['body' => ob_get_clean()]);
    }

    /**
     * Cms settings
     * 
     * Displays/Sets the Cms settings
     *
     * @return \Cake\Http\Response|null
     */
    #[\Override]
    public function settings()
    {
        parent::settings();

        $pm = new PluginManager();

        $loadedPlugins = $pm->getPlugins(true);
        $plugins = [];

        foreach ($loadedPlugins as $plugin) {
            $plugins[$plugin] = $plugin;
        }

        $this->set(compact('plugins'));
    }
}
