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

namespace App\Controller\Admin;

use App\Event\RegionChangeListener;
use Cake\Cache\Cache;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\Routing\Router;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3/en/controllers.html#the-app-controller
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
        $this->loadComponent('Search.Search');
        $this->loadComponent('Authentication.Authentication', [
            'logoutRedirect' => [
                'plugin' => null,
                'prefix' => 'Admin',
                'controller' => 'Users',
                'action' => 'login'
            ]
        ]);
        $this->loadComponent('Authorization.Authorization');

        $cacheListener = new RegionChangeListener();
        $locator = $this->getTableLocator();
        $locator->get('Regions')->getEventManager()->on($cacheListener);
        $locator->get('Blocks')->getEventManager()->on($cacheListener);

        if ($this->request->is('post') && $this->request->getParam('action') === 'settings') {
            $plugin = $this->getPlugin();
            Cache::delete('settings', isset($plugin) ? strtolower($plugin) : 'cms');
        }
    }

    #[\Override]
    public function beforeFilter(EventInterface $event)
    {
        $this->_setLocale();

        $this->set('config', $this->getConfig());
    }

    #[\Override]
    public function beforeRedirect(EventInterface $event, $url, Response $response)
    {
        $queryParams = $this->request->getQueryParams();

        $redirect = $this->request->getData('redirect');

        if (isset($redirect)) {
            $url = ['action' => $redirect, $this->request->getParam('pass.0')];
        }

        if (array_key_exists('locale', $queryParams)) {
            unset($queryParams['locale']);
        }

        if (is_array($url) && $queryParams) {
            $url['?'] = $queryParams;
        }
        $event->setResult($response->withLocation(Router::url($url, true)));
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
        if ($this->request->is('ajax')) {
            $this->viewBuilder()->setClassName('Ajax');
        } else {
            $this->viewBuilder()->setLayout('admin');

            $plugin = $this->request->getParam('plugin');
            $controller = $this->request->getParam('controller');
            $action = $this->request->getParam('action');

            $params = Router::parseRequest(new ServerRequest(['url' => $this->referer()]));

            if (isset($plugin) && $plugin !== 'Pages' && $controller !== 'Dashboard') {
                $this->addCrumb(preg_replace('/([A-Z])/', " " . '$1', $plugin), ['plugin' => $plugin, 'controller' => 'Dashboard', 'action' => 'index']);
            }

            if ($params['action'] === 'view' && !in_array($action, ['view', 'index'])) {
                $this->addCrumb(preg_replace('/([A-Z])/', " " . '$1', $params['controller']), $params['action'] != 'index' ? ['plugin' => $params['plugin'], 'controller' => $params['controller'], 'action' => 'index'] : null);
                $this->addCrumb(preg_replace('/([A-Z])/', " " . '$1', $controller), ['plugin' => $plugin, 'controller' => $params['controller'], 'action' => $params['action'], $params['pass'][0]]);
            } else if ($action !== 'index') {
                $this->addCrumb(preg_replace('/([A-Z])/', " " . '$1', $controller), ['plugin' => $plugin, 'controller' => $controller, 'action' => 'index']);
            }

            $this->set('breadcrumbs', $this->_breadcrumbs);
        }
    }

    public function deleteMany()
    {
        $this->request->allowMethod(['post', 'delete']);

        $ids = $this->request->getData('ids');

        $params = $this->request->getParam('pass');

        $modelClass = pluginSplit($this->name)[1];

        $model = empty($params) ? $this->{$modelClass} : $this->{$modelClass}->{$params[0]};

        $alias = strtolower(preg_replace('/([A-Z])/', " " . '$1', $model->getAlias()));

        if (empty($ids)) {
            $this->Flash->error(__('You haven\'t selected any of the {0}.', $alias));
            return $this->redirect($this->referer());
        }

        $entities = $model->find()->where(['id IN' => $ids])->toArray();

        if ($model->deleteMany($entities)) {
            $this->Flash->success(__('The {0} has been deleted.', $alias));
        } else {
            $this->Flash->error(__('The {0} could not be deleted. Please, try again.', $alias));
        }

        return $this->redirect($this->referer());
    }

    public function deleteFiles($id)
    {
        $this->request->allowMethod(['post', 'delete']);

        $modelClass = pluginSplit($this->name)[1];

        $entity = $this->{$modelClass}->get($id);

        $this->{$modelClass}->remove($entity->files);

        return $this->redirect($this->referer());
    }

    public function getConfig(?string $var = null, $default = null)
    {
        if ($var === null) {
            return Configure::read();
        }

        return Configure::read($var, $default);
    }

    public function getStorage(string $basePath = WWW_ROOT): Filesystem
    {
        $visibility = PortableVisibilityConverter::fromArray(
                [
                    'file' => ['public' => 0640, 'private' => 0600,],
                    'dir' => ['public' => 0750, 'private' => 0700,],
                ],
                Visibility::PUBLIC
        );

        return new Filesystem(new LocalFilesystemAdapter($basePath, $visibility));
    }

    protected function addCrumb($title, $url = null)
    {
        $this->_breadcrumbs[] = ['title' => $title, 'url' => Router::url($url, true)];
    }

    protected function settings()
    {
        $plugin = $this->getPlugin() ?? 'App';
        $formClass = $plugin . '\Form\ConfigForm';
        $namespace = $plugin === 'App' ? 'Cms' : $plugin;
        $settings = new $formClass;

        if ($this->request->is('post')) {
            if ($settings->execute($this->request->getData())) {
                $this->Flash->success(__('Configuration saved'));
            } else {
                $this->Flash->error(__('There was a problem submitting your form.'));
            }

            return $this->redirect($this->referer());
        }

        if ($this->request->is('get')) {
            $conf = Configure::read($namespace);
            if ($conf) {
                foreach ($settings->getSchema()->fields() as $field) {
                    list($group, $var) = strpos($field, '.') ? explode('.', $field) : [null, $field];
                    $value = null;
                    if ($group && isset($conf[$group][$var])) {
                        $value = $conf[$group][$var];
                    } else if (isset($conf[$var])) {
                        $value = $conf[$var];
                    }
                    $this->setRequest($this->request->withData($field, $value));
                }
            }
        }

        $this->set(compact('settings'));
    }

    private function _setLocale()
    {
        $locale = $this->request->getQuery('locale');
        $modelClass = pluginSplit($this->name)[1];

        $languages = $this->getConfig('I18n.languages');
        $defaultLanguage = explode('_', I18n::getDefaultLocale())[0] ?? 'en';
        $currentLanguage = ($this->request->getAttribute('params'))['lang'] ?? null;

        if ($locale && ($this->{$modelClass}->hasBehavior('Translate'))) {
            $this->{$modelClass}->setLocale($locale);
        }

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
