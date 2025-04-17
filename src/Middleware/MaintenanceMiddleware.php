<?php

declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\Utility\Inflector;
use Cake\View\View;
use Cake\View\ViewBuilder;
use Laminas\Diactoros\CallbackStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class MaintenanceMiddleware implements MiddlewareInterface
{

    use InstanceConfigTrait;

    /**
     * @var array
     */
    protected $_defaultConfig = [
        'allowedIps' => [],
        'className' => View::class,
        'templatePath' => 'Error',
        'statusCode' => 503,
        'templateLayout' => 'maintenance',
        'templateFileName' => 'maintenance',
        'contentType' => 'text/html'
    ];

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);

        if (isset($config['allowedIps'])) {
            if (is_array($config['allowedIps'])) {
                $this->setConfig('allowedIps', $config['allowedIps']);
            }

            if (is_string($config['allowedIps'])) {
                $this->setConfig('allowedIps', preg_split("/[\s,;]+/", $config['allowedIps'], -1, \PREG_SPLIT_NO_EMPTY));
            }
        }
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->_config['mode'] || $this->_isIpAllowed($request)) {
            return $handler->handle($request);
        }

        return $this->build();
    }

    /**
     * @return \Cake\Http\Response
     */
    protected function build()
    {
        $cakeRequest = ServerRequestFactory::fromGlobals();
        $builder = new ViewBuilder();
        $view = $builder
                ->setClassName($this->_config['className'])
                ->setTemplatePath(Inflector::camelize($this->_config['templatePath']))
                ->setLayout($this->_config['templateLayout'])
                ->setVar('message', $this->_config['message'])
                ->build($cakeRequest);

        $bodyString = $view->render($this->_config['templateFileName']);

        $response = new Response();

        $response
                ->withHeader('Retry-After', (string) 3600)
                ->withHeader('Content-Type', $this->_config['contentType'])
                ->withStatus($this->_config['statusCode']);

        $body = new CallbackStream(function () use ($bodyString) {
                    return $bodyString;
                });

        return $response->withBody($body);
    }

    private function _isIpAllowed($request)
    {
        $clientIp = $request->clientIp();
        $ipAddressList = $this->_config['allowedIps'];
        if (empty($ipAddressList)) {
            return false;
        }
        foreach ($ipAddressList as $allowIP) {
            if (strpos($allowIP, '/') == 0) {
                $allowIP .= '/32';
            }

            if (!preg_match('/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\/([1-9]|1[0-9]|2[0-9]|3[0-2])$/', $allowIP)) {
                continue;
            }
            list($ip, $maskBit) = explode("/", $allowIP);
            $ipLong = ip2long($ip) >> (32 - $maskBit);
            $selfIpLong = ip2long($clientIp) >> (32 - $maskBit);
            if ($selfIpLong === $ipLong) {
                return true;
            }
        }
        return false;
    }
}
