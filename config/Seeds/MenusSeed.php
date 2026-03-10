<?php
declare(strict_types=1);

use Migrations\BaseSeed;

/**
 * Menus seed.
 */
class MenusSeed extends BaseSeed
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
                'name' => 'Plugins menu',
                'description' => 'Admin plugins menu',
                'prefix' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Custom menu',
                'description' => 'Admin custom menu',
                'prefix' => 1,
            ],
        ];

        $table = $this->table('menus');
        $table->insert($data)->save();
    }
}
