<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RolesFixture
 */
class RolesFixture extends TestFixture
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
                'parent_id' => null,
                'lft' => 1,
                'rght' => 8,
                'name' => 'Root',
                'alias' => 'root',
                'created' => '2020-11-09 00:15:07',
                'modified' => '2020-11-09 00:15:07',
            ],
            [
                'id' => 2,
                'parent_id' => 1,
                'lft' => 2,
                'rght' => 7,
                'name' => 'Admin',
                'alias' => 'admin',
                'created' => '2020-11-09 00:42:12',
                'modified' => '2020-11-09 00:42:12',
            ],
            [
                'id' => 3,
                'parent_id' => 2,
                'lft' => 3,
                'rght' => 6,
                'name' => 'User',
                'alias' => 'user',
                'created' => '2020-11-09 00:42:55',
                'modified' => '2020-11-09 00:42:55',
            ],
            [
                'id' => 4,
                'parent_id' => 3,
                'lft' => 4,
                'rght' => 5,
                'name' => 'Authenticated',
                'alias' => 'authenticated',
                'created' => '2020-11-09 00:50:19',
                'modified' => '2020-11-09 00:50:19',
            ],
        ];
        parent::init();
    }
}
