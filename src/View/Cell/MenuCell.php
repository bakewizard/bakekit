<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\View\Cell\BlockCell as Cell;

/**
 * Menu cell
 */
class MenuCell extends Cell
{
    /**
     * Menu
     *
     * Displays menu
     *
     * @param string $helper Menu helper
     * @return void
     */
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
