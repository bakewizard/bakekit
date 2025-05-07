<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\I18n\I18n;
use Cake\Routing\Router;
use Cake\View\JsonView;
use Override;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * Breadcrumbs array.
     *
     * @var array<array<string, mixed>>
     */
    private array $_breadcrumbs = [];

    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(): void
    {
        $this->loadComponent('Flash');
    }

    /**
     * {@inheritDoc}
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The beforeFilter event.
     * @return \Cake\Http\Response|null|void May return a {@see \Cake\Http\Response} early or void to continue normally.
     */
    #[Override]
    public function beforeFilter(EventInterface $event)
    {
        $this->setLocale();

        $this->setMeta();

        $this->set('config', $this->getConfig());
    }

    /**
     * {@inheritDoc}
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event The beforeRender event.
     * @return \Cake\Http\Response|null|void May return a {@see \Cake\Http\Response} early or void to continue normally.
     */
    #[Override]
    public function beforeRender(EventInterface $event)
    {
        $this->viewBuilder()->setTheme($this->getConfig('Cms.theme'));

        $this->set('breadcrumbs', $this->_breadcrumbs);
    }

    /**
     * Returns the view classes this controller can use.
     *
     * @return array<string> An array containing the view class names.
     */
    #[Override]
    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    /**
     * Get a configuration value.
     *
     * @param string|null $var The configuration key to retrieve. If null, all configuration values are returned.
     * @param mixed $default The default value to return if the configuration key does not exist.
     * @return mixed The configuration value, or the default value if the key does not exist.
     */
    public function getConfig(?string $var = null, mixed $default = null)
    {
        if ($var === null) {
            return Configure::read();
        }

        return Configure::read($var, $default);
    }

    /**
     * Adds a crumb to the breadcrumbs array.
     *
     * @param string $title The title of the breadcrumb.
     * @param array<mixed, mixed>|string|null $url The URL of the breadcrumb. If null, it will not be a link.
     * @return void
     */
    protected function addCrumb(string $title, array|string|null $url = null): void
    {
        $this->_breadcrumbs[] = ['title' => $title, 'url' => Router::url($url, true)];
    }

    /**
     * Sets meta information for specific actions.
     *
     * Currently, it fetches meta data for the 'index' action based on the plugin name.
     *
     * @return void
     */
    private function setMeta(): void
    {
        $action = $this->request->getParam('action');
        if ($action === 'index') {
            $plugin = $this->request->getParam('plugin', 'Pages');
            $meta = $this->fetchTable('Meta')->find()->contain(['Plugins'])->where(['name' => $plugin])->first();
            if (!empty($meta)) {
                $this->set(compact('meta'));
            }
        }
    }

    /**
     * Sets the locale for the current request.
     *
     * It determines the locale based on the 'lang' parameter in the request,
     * falling back to the default language configured in the application.
     * It also sets configuration values related to internationalization.
     *
     * @return void
     */
    private function setLocale(): void
    {
        $languages = $this->getConfig('App.languages');
        $defaultLanguage = explode('_', I18n::getDefaultLocale())[0] ?? 'en';
        $currentLanguage = $this->request->getAttribute('params')['lang'] ?? null;

        if (isset($languages)) {
            $key = array_search($defaultLanguage, $languages);
            if ($key !== false) {
                unset($languages[$key]);
            }
            array_unshift($languages, $defaultLanguage);
        } else {
            $languages = [$defaultLanguage];
        }

        if ($currentLanguage) {
            I18n::setLocale($currentLanguage);
        } else {
            $currentLanguage = $defaultLanguage;
        }

        Configure::write('App.I18n', [
            'defaultLanguage' => $defaultLanguage,
            'currentLanguage' => $currentLanguage,
            'languages' => $languages,
        ]);
    }
}
