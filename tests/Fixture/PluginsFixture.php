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
                'name' => 'TestPlugin1',
                'alias' => 'test_plugin_1',
                'description' => 'This is a test plugin 1',
                'parent_plugin' => null,
                'enabled' => 1,
            ],
            [
                'id' => 2,
                'name' => 'TestPlugin2',
                'alias' => 'test_plugin_2',
                'description' => 'This is a test plugin 2',
                'parent_plugin' => null,
                'enabled' => 1,
            ],
            [
                'id' => 3,
                'name' => 'TestPlugin3',
                'alias' => 'test_plugin_3',
                'description' => 'This is a test plugin 3',
                'parent_plugin' => null,
                'enabled' => 1,
            ],
        ];
        parent::init();
    }
}
