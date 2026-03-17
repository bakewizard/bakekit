<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Attribute\Link;
use App\View\Cell\BlockCell as Cell;

/**
 * Menu cell
 */
class MenuCell extends Cell
{
    /**
     * Menu block rendering method.
     *
     * Retrieves menu items for a given menu ID from block parameters
     * and builds a threaded (hierarchical) structure ordered by `lft`.
     * Results are cached using a key that includes menu ID, prefix,
     * and language (if present).
     *
     * @param string $helper Menu helper
     * @return void
     */
    #[Link(summary: 'Menu', description: 'Displays menu')]
    public function display(string $helper = 'Menu'): void
    {
        $menuId = $this->block->params['menu'] ?? '';
        $options = $this->block->params;
        $menuItems = [];

        $lang = $this->request->getParam('lang');
        $prefix = $this->request->getParam('prefix');

        if (!empty($menuId)) {
            $cacheKey = $menuId . ($prefix ? '_' . strtolower($prefix) : '') . ($lang ? "_$lang" : '');
            $menuItems = $this->fetchTable('MenuLinks')
                ->find('threaded')
                ->where(['menu_id' => $menuId])
                ->orderByAsc('lft')
                ->cache($cacheKey, 'menus')
                ->toArray();
        }

        $this->set(compact('menuItems', 'options', 'helper'));
    }
}
