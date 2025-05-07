<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Model\Entity\MenuLink;
use Cake\I18n\I18n;
use Cake\View\Helper;
use Override;

/**
 * Menu helper
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class MenuHelper extends Helper
{
    /**
     * @var string
     */
    private string $path;

    /**
     * Helpers used.
     *
     * @var array<string>
     */
    public array $helpers = ['Html'];

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'container' => [
            'class' => 'navbar-nav',
        ],
        'item' => [
            'class' => 'nav-item',
        ],
        'itemLink' => [
            'class' => 'nav-link',
        ],
        'itemWithDropdown' => [
            'class' => 'dropdown',
        ],
        'itemWithDropdownLink' => [
            'class' => 'dropdown-toggle',
            'data-bs-toggle' => 'dropdown',
            'role' => 'button',
        ],
        'dropdownMenu' => [
            'class' => 'dropdown-menu',
        ],
        'dropdownMenuItem' => [],
        'dropdownMenuItemLink' => [
            'class' => 'dropdown-item',
        ],
    ];

    /**
     * @inheritDoc
     */
    #[Override]
    public function initialize(array $config): void
    {
        $this->path = rtrim($this->getView()->getRequest()->getPath(), '/');
    }

    /**
     * Renders menu
     *
     * @param array<\App\Model\Entity\MenuLink> $menuItems Menu items
     * @param array<string, mixed> $options Options
     * @return string
     */
    public function render(array $menuItems = [], array $options = []): string
    {
        if (empty($menuItems)) {
            return '';
        }

        if (!empty($options)) {
            $this->setConfig($options);
        }
        $html = '';
        foreach ($menuItems as $menuItem) {
            $html .= $this->renderMenuItem($menuItem);
        }

        return $this->Html->tag('ul', $html, $this->_config['container']);
    }

    /**
     * Renders menu item
     *
     * @param \App\Model\Entity\MenuLink $item
     * @param bool $hasSubmenu
     * @return string
     */
    protected function renderMenuItem(MenuLink $item, bool $hasSubmenu = false): string
    {
        $html = '';

        $title = !empty($item['icon']) ? "<i class=\"{$item['icon']}\"></i> {$item['title']}" : $item['title'];

        $linkAttrs = [];
        if ($hasSubmenu) {
            $linkAttrs = !empty($item['children'])
                ? $this->mergeAttrs($this->_config['dropdownMenuItemLink'], $this->_config['itemWithDropdownLink'])
                : $this->_config['dropdownMenuItemLink'];
        } else {
            $linkAttrs = (!empty($item['children']) ? $this->mergeAttrs($this->_config['itemLink'], $this->_config['itemWithDropdownLink']) : $this->_config['itemLink']);
        }

        if ($this->path === $item['link']) {
            if (isset($linkAttrs['class'])) {
                $linkAttrs['class'] .= ' active';
            } else {
                $linkAttrs['class'] = 'active';
            }
        }

        $html .= $this->Html->link($title, $this->renderLink($item['link']), $linkAttrs + ['target' => $item['target'], 'escape' => false]);

        if (!empty($item['children'])) {
            $items = '';
            foreach ($item['children'] as $childItem) {
                $items .= $this->renderMenuItem($childItem, true);
            }
            $html .= $this->Html->tag('ul', $items, $this->_config['dropdownMenu']);
        }

        $listItemAttrs = [];
        if ($hasSubmenu) {
            $listItemAttrs = (!empty($item['children']) ? $this->mergeAttrs($this->_config['dropdownMenuItem'], $this->_config['itemWithDropdown']) : $this->_config['dropdownMenuItem']);
        } else {
            $listItemAttrs = (!empty($item['children']) ? $this->mergeAttrs($this->_config['item'], $this->_config['itemWithDropdown']) : $this->_config['item']);
        }

        return $this->Html->tag('li', $html, $listItemAttrs);
    }

    /**
     * Renders menu link
     *
     * @param string $link Menu link
     * @return string
     */
    protected function renderLink(string $link): string
    {
        if (str_starts_with($link, '/') && I18n::getLocale() !== I18n::getDefaultLocale()) {
            return '/' . I18n::getLocale() . $link;
        } else {
            return $link;
        }
    }

    /**
     * Merges attributes
     *
     * @param array<string, string> $attrs1
     * @param array<string, string> $attrs2
     * @return array<string, string>
     */
    protected function mergeAttrs(array $attrs1, array $attrs2): array
    {
        $attrs = $attrs1;
        foreach ($attrs2 as $attr2 => $value2) {
            if (isset($attrs[$attr2])) {
                $attrs[$attr2] = trim($attrs[$attr2] . ' ' . $value2);
            } else {
                $attrs[$attr2] = $value2;
            }
        }

        return $attrs;
    }
}
