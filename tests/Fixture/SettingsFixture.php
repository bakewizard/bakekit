<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SettingsFixture
 */
class SettingsFixture extends TestFixture
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
                'namespace' => 'System',
                'path' => 'siteName',
                'value' => 'BakeKit',
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 2,
                'namespace' => 'System',
                'path' => 'theme',
                'value' => null,
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 3,
                'namespace' => 'System',
                'path' => 'defaultDashboard',
                'value' => 'System',
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 4,
                'namespace' => 'System',
                'path' => 'maintenance.mode',
                'value' => '0',
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 5,
                'namespace' => 'System',
                'path' => 'maintenance.allowedIps',
                'value' => null,
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 6,
                'namespace' => 'System',
                'path' => 'maintenance.message',
                'value' => null,
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 7,
                'namespace' => 'System',
                'path' => 'images.format',
                'value' => 'jpeg',
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 8,
                'namespace' => 'System',
                'path' => 'images.quality',
                'value' => '90',
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
        ];
        parent::init();
    }
}
