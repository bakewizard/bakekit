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

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/4/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    private $_breadcrumbs = [];

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    #[\Override]
    public function initialize(): void
    {
        $this->loadComponent('Flash');
    }

    #[\Override]
    public function beforeFilter(EventInterface $event)
    {
        $this->_setLocale();

        $this->_setMeta();

        $this->set('config', $this->getConfig());
    }

    /**
     * Before render callback.
     *
     * @param \Cake\Event\EventInterface $event The beforeRender event.
     * @return void
     */
    #[\Override]
    public function beforeRender(EventInterface $event)
    {
        $this->viewBuilder()->setTheme($this->getConfig('Cms.theme'));

        $this->set('breadcrumbs', $this->_breadcrumbs);
    }

    #[\Override]
    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    public function getConfig(?string $var = null, $default = null)
    {
        if ($var === null) {
            return Configure::read();
        }

        return Configure::read($var, $default);
    }

    protected function addCrumb($title, $url = null)
    {
        $this->_breadcrumbs[] = ['title' => $title, 'url' => Router::url($url, true)];
    }

    private function _setMeta()
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

    private function _setLocale()
    {
        $languages = $this->getConfig('I18n.languages');
        $defaultLanguage = explode('_', I18n::getDefaultLocale())[0] ?? 'en';
        $currentLanguage = ($this->request->getAttribute('params'))['lang'] ?? null;

        if (isset($languages)) {
            if (($key = array_search($defaultLanguage, $languages)) !== false) {
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
            'languages' => $languages
        ]);
    }
}
