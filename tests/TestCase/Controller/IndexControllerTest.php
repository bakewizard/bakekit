<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\IndexController Test Case
 *
 * @uses \App\Controller\IndexController
 */
class IndexControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\IndexController::index()
     */
    public function testIndex(): void
    {
        $this->get('/');

        $this->assertResponseOk();
    }
}
