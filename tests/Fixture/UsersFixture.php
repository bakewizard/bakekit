<?php

declare(strict_types=1);

namespace App\Test\Fixture;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
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
                'first_name' => 'Neo',
                'last_name' => 'Matrix',
                'alias' => 'root',
                'email' => 'neo@matrix.net',
                'password' => (new DefaultPasswordHasher())->hash('admin'),
                'role_id' => 1,
                'created' => '2025-05-12 08:22:11',
                'modified' => '2025-05-12 08:22:11',
                'files' => [],
            ],
            [
                'id' => 2,
                'first_name' => 'Test',
                'last_name' => 'Test',
                'alias' => 'test',
                'email' => 'test@test.net',
                'password' => (new DefaultPasswordHasher())->hash('test'),
                'role_id' => 2,
                'created' => '2025-05-12 08:30:12',
                'modified' => '2025-05-12 08:30:12',
                'files' => [],
            ],
        ];
        parent::init();
    }
}
