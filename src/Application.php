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

use App\Command\InstallCommand;
use App\Core\Configure\Engine\DbConfig;
use App\Lib\ComposerManager;
use App\Lib\ExtensionHandler;
use App\Lib\PluginManager;
use App\Lib\ResourcesExplorer;
use App\Lib\ThemeManager;
use App\Middleware\HostHeaderMiddleware;
use App\Middleware\MaintenanceMiddleware;
use App\Model\Table\ResourcesTable;
use App\Model\Table\SettingsTable;
use App\Policy\RequestPolicy;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Middleware\RequestAuthorizationMiddleware;
use Authorization\Policy\MapResolver;
use Authorization\Policy\OrmResolver;
use Authorization\Policy\ResolverCollection;
use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Core\Exception\MissingPluginException;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\ORM\Locator\TableContainer;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Exception;
use Migrations\Migrations;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 *
 * @extends \Cake\Http\BaseApplication<\App\Application>
 */
class Application extends BaseApplication implements AuthenticationServiceProviderInterface, AuthorizationServiceProviderInterface
{
    /**
     * Table locator instance
     *
     * @var \Cake\ORM\Locator\TableLocator
     */
    private TableLocator $tableLocator;

    /**
     * Config helper
     *
     * @param string|null $var Variable to obtain. Use '.' to access array elements.
     * @param mixed $default The return value when the configure does not exist
     * @return mixed Value stored in configure, or null.
     */
    public function getConfig(?string $var = null, mixed $default = null): mixed
    {
        if ($var === null) {
            return Configure::read();
        }

        return Configure::read($var, $default);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        $this->tableLocator = new TableLocator();

        if (PHP_SAPI !== 'cli') {
            FactoryLocator::add('Table', $this->tableLocator->allowFallbackClass(true)); // @phpstan-ignore argument.type
        }

        // Provide default cache configs, unless overridden in app_local.php
        if (!Cache::getConfig('menus')) {
            Cache::setConfig('menus', [
                'className' => FileEngine::class,
                'path' => CACHE . 'cms' . DS . 'menus' . DS,
                'duration' => '+1 years',
                'prefix' => 'menu_',
                'serialize' => true,
            ]);
        }

        if (!Cache::getConfig('permissions')) {
            Cache::setConfig('permissions', [
                'className' => FileEngine::class,
                'path' => CACHE . 'cms' . DS . 'permissions' . DS,
                'duration' => '+1 years',
                'prefix' => 'perm_',
                'serialize' => true,
            ]);
        }

        if (!Cache::getConfig('cms')) {
            Cache::setConfig('cms', [
                'className' => FileEngine::class,
                'path' => CACHE . 'cms' . DS,
                'duration' => '+1 years',
                'prefix' => null,
                'serialize' => true,
            ]);
        }
        try {
            Configure::config('db', new DbConfig($this->tableLocator->get('Settings'), 'cms'));
            Configure::load('Cms', 'db');

            $this->loadTheme();
            $this->loadPlugins();

            TypeFactory::map('textandjson', 'App\Database\Type\TextAndJsonType');
        } catch (MissingPluginException $e) {
            Log::warning($e->getMessage());
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * Returns an authentication service instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request
     * @return \Authentication\AuthenticationServiceInterface
     */
    #[Override]
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
            AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
        ];

        $passwordIdentifier = [
            'Authentication.Password' => [
                'fields' => $fields,
            ],
        ];

        // Put form authentication first so that users can re-login via the login form if necessary.
        $service->loadAuthenticator('Authentication.Form', [
            'identifier' => $passwordIdentifier,
            'fields' => $fields,
            'loginUrl' => $loginUrl,
        ]);

        // Then use sessions if they are active.
        $service->loadAuthenticator('Authentication.Session', [
            'identifier' => $passwordIdentifier,
            'sessionKey' => 'Auth.User',
        ]);

        // If the user is on the login page, check for a cookie as well.
        $service->loadAuthenticator('Authentication.Cookie', [
            'identifier' => $passwordIdentifier,
            'fields' => $fields,
            'loginUrl' => $loginUrl,
        ]);

        return $service;
    }

    /**
     * Returns an authorization service instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request
     * @return \Authorization\AuthorizationServiceInterface
     */
    #[Override]
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
    #[Override]
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
                'className' => 'RefererRedirect',
            ],
        ]));
        $routes->registerMiddleware('request_authorization', new RequestAuthorizationMiddleware());
        $routes->middlewareGroup('auth', ['authentication', 'authorization', 'request_authorization']);

        $routes->scope('/', ['controller' => 'Index'], function (RouteBuilder $builder): void {
            $languages = $this->getConfig('App.languages');
            if ($languages) {
                foreach ($languages as $i => $lang) {
                    if ($i !== 0) {
                        $builder->scope('/' . $lang, ['lang' => $lang], function (RouteBuilder $builder): void {
                            $builder->connect('/', ['action' => 'index']);
                        });
                    }
                }
            }

            $builder->connect('/', ['action' => 'index']);
        });

        $routes->prefix('Admin', function (RouteBuilder $builder): void {
            $builder->applyMiddleware('auth');
            $defaultDashboard = $this->getConfig('Cms.defaultDashboard');
            $plugin = $defaultDashboard == 'System' ? null : $defaultDashboard;
            $builder->connect('/', ['plugin' => $plugin, 'controller' => 'Dashboard']);
            $builder->fallbacks(DashedRoute::class);
        });

        Router::addUrlFilter(function ($params, $request) {
            if ($request && $request->getParam('lang') && !isset($params['lang'])) {
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
    #[Override]
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue

            // Maintenance middleware
            ->add(new MaintenanceMiddleware((array)$this->getConfig('Cms.maintenance', [])))

            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware($this->getConfig('Error'), $this))

            // Validate Host header to prevent Host Header Injection attacks.
            // In production, ensures App.fullBaseUrl is configured and validates
            // the incoming Host header against it.
            ->add(new HostHeaderMiddleware())

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
     * @link https://book.cakephp.org/5/en/development/dependency-injection.html#dependency-injection
     */
    #[Override]
    public function services(ContainerInterface $container): void
    {
        $container->delegate(new TableContainer());

        $container->add(ComposerManager::class);
        $container->add(ResourcesExplorer::class);
        $container->add(ExtensionHandler::class);
        $container->add(Migrations::class);

        $container->add(ThemeManager::class)
            ->addArgument(ExtensionHandler::class);

        $container->add(PluginManager::class)
            ->addArgument(ExtensionHandler::class)
            ->addArgument(ResourcesExplorer::class)
            ->addArgument(Migrations::class)
            ->addArgument(SettingsTable::class)
            ->addArgument(ResourcesTable::class);

        $container->add(InstallCommand::class)
            ->addArgument(PluginManager::class);
    }

    /**
     * Loads a theme
     *
     * @return void
     */
    private function loadTheme(): void
    {
        $theme = $this->getConfig('Cms.theme');
        if ($theme) {
            $this->addPlugin($theme, [
                'path' => ROOT . DS . 'themes' . DS . $theme . DS,
            ]);
        }
    }

    /**
     * Loads plugins
     *
     * @return void
     */
    private function loadPlugins(): void
    {
        $table = $this->tableLocator->get('Plugins');
        $plugins = $table->find()->where(['enabled' => true])->cache('plugins', 'cms')->toArray();
        foreach ($plugins as $plugin) {
            $this->addPlugin($plugin->name, ['alias' => $plugin->alias]);
        }
    }
}
