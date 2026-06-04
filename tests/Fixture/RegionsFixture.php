<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RegionsFixture
 */
class RegionsFixture extends TestFixture
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
                'alias' => 'plugins-menu',
                'theme' => 'DefaultTheme',
                'description' => 'Plugins admin menu region',
            ],
            [
                'id' => 2,
                'alias' => 'custom-menu',
                'theme' => 'DefaultTheme',
                'description' => 'Custom admin menu region',
            ],
            [
                'id' => 3,
                'alias' => 'frontend-main-menu',
                'theme' => 'DefaultTheme',
                'description' => 'Frontend main menu region',
            ],
            [
                'id' => 4,
                'alias' => 'frontend-footer-menu',
                'theme' => 'DefaultTheme',
                'description' => 'Frontend footer menu region',
            ],
        ];
        parent::init();
    }
}
