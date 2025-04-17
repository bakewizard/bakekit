<?php

declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\I18n\I18n;

/**
 * Menu helper
 */
class MenuHelper extends Helper
{

    private $_path;

    /**
     * Helpers used.
     *
     * @var array
     */
    public array $helpers = ['Html'];

    /**
     * Default configuration.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'container' => [
            'class' => 'navbar-nav',
        ],
        'item' => [
            'class' => 'nav-item'
        ],
        'itemLink' => [
            'class' => 'nav-link',
        ],
        'itemWithDropdown' => [
            'class' => 'dropdown'
        ],
        'itemWithDropdownLink' => [
            'class' => 'dropdown-toggle',
            'data-bs-toggle' => 'dropdown',
            'role' => 'button'
        ],
        'dropdownMenu' => [
            'class' => 'dropdown-menu'
        ],
        'dropdownMenuItem' => [],
        'dropdownMenuItemLink' => [
            'class' => 'dropdown-item'
        ]
    ];

    #[\Override]
    public function initialize(array $config): void
    {
        $this->_path = rtrim($this->getView()->getRequest()->getPath(), '/');
    }

    public function render(array $menuItems = [], array $options = [])
    {
        if (empty($menuItems)) {
            return '';
        }

        if (!empty($options)) {
            $this->setConfig($options);
        }
        $html = '';
        foreach ($menuItems as $menuItem) {
            $html .= $this->_renderMenuItem($menuItem);
        }

        return $this->Html->tag('ul', $html, $this->_config['container']);
    }

    protected function _renderMenuItem($item, $hasSubmenu = false)
    {
        $html = '';

        $title = !empty($item['icon']) ? "<i class=\"{$item['icon']}\"></i> {$item['title']}" : $item['title'];

        if ($hasSubmenu) {
            $attrs = (!empty($item['children']) ? $this->_mergeAttrs($this->_config['dropdownMenuItemLink'], $this->_config['itemWithDropdownLink']) : $this->_config['dropdownMenuItemLink']);
        } else {
            $attrs = (!empty($item['children']) ? $this->_mergeAttrs($this->_config['itemLink'], $this->_config['itemWithDropdownLink']) : $this->_config['itemLink']);
        }

        if ($this->_path === $item['link']) {
            $attrs['class'] .= ' active';
        }

        $html .= $this->Html->link($title, $this->_renderLink($item['link']), $attrs + ['target' => $item['target'], 'escape' => false]);

        if (!empty($item['children'])) {
            $items = '';
            foreach ($item['children'] as $childItem) {
                $items .= $this->_renderMenuItem($childItem, true);
            }
            $html .= $this->Html->tag('ul', $items, $this->_config['dropdownMenu']);
        }

        if ($hasSubmenu) {
            $attrs = (!empty($item['children']) ? $this->_mergeAttrs($this->_config['dropdownMenuItem'], $this->_config['itemWithDropdown']) : $this->_config['dropdownMenuItem']);
        } else {
            $attrs = (!empty($item['children']) ? $this->_mergeAttrs($this->_config['item'], $this->_config['itemWithDropdown']) : $this->_config['item']);
        }

        return $this->Html->tag('li', $html, $attrs);
    }

    protected function _renderLink($link)
    {
        if ($link[0] === '/' && I18n::getLocale() !== I18n::getDefaultLocale()) {
            return '/' . I18n::getLocale() . $link;
        } else {
            return $link;
        }
    }

    protected function _mergeAttrs($attrs1, $attrs2)
    {
        $attrs = $attrs1;
        foreach ($attrs1 as $attr1 => $value1) {
            foreach ($attrs2 as $attr2 => $value2) {
                if ($attr2 === $attr1) {
                    $attrs[$attr2] = trim($value1 . ' ' . $value2);
                } else {
                    $attrs[$attr2] = $value2;
                }
            }
        }

        return $attrs;
    }
}
