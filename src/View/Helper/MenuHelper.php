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
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class MenuHelper extends Helper
{
    /**
     * The current path of the request.
     *
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
        'container' => ['class' => 'navbar-nav'],
        'item' => ['class' => 'nav-item'],
        'itemLink' => ['class' => 'nav-link'],
        'itemWithDropdown' => ['class' => 'dropdown'],
        'itemWithDropdownLink' => [
            'class' => 'dropdown-toggle',
            'data-bs-toggle' => 'dropdown',
            'role' => 'button',
        ],
        'dropdownMenu' => ['class' => 'dropdown-menu'],
        'dropdownMenuItem' => [],
        'dropdownMenuItemLink' => ['class' => 'dropdown-item'],
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
     * Renders a single menu item
     *
     * @param \App\Model\Entity\MenuLink $item Menu item
     * @param bool $hasSubmenu Whether the item has a submenu
     * @return string
     */
    protected function renderMenuItem(MenuLink $item, bool $hasSubmenu = false): string
    {
        $linkHtml = $this->buildLink($item, $hasSubmenu);

        if (!empty($item->children)) {
            $childrenHtml = '';
            foreach ($item->children as $childItem) {
                $childrenHtml .= $this->renderMenuItem($childItem, true);
            }
            $linkHtml .= $this->Html->tag('ul', $childrenHtml, $this->_config['dropdownMenu']);
        }

        return $this->Html->tag('li', $linkHtml, $this->getListItemAttributes($item, $hasSubmenu));
    }

    /**
     * Builds the link HTML for a menu item
     *
     * @param \App\Model\Entity\MenuLink $item Menu item
     * @param bool $hasSubmenu Whether the item has a submenu
     * @return string
     */
    protected function buildLink(MenuLink $item, bool $hasSubmenu): string
    {
        $title = !empty($item->icon) ? "<i class=\"{$item->icon}\"></i> {$item->title}" : $item->title;
        $attrs = $this->getLinkAttributes($item, $hasSubmenu);

        return $this->Html->link(
            $title,
            $this->renderLink((string)($item->link ?? '#')),
            $attrs + ['target' => $item->target, 'escape' => false],
        );
    }

    /**
     * Gets the attributes for the link element
     *
     * @param \App\Model\Entity\MenuLink $item Menu item
     * @param bool $hasSubmenu Whether the item has a submenu
     * @return array<string, mixed>
     */
    protected function getLinkAttributes(MenuLink $item, bool $hasSubmenu): array
    {
        $base = $hasSubmenu
            ? (!empty($item->children)
                ? $this->mergeAttrs($this->_config['dropdownMenuItemLink'], $this->_config['itemWithDropdownLink'])
                : $this->_config['dropdownMenuItemLink'])
            : (!empty($item->children)
                ? $this->mergeAttrs($this->_config['itemLink'], $this->_config['itemWithDropdownLink'])
                : $this->_config['itemLink']);

        if ($this->path === $item->link) {
            $base['class'] = ($base['class'] ?? '') . ' active';
        }

        return $base;
    }

    /**
     * Gets the attributes for the list item element
     *
     * @param \App\Model\Entity\MenuLink $item Menu item
     * @param bool $hasSubmenu Whether the item has a submenu
     * @return array<string, mixed>
     */
    protected function getListItemAttributes(MenuLink $item, bool $hasSubmenu): array
    {
        return $hasSubmenu
            ? (!empty($item->children)
                ? $this->mergeAttrs($this->_config['dropdownMenuItem'], $this->_config['itemWithDropdown'])
                : $this->_config['dropdownMenuItem'])
            : (!empty($item->children)
                ? $this->mergeAttrs($this->_config['item'], $this->_config['itemWithDropdown'])
                : $this->_config['item']);
    }

    /**
     * Renders a link with the correct locale prefix if necessary
     *
     * @param string $link The link to render
     * @return string
     */
    protected function renderLink(string $link): string
    {
        if (str_starts_with($link, '/') && I18n::getLocale() !== I18n::getDefaultLocale()) {
            return '/' . I18n::getLocale() . $link;
        }

        return $link;
    }

    /**
     * Merges two arrays of attributes, concatenating values for existing keys
     *
     * @param array<string, mixed> $attrs1 First set of attributes
     * @param array<string, mixed> $attrs2 Second set of attributes
     * @return array<string, mixed>
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
