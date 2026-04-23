<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\Routing\Router;
use Cake\View\Helper;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * JsonLD helper
 */
class SchemaHelper extends Helper
{
    /**
     * Array to hold the structured data.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $_data = [];

    /**
     * Adds Organization schema markup.
     *
     * Retrieves site name from the 'config' and generates the Organization schema.
     *
     * @return self
     */
    public function addOrganization(): self
    {
        $this->_data[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->getView()->get('config')['System']['siteName'] ?? '',
            'url' => Router::fullBaseUrl(),
            'logo' => $this->getView()->Url->image('/img/logo.png', ['fullBase' => true]),
//            'sameAs' => []
        ];

        return $this;
    }

    /**
     * Adds BreadcrumbList schema markup.
     *
     * Takes an array of breadcrumb items and formats them for JSON-LD.
     *
     * @param array<int, array{title: string, url: string}> $breadcrumbs An array of breadcrumb items.
     * @return self
     */
    public function addBreadcrumbs(array $breadcrumbs): self
    {
        if (empty($breadcrumbs)) {
            return $this;
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
        ];

        $items = [];

        foreach ($breadcrumbs as $i => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => [
                    '@id' => $crumb['url'],
                    'name' => $crumb['title'],
                ],
            ];
        }

        $data['itemListElement'] = $items;

        $this->_data[] = $data;

        return $this;
    }

    /**
     * Renders the accumulated JSON-LD data as a script tag.
     *
     * @return string A script tag containing the JSON-LD data, or an empty string
     * if no data has been added.
     */
    public function render(): string
    {
        if (empty($this->_data)) {
            return '';
        }

        $scriptOpen = '<script type="application/ld+json">';
        if (count($this->_data) > 1) {
            $data = $this->_data;
        } else {
            $data = $this->_data[0];
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $scriptClose = '</script>';

        return $scriptOpen . $json . $scriptClose;
    }
}
