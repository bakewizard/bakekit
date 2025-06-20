<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BlocksFixture
 */
class BlocksFixture extends TestFixture
{
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
                'alias' => 'header-main-menu',
                'title' => null,
                'description' => 'Header main menu',
                'region_id' => 3,
                'cell' => 'Menu',
                'template' => null,
                'params' => [
                    'menu' => '3',
                ],
                'position' => 1,
                'enabled' => true,
            ],
            [
                'id' => 2,
                'alias' => 'footer-text',
                'title' => 'Footer Text',
                'description' => 'Copyright text',
                'region_id' => 4,
                'cell' => null,
                'template' => null,
                'params' => '© 2025 BakeKit. All rights reserved.',
                'position' => 1,
                'enabled' => 1,
            ],
        ];
        parent::init();
    }
}
