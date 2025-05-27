<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MenusFixture
 */
class MenusFixture extends TestFixture
{
    public string $table = 'menus';

    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'name' => 'Plugins menu',
                'description' => 'Admin plugins menu',
                'prefix' => 1,
                'enabled' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Custom menu',
                'description' => 'Admin custom menu',
                'prefix' => 1,
                'enabled' => 1,
            ],
        ];
        parent::init();
    }
}
