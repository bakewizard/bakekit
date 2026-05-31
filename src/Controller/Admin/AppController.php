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
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Form\Form;
use Cake\Http\Response;
use Cake\I18n\I18n;
use Cake\Routing\Router;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use Override;
use Psr\Http\Message\UriInterface;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/5/en/controllers.html#the-app-controller
 * @property \Search\Controller\Component\SearchComponent $Search
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class AppController extends Controller
{
    /**
     * Breadcrumbs array
     *
     * @var array<int, array{title: string, url: array<mixed>|string|null}>
     */
    private array $breadcrumbs = [];

    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(): void
    {
        $this->loadComponent('Flash');
        $this->loadComponent('Search.Search');
        $this->loadComponent('Authentication.Authentication', [
            'logoutRedirect' => [
                'plugin' => null,
                'prefix' => 'Admin',
                'controller' => 'Users',
                'action' => 'login',
            ],
        ]);
        $this->loadComponent('Authorization.Authorization');

        $cacheListener = new RegionChangeListener();
        $locator = $this->getTableLocator();
        $locator->get('Regions')->getEventManager()->on($cacheListener);
        $locator->get('Blocks')->getEventManager()->on($cacheListener);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function beforeFilter(EventInterface $event)
    {
        $this->setLocale();

        $this->addCrumb(
            '<i class="fa-solid fa-tachometer-alt"></i>',
            [
                'prefix' => 'Admin',
                'plugin' => null,
                'controller' => 'Dashboard',
                'action' => 'index',
            ],
        );
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function beforeRender(EventInterface $event)
    {
        if ($this->request->is('ajax')) {
            $this->viewBuilder()->setClassName('Ajax');

            return;
        }

        $this->viewBuilder()->setLayout('admin');

        $this->set('breadcrumbs', $this->breadcrumbs);

        $this->set('config', $this->getConfig());
    }

    /**
     * Undocumented function
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event
     * @param \Psr\Http\Message\UriInterface|array<mixed, mixed>|string $url
     * @param \Cake\Http\Response $response
     * @return void
     */
    #[Override]
    public function beforeRedirect(EventInterface $event, UriInterface|array|string $url, Response $response)
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
     * Deletes a list of records
     *
     * @return \Cake\Http\Response|null
     */
    public function deleteMany(): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $ids = $this->request->getData('ids');

        $params = $this->request->getParam('pass');

        $modelClass = pluginSplit($this->name)[1];

        $model = empty($params) ? $this->{$modelClass} : $this->{$modelClass}->{$params[0]};

        $alias = strtolower(preg_replace('/([A-Z])/', ' ' . '$1', $model->getAlias()));

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

    /**
     * Config helper
     *
     * @param string|null $var Variable to obtain. Use '.' to access array elements.
     * @param mixed $default The return value when the configure does not exist
     * @return mixed Value stored in configure, or null.
     */
    public function getConfig(?string $var = null, mixed $default = null)
    {
        if ($var === null) {
            return Configure::read();
        }

        return Configure::read($var, $default);
    }

    /**
     * Get a Flysystem Filesystem instance with custom visibility settings.
     *
     * @param string $basePath The base path for the local filesystem (default is WWW_ROOT).
     * @return \League\Flysystem\Filesystem Configured filesystem instance.
     */
    public function getStorage(string $basePath = WWW_ROOT): Filesystem
    {
        $visibility = PortableVisibilityConverter::fromArray(
            [
                'file' => ['public' => 0640, 'private' => 0600,],
                'dir' => ['public' => 0750, 'private' => 0700,],
            ],
            Visibility::PUBLIC,
        );

        return new Filesystem(new LocalFilesystemAdapter($basePath, $visibility));
    }

    /**
     * Adds a breadcrumb item to the breadcrumb trail.
     *
     * @param string $title The title of the breadcrumb.
     *
     * @param array<mixed, mixed>|string|null $url The URL of the breadcrumb. If null, it will not be a link.
     * @return void
     */
    protected function addCrumb(string $title, string|array|null $url = null)
    {
        //Router::url($url, true)
        $this->breadcrumbs[] = ['title' => $title, 'url' => $url];
    }

    /**
     * Handles configuration form
     *
     * @throws \LogicException
     * @return \Cake\Http\Response|null|void
     */
    protected function settings()
    {
        $plugin = $this->getPlugin() ?? 'App';
        $formClass = $plugin . '\Form\ConfigForm';
        $namespace = $plugin === 'App' ? 'System' : $plugin;
        $settings = new $formClass();

        if (!$settings instanceof Form) {
            $this->Flash->error(__('Expected an instance of {0}, got {1}', Form::class, get_class($settings)));

            return $this->redirect($this->referer());
        }

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
                    [$group, $var] = strpos($field, '.') ? explode('.', $field) : [null, $field];
                    $value = null;
                    if ($group && isset($conf[$group][$var])) {
                        $value = $conf[$group][$var];
                    } elseif (isset($conf[$var])) {
                        $value = $conf[$var];
                    }
                    $this->setRequest($this->request->withData($field, $value));
                }
            }
        }

        $this->set(compact('settings'));
    }

    /**
     * Sets the application locale and language configuration.
     *
     * @return void
     */
    private function setLocale()
    {
        $locale = $this->request->getQuery('locale');
        $modelClass = pluginSplit($this->name)[1];

        $languages = $this->getConfig('App.languages');
        $defaultLanguage = explode('_', I18n::getDefaultLocale())[0] ?? 'en';
        $currentLanguage = $this->request->getAttribute('params')['lang'] ?? null;

        if ($locale && ($this->{$modelClass}->hasBehavior('Translate'))) {
            $behavior = $this->{$modelClass}->getBehavior('Translate');
            $behavior->setLocale($locale);
        }

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
