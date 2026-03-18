<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Lib\PluginManager;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Command\InstallCommand Test Case
 *
 * NOTE: InstallCommand is still under development — these tests cover the
 * stable parts: migration step, rollback logic, and input validation.
 *
 * @uses \App\Command\InstallCommand
 */
class InstallCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Permissions',
        'app.Resources',
    ];

    // -------------------------------------------------------------------------
    // migrate() — first step
    // -------------------------------------------------------------------------

    /**
     * When addMigrations() returns false, the command aborts immediately.
     */
    public function testAbortsWhenMigrationsFail(): void
    {
        $pluginManager = $this->createMock(PluginManager::class);
        $pluginManager->expects($this->once())->method('addMigrations')->willReturn(false);
        $pluginManager->expects($this->never())->method('addResources');
        $pluginManager->expects($this->never())->method('addSettings');

        $this->mockService(PluginManager::class, fn() => $pluginManager);

        $this->exec('install');

        $this->assertExitError();
    }

    // -------------------------------------------------------------------------
    // createRootUser() — input validation
    // -------------------------------------------------------------------------

    /**
     * Empty first name should be rejected and re-prompted.
     */
    public function testRejectsEmptyFirstName(): void
    {
        $pluginManager = $this->createStub(PluginManager::class);
        $pluginManager->method('addMigrations')->willReturn(true);

        $this->mockService(PluginManager::class, fn() => $pluginManager);

        $this->exec('install', [
            '', // empty first name — rejected
            'John', // valid first name
            'Doe', // last name
            'john@example.com',
            'password123',
            'password123',
        ]);

        $this->assertErrorContains('First name must not be empty');
    }

    /**
     * Invalid email format should be rejected and re-prompted.
     */
    public function testRejectsInvalidEmail(): void
    {
        $pluginManager = $this->createStub(PluginManager::class);
        $pluginManager->method('addMigrations')->willReturn(true);

        $this->mockService(PluginManager::class, fn() => $pluginManager);

        $this->exec('install', [
            'John',
            'Doe',
            'not-an-email',
            'john@example.com',
            'password123',
            'password123',
        ]);

        $this->assertErrorContains('Email is not valid');
    }

    /**
     * Mismatched passwords should be rejected and re-prompted.
     */
    public function testRejectsMismatchedPasswords(): void
    {
        $pluginManager = $this->createStub(PluginManager::class);
        $pluginManager->method('addMigrations')->willReturn(true);

        $this->mockService(PluginManager::class, fn() => $pluginManager);

        $this->exec('install', [
            'John',
            'Doe',
            'john@example.com',
            'password123',
            'different', // mismatch
            'password123',
            'password123',
        ]);

        $this->assertErrorContains('Passwords do not match');
    }

    // -------------------------------------------------------------------------
    // Rollback logic
    // -------------------------------------------------------------------------

    /**
     * If createRootUser() fails (save returns false → abort),
     * only migrations are rolled back — resources were never reached.
     * deleteMigrations() should be called, deleteResources() should NOT.
     */
    public function testRollsBackMigrationsWhenUserCreationFails(): void
    {
        $pluginManager = $this->createMock(PluginManager::class);
        $pluginManager->method('addMigrations')->willReturn(true);

        // Resources should never be reached
        $pluginManager->expects($this->never())->method('addResources');
        $pluginManager->expects($this->never())->method('deleteResources');

        // Migration rollback should be called since migrations succeeded
        $pluginManager->expects($this->once())->method('deleteMigrations');

        $this->mockService(PluginManager::class, fn() => $pluginManager);

        // Provide valid inputs — but Users table save() will fail
        // because role_id=1 validation blocks creating Root via this path
        $this->exec('install', [
            'John',
            'Doe',
            'john@example.com',
            'password123',
            'password123',
        ]);

        $this->assertOutputContains('Rolling back');
        $this->assertExitError();
    }
}
