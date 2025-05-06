<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Utility\Inflector;
use Cake\View\View;
use Cake\View\ViewBuilder;
use Laminas\Diactoros\CallbackStream;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use const PREG_SPLIT_NO_EMPTY;

class MaintenanceMiddleware implements MiddlewareInterface
{
    use InstanceConfigTrait;

    /**
     * @var array
     */
    protected array $_defaultConfig = [
        'allowedIps' => [],
        'className' => View::class,
        'templatePath' => 'Error',
        'statusCode' => 503,
        'templateLayout' => 'maintenance',
        'templateFileName' => 'maintenance',
        'contentType' => 'text/html',
    ];

    /**
     * @inheritDoc
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);

        if (isset($config['allowedIps'])) {
            if (is_array($config['allowedIps'])) {
                $this->setConfig('allowedIps', $config['allowedIps']);
            }

            if (is_string($config['allowedIps'])) {
                $this->setConfig('allowedIps', preg_split("/[\s,;]+/", $config['allowedIps'], -1, PREG_SPLIT_NO_EMPTY));
            }
        }
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isMaintenanceMode = $this->_config['mode'] ?? false;

        if (!$isMaintenanceMode) {
            return $handler->handle($request);
        }

        if ($request instanceof ServerRequest && $this->isIpAllowed($request)) {
            return $handler->handle($request);
        }

        return $this->build();
    }

    /**
     * @return \Cake\Http\Response
     */
    protected function build(): Response
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
                ->withHeader('Retry-After', (string)3600)
                ->withHeader('Content-Type', $this->_config['contentType'])
                ->withStatus($this->_config['statusCode']);

        $body = new CallbackStream(function () use ($bodyString) {
                    return $bodyString;
        });

        return $response->withBody($body);
    }

    /**
     * Check if the client's IP address is allowed based on configured CIDR ranges.
     *
     * @param \Cake\Http\ServerRequest $request
     * @return bool
     */
    private function isIpAllowed(ServerRequest $request): bool
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
            [$ip, $maskBit] = explode('/', $allowIP);
            $ipLong = ip2long($ip) >> 32 - (int)$maskBit;
            $selfIpLong = ip2long($clientIp) >> 32 - (int)$maskBit;
            if ($selfIpLong === $ipLong) {
                return true;
            }
        }

        return false;
    }
}
