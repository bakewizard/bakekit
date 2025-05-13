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
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
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
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
                'files' => [],
            ],
        ];
        parent::init();
    }
}
