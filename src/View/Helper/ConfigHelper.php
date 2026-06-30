<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\Utility\Hash;
use Cake\View\Helper;

/**
 * Flexible Config helper supporting both direct invoke and explicit get calls.
 */
class ConfigHelper extends Helper
{
    /**
     * Explicit read method: $this->Config->get('Shop.productImages.th')
     *
     * @param string $path Dot-notated path with scope prefix (e.g., 'System.siteName').
     * @param mixed $default The fallback value.
     * @return mixed
     */
    public function get(string $path, mixed $default = null): mixed
    {
        [$scope, $key] = pluginSplit($path);
        $scope = $scope !== null ? strtolower($scope) : 'system';

        $attributeName = $scope . '_config';
        $config = $this->getView()->getRequest()->getAttribute($attributeName) ?? [];

        return Hash::get($config, $key, $default);
    }

    /**
     * Magic invoke fallback: $this->Config('Shop.productImages.th')
     *
     * @param string $path Dot-notated path with scope prefix.
     * @param mixed $default The fallback value.
     * @return mixed
     */
    public function __invoke(string $path, mixed $default = null): mixed
    {
        return $this->get($path, $default);
    }
}
