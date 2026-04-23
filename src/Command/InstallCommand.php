<?php
declare(strict_types=1);

namespace App\Command;

use App\Lib\PluginManager;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Exception;
use Override;

/**
 * Install Command
 *
 * Command to install the application by running all necessary setup steps.
 */
class InstallCommand extends Command
{
    /**
     * The plugin manager instance.
     *
     * @var \App\Lib\PluginManager
     */
    protected PluginManager $pluginManager;

    /**
     * @inheritDoc
     */
    public function __construct(PluginManager $pluginManager)
    {
        parent::__construct();
        $this->pluginManager = $pluginManager;
    }

    /**
     * Installs the application.
     *
     * Runs all installation steps in order. If any step fails,
     * completed steps are rolled back in reverse order.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $migrated = false;
        $userCreated = false;
        $resourcesGenerated = false;

        try {
            $this->migrate($io);
            $migrated = true;
            $io->hr();

            $this->createRootUser($io);
            $userCreated = true;
            $io->hr();

            $this->generateSystemResources($io);
            $resourcesGenerated = true;
            $io->hr();

            $this->setRootPermissions($io);
            $io->hr();

            $this->loadDefaultSettings($io);
            $io->hr();

            $io->out('Congratulations, BakeKit has been installed successfully!');

            return static::CODE_SUCCESS;
        } catch (Exception $e) {
            $io->err('Installation failed: ' . $e->getMessage());
            $io->hr();
            $io->out('Rolling back...');

            if ($resourcesGenerated) {
                $io->out('Removing system resources... - ', 0);
                $this->pluginManager->deleteResources('System');
                $io->out('done!');
            }

            if ($userCreated) {
                $io->out('Removing root user... - ', 0);
                $users = $this->fetchTable('Users');
                $users->deleteAll(['alias' => 'root']);
                $io->out('done!');
            }

            if ($migrated) {
                $io->out('Rolling back migrations... - ', 0);
                $this->pluginManager->deleteMigrations();
                $io->out('done!');
            }

            $io->out('Rollback complete. Please fix the error and try again.');
            $io->abort('');
        }
    }

    /**
     * Runs database migrations and seeds.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    private function migrate(ConsoleIo $io): void
    {
        $io->out('Setting up database objects... - ', 0);

        if ($this->pluginManager->addMigrations()) {
            $io->out('success!');
        } else {
            $io->abort('failure!');
        }
    }

    /**
     * Creates a root user.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    private function createRootUser(ConsoleIo $io): void
    {
        $io->out('Setup root user:');

        do {
            $firstName = $io->ask('First name:');
            if (empty($firstName)) {
                $io->err('First name must not be empty');
            }
        } while (empty($firstName));

        do {
            $lastName = $io->ask('Last name:');
            if (empty($lastName)) {
                $io->err('Last name must not be empty');
            }
        } while (empty($lastName));

        do {
            $email = $io->ask('Email:');
            if (empty($email)) {
                $io->err('Email must not be empty');
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $io->err('Email is not valid');
                $email = null;
            }
        } while (empty($email));

        do {
            $password = $io->ask('Password:');
            $verify = $io->ask('Verify Password:');
            $passwordsMatched = $password === $verify;

            if (!$passwordsMatched) {
                $io->err('Passwords do not match');
            }

            if (empty($password)) {
                $io->err('Password must not be empty');
            }
        } while (empty($password) || !$passwordsMatched);

        $io->out();
        $io->out('Setting up root user... - ', 0);

        $users = $this->fetchTable('Users');

        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'alias' => 'root',
            'email' => $email,
            'password' => $password,
            'role' => [
                'name' => 'Root',
                'alias' => 'root',
            ],
        ];

        $user = $users->newEntity($data, [
            'associated' => ['Roles'],
        ]);

        if ($users->save($user)) {
            $io->out('success!');
        } else {
            $io->abort('failure!');
        }
    }

    /**
     * Generates system resources (controllers, actions).
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    private function generateSystemResources(ConsoleIo $io): void
    {
        $io->out('Generating system resources... - ', 0);

        $this->pluginManager->addResources('System');

        $io->out('success!');
    }

    /**
     * Sets root user permissions.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    private function setRootPermissions(ConsoleIo $io): void
    {
        $io->out('Setting root permissions... - ', 0);
        $permissions = $this->fetchTable('Permissions');
        $permissions->allow(1, 1);
        $io->out('success!');
    }

    /**
     * Loads System default settings into DB.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    private function loadDefaultSettings(ConsoleIo $io): void
    {
        $io->out('Loading default settings... - ', 0);

        $this->pluginManager->addSettings();

        $io->out('success!');
    }
}
