<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Lib\PluginManager;
use Cake\Http\Response;
use Override;

/**
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class DashboardController extends AppController
{
    /**
     * Shows dashboard
     *
     * @return void
     */
    public function index()
    {
    }

    /**
     * Shows phpinfo() content
     *
     * @return \Cake\Http\Response
     */
    public function info()
    {
        ob_start();
        phpinfo();

        return new Response(['body' => ob_get_clean()]);
    }

    /**
     * Cms settings
     *
     * Displays/Sets the Cms settings
     *
     * @return \Cake\Http\Response|null
     */
    #[Override]
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
