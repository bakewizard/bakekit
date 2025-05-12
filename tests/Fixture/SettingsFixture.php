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
                'namespace' => 'Cms',
                'path' => 'siteName',
                'value' => 'BakeKit CMS',
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 2,
                'namespace' => 'Cms',
                'path' => 'theme',
                'value' => null,
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 3,
                'namespace' => 'Cms',
                'path' => 'defaultDashboard',
                'value' => 'System',
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 4,
                'namespace' => 'Cms',
                'path' => 'maintenance.mode',
                'value' => '0',
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 5,
                'namespace' => 'Cms',
                'path' => 'maintenance.allowedIps',
                'value' => null,
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 6,
                'namespace' => 'Cms',
                'path' => 'maintenance.message',
                'value' => null,
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 7,
                'namespace' => 'Cms',
                'path' => 'images.format',
                'value' => 'jpeg',
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
            [
                'id' => 8,
                'namespace' => 'Cms',
                'path' => 'images.quality',
                'value' => '90',
                'created' => '2025-05-12 08:22:12',
                'modified' => '2025-05-12 08:22:12',
            ],
        ];
        parent::init();
    }
}
