<?php
declare(strict_types=1);

namespace App\Controller\Admin;

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
     * @return \Cake\Http\Response|null|void
     */
    #[Override]
    public function settings()
    {
        parent::settings();

        /** @var \App\Model\Table\PluginsTable $table */
        $table = $this->fetchTable('Plugins');
        $activePlugins = $table->getActivePlugins(true);
        $plugins = [];

        foreach ($activePlugins as $plugin) {
            $plugins[$plugin] = $plugin;
        }

        $this->set(compact('plugins'));
    }
}
