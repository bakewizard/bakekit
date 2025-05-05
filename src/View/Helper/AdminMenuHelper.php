<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Model\Entity\MenuLink;
use Override;

/**
 * Admin Menu helper
 */
class AdminMenuHelper extends MenuHelper
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'container' => [
            'id' => 'admin-plugins-menu',
            'class' => 'nav sidebar-menu flex-column',
            'role' => 'menu',
            'data-accordion' => 'true',
            'data-lte-toggle' => 'treeview',
        ],
        'item' => [
            'class' => 'nav-item',
        ],
        'itemLink' => [
            'class' => 'nav-link',
        ],
        'itemWithDropdown' => [],
        'itemWithDropdownLink' => [],
        'dropdownMenu' => [
            'class' => 'nav nav-treeview',
        ],
        'dropdownMenuItem' => [
            'class' => 'nav-item',
        ],
        'dropdownMenuItemLink' => [
            'class' => 'nav-link',
        ],
    ];

    /**
     * @inheritDoc
     */
    #[Override]
    protected function renderMenuItem(MenuLink $item, bool $hasSubmenu = false): string
    {
        $html = '';

        $icon = !empty($item['icon']) ? $item['icon'] : 'fas fa-circle';
        $submenuIcon = !empty($item['children']) ? ' <i class="nav-arrow fas fa-angle-right"></i>' : '';
        $title = "<i class=\"nav-icon $icon\"></i><p>{$item['title']}$submenuIcon</p>";

        if ($hasSubmenu) {
            $attrs = !empty($item['children'])
                ? $this->mergeAttrs($this->_config['dropdownMenuItemLink'], $this->_config['itemWithDropdownLink'])
                : $this->_config['dropdownMenuItemLink'];
        } else {
            $attrs = (!empty($item['children']) ? $this->mergeAttrs($this->_config['itemLink'], $this->_config['itemWithDropdownLink']) : $this->_config['itemLink']);
        }
        $html .= $this->Html->link($title, $item['link'], $attrs + ['target' => $item['target'], 'escape' => false]);

        if (!empty($item['children'])) {
            $items = '';
            foreach ($item['children'] as $childItem) {
                $items .= $this->renderMenuItem($childItem, true);
            }
            $html .= $this->Html->tag('ul', $items, $this->_config['dropdownMenu']);
        }

        if ($hasSubmenu) {
            $attrs = (!empty($item['children']) ? $this->mergeAttrs($this->_config['dropdownMenuItem'], $this->_config['itemWithDropdown']) : $this->_config['dropdownMenuItem']);
        } else {
            $attrs = (!empty($item['children']) ? $this->mergeAttrs($this->_config['item'], $this->_config['itemWithDropdown']) : $this->_config['item']);
        }

        return $this->Html->tag('li', $html, $attrs);
    }
}
