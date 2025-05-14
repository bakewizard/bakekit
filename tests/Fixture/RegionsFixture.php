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
                'description' => 'Plugins admin menu region',
            ],
            [
                'id' => 2,
                'alias' => 'custom-menu',
                'description' => 'Custom admin menu region',
            ],
        ];
        parent::init();
    }
}
