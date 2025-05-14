<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PluginsFixture
 */
class PluginsFixture extends TestFixture
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
                'name' => 'Test',
                'alias' => 'test',
                'description' => 'This is a test plugin',
                'parent_plugin' => null,
                'enabled' => 1,
            ],
        ];
        parent::init();
    }
}
