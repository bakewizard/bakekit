<?php
declare(strict_types=1);

namespace App\View\Cell\Admin;

use Cake\View\Cell;

/**
 * Admin Menu cell
 */
class MenuCell extends Cell
{
    /**
     * Admin menu
     *
     * Displays admin menu
     *
     * @param int $menuId Menu id
     * @return void
     */
    public function display(int $menuId, string $helper = 'Menu'): void
    {
        $lang = (string)$this->request->getParam('lang');
        $langKey = $lang ? "_$lang" : '';

        $cacheKey = "admin_{$menuId}{$langKey}";

        $menuItems = $this->fetchTable('MenuLinks')
            ->find('threaded')
            ->where(['menu_id' => $menuId])
            ->orderByAsc('lft')
            ->cache($cacheKey, 'menus')
            ->toArray();

        $this->set(compact('menuItems', 'helper'));
    }
}
