<?php

declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\Routing\Router;

/**
 * JsonLD helper
 */
class SchemaHelper extends Helper
{

    protected $_data = [];

    public function addOrganization()
    {
        $this->_data[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->getView()->get('config')['Cms']['siteName'] ?? '',
            'url' => Router::fullBaseUrl(),
            'logo' => $this->getView()->Url->image('/img/logo.png', ['fullBase' => true]),
//            'sameAs' => []
        ];

        return $this;
    }

    public function addBreadcrumbs($breadcrumbs)
    {
        if (empty($breadcrumbs)) {
            return;
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList'
        ];

        $items = [];

        foreach ($breadcrumbs as $i => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => [
                    '@id' => $crumb['url'],
                    'name' => $crumb['title']
                ]
            ];
        }

        $data['itemListElement'] = $items;

        $this->_data[] = $data;

        return $this;
    }

    public function render()
    {
        if (!empty($this->_data)) {
            $scriptOpen = '<script type="application/ld+json">';
            if (count($this->_data) > 1) {
                $data = $this->_data;
            } else {
                $data = $this->_data[0];
            }

            $json = json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
            $scriptClose = '</script>';

            return $scriptOpen . $json . $scriptClose;
        }
    }
}
