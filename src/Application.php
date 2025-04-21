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
 * @since     3.3.0
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace App;

use App\Core\Configure\Engine\DbConfig;
use App\Middleware\MaintenanceMiddleware;
use App\Policy\RequestPolicy;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\AuthenticationServiceInterface;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\AuthorizationServiceInterface;
use Authorization\Policy\ResolverCollection;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Middleware\RequestAuthorizationMiddleware;
use Authorization\Policy\OrmResolver;
use Authorization\Policy\MapResolver;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Core\Exception\MissingPluginException;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\ServerRequest;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Router;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication implements AuthenticationServiceProviderInterface, AuthorizationServiceProviderInterface
{

    public function getConfig(?string $var = null, $default = null)
    {
        if ($var === null) {
            return Configure::read();
        }

        return Configure::read($var, $default);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        if (PHP_SAPI !== 'cli') {
            FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(true));
        }

        Cache::setConfig('cms', [
            'className' => 'File',
            'prefix' => 'cms_',
            'path' => CACHE . 'cms' . DS,
            'duration' => '+999 days'
        ]);
        try {
            Configure::config('db', new DbConfig(null, 'cms'));
            Configure::load('Cms', 'db');

            $this->_loadTheme();
            $this->_loadPlugins();
        } catch (\Exception $e) {
            
        }
    }

    #[\Override]
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $loginUrl = Router::url([
            'plugin' => null,
            'prefix' => 'Admin',
            'controller' => 'Users',
            'action' => 'login',
        ]);

        $service = new AuthenticationService([
            'unauthenticatedRedirect' => $loginUrl,
            'queryParam' => 'redirect',
        ]);

        $fields = [
            AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
            AbstractIdentifier::CREDENTIAL_PASSWORD => 'password'
        ];

        // Put form authentication first so that users can re-login via the login form if necessary.
        $service->loadAuthenticator('Authentication.Form', [
            'fields' => $fields,
            'loginUrl' => $loginUrl
        ]);

        // Then use sessions if they are active.
        $service->loadAuthenticator('Authentication.Session', [
            'sessionKey' => 'Auth.User',
        ]);

        // If the user is on the login page, check for a cookie as well.
        $service->loadAuthenticator('Authentication.Cookie', [
            'fields' => $fields,
            'loginUrl' => $loginUrl
        ]);

        // Load identifiers
        $service->loadIdentifier('Authentication.Password', compact('fields'));

        return $service;
    }

    #[\Override]
    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        $orm = new OrmResolver();
        $map = new MapResolver();
        $map->map(ServerRequest::class, RequestPolicy::class);

        return new AuthorizationService(new ResolverCollection([$map, $orm]));
    }

    /**
     * Define the routes for an application.
     *
     * Use the provided RouteBuilder to define an application's routing, register scoped middleware.
     *
     * @param \Cake\Routing\RouteBuilder $routes A route builder to add routes into.
     * @return void
     */
    #[\Override]
    public function routes(RouteBuilder $routes): void
    {
        $routes->setRouteClass(DashedRoute::class);
        // Register scoped middleware for use in routes.php
        $routes->registerMiddleware('authentication', new AuthenticationMiddleware($this));
        $routes->registerMiddleware('authorization', new AuthorizationMiddleware($this, [
                    'identityDecorator' => function ($auth, $user) {
                        return $user->setAuthorization($auth);
                    },
                    'unauthorizedHandler' => [
                        'className' => 'RefererRedirect'
                    ]
        ]));
        $routes->registerMiddleware('request_authorization', new RequestAuthorizationMiddleware());
        $routes->middlewareGroup('auth', ['authentication', 'authorization', 'request_authorization']);

        $routes->scope('/', ['controller' => 'Index'], function (RouteBuilder $builder) {
            $languages = $this->getConfig('I18n.languages');
            if ($languages) {
                foreach ($languages as $i => $lang) {
                    if ($i !== 0) {
                        $builder->scope('/' . $lang, ['lang' => $lang], function (RouteBuilder $builder) {
                            $builder->connect('/', ['action' => 'index']);
                        });
                    }
                }
            }

            $builder->connect('/', ['action' => 'index']);
        });

        $routes->prefix('Admin', function (RouteBuilder $builder) {
            $builder->applyMiddleware('auth');
            $defaultDashboard = $this->getConfig('Cms.defaultDashboard');
            $plugin = $defaultDashboard == 'System' ? null : $defaultDashboard;
            $builder->connect('/', ['plugin' => $plugin, 'controller' => 'Dashboard']);
            $builder->fallbacks(DashedRoute::class);
        });

        Router::addUrlFilter(function ($params, $request) {
            if ($request->getParam('lang') && !isset($params['lang'])) {
                $params['lang'] = $request->getParam('lang');
            }
            return $params;
        });
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    #[\Override]
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue

                // Maintenance middleware
                ->add(new MaintenanceMiddleware($this->getConfig('Cms.maintenance')))

                // Catch any exceptions in the lower layers,
                // and make an error page/response
                ->add(new ErrorHandlerMiddleware($this->getConfig('Error'), $this))

                // Handle plugin/theme assets like CakePHP normally does.
                ->add(new AssetMiddleware(['cacheTime' => $this->getConfig('Asset.cacheTime')]))

                // Add routing middleware.
                // If you have a large number of routes connected, turning on routes
                // caching in production could improve performance.
                // See https://github.com/CakeDC/cakephp-cached-routing
                ->add(new RoutingMiddleware($this))

                // Parse various types of encoded request bodies so that they are
                // available as array through $request->getData()
                // https://book.cakephp.org/5/en/controllers/middleware.html#body-parser-middleware
                ->add(new BodyParserMiddleware())

                // Cross Site Request Forgery (CSRF) Protection Middleware
                // https://book.cakephp.org/5/en/security/csrf.html#cross-site-request-forgery-csrf-middleware
                ->add(new CsrfProtectionMiddleware(['httponly' => true]));

        return $middlewareQueue;
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/4/en/development/dependency-injection.html#dependency-injection
     */
    #[\Override]
    public function services(ContainerInterface $container): void
    {
        
    }

    private function _loadTheme()
    {
        $theme = $this->getConfig('Cms.theme');
        try {
            if ($theme) {
                $this->addPlugin($theme);
            }
        } catch (MissingPluginException $e) {
            // Do not halt if the plugin is missing
        }
    }

    private function _loadPlugins()
    {
        $table = FactoryLocator::get('Table')->get('Plugins');
        $plugins = $table->find()->where(['enabled' => true])->cache('plugins', 'cms')->toArray();
        foreach ($plugins as $plugin) {
            $this->addPlugin($plugin->name, ['alias' => $plugin->alias]);
        }
    }
}
