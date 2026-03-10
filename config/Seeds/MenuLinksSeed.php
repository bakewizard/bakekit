<?php
declare(strict_types=1);

use Migrations\BaseSeed;

/**
 * MenuLinks seed.
 */
class MenuLinksSeed extends BaseSeed
{
    /**
     * Run Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html#the-run-method
     *
     * @return void
     */
    public function run(): void
    {
        $data = [
            [
                'id' => 1,
                'menu_id' => 1,
                'parent_id' => null,
                'title' => 'File manager',
                'icon' => 'fa-solid fa-folder-tree',
                'link' => '/admin/file-manager',
                'target' => '_self',
                'lft' => 1,
                'rght' => 2,
            ],
            [
                'id' => 2,
                'menu_id' => 2,
                'parent_id' => null,
                'title' => 'Documentation',
                'icon' => 'fa-solid fa-book',
                'link' => 'https://bakewizard.github.io/bakekit/',
                'target' => '_blank',
                'lft' => 3,
                'rght' => 4,
            ],
        ];

        $table = $this->table('menu_links');
        $table->insert($data)->save();
    }

    /**
     * Get Dependencies Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html#foreign-key-dependencies
     *
     * @return void
     */
    public function getDependencies(): array
    {
        return [
            'MenusSeed',
        ];
    }
}
