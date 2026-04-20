<?php
declare(strict_types=1);

namespace App\Lib;

use Composer\Console\Application;
use Exception;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Simple Composer wrapper class.
 *
 * A wrapper class for Composer to call its commands from inside your code
 * using a simple object oriented API: turns php composer.phar require monolog/monolog
 * into $composer->require(['monolog/monolog:*']);.
 */
class ComposerManager
{
    private string $composerHome = ROOT . '/bin';
    private string $composerPath = ROOT . '/bin/composer.phar';

    /**
     * Runs composer commands.
     *
     * @param array<string, mixed> $options
     * @return string
     * @throws \Exception
     */

    /**
     * Runs composer commands.
     *
     * Saves and restores COMPOSER_HOME, COMPOSER_CACHE_DIR and cwd
     * so that running Composer does not affect the rest of the PHP process.
     *
     * @param array<string, mixed> $options
     * @return string
     * @throws \Exception
     */
    public function run(array $options): string
    {
        if (!class_exists(Application::class)) {
            if (!is_file($this->composerPath)) {
                throw new Exception('composer.phar is not found.');
            }
            require "phar://{$this->composerPath}/src/bootstrap.php";
        }

        $origHome = getenv('COMPOSER_HOME');
        $origCache = getenv('COMPOSER_CACHE_DIR');

        putenv("COMPOSER_HOME={$this->composerHome}");
        putenv('COMPOSER_CACHE_DIR=' . CACHE . 'composer');

        $options += [
            '--no-interaction' => true,
            '--working-dir' => ROOT,
        ];

        $cwd = getcwd();
        if ($cwd === false) {
            throw new Exception('Failed to get current working directory.');
        }
        chdir(ROOT);

        $input = new ArrayInput($options);
        $output = new BufferedOutput();

        try {
            $application = new Application();
            $application->setAutoExit(false);
            $exitCode = $application->run($input, $output);

            if ($exitCode !== 0) {
                throw new Exception('Composer failed: ' . $output->fetch());
            }

            return $output->fetch();
        } finally {
            putenv($origHome !== false ? "COMPOSER_HOME={$origHome}" : 'COMPOSER_HOME');
            putenv($origCache !== false ? "COMPOSER_CACHE_DIR={$origCache}" : 'COMPOSER_CACHE_DIR');
            chdir($cwd);
        }
    }

    /**
     * Installs one or more packages
     * E.g. $this->Composer->require(['monolog/monolog:~1.16', 'slim/slim'])
     * will install monolog in version 1.16 or later and the Slim framework
     * in the latest available version.
     *
     * @param array<int, string> $packages
     * @param array<string, mixed> $options
     * @return string
     */
    public function require(array $packages, array $options = []): string
    {
        $options += [
            '--prefer-dist' => true,
            '--no-progress' => true,
        ];
        $command = 'require';

        return $this->run(compact('command', 'packages') + $options);
    }

    /**
     * Uninstalles one or more packages
     * E.g. $this->Composer->remove(['monolog/monolog', 'slim/slim']) will uninstall
     * monolog in version 1.16 or later and the Slim framework in the latest available version.
     *
     * @param array<int, string> $packages
     * @param array<string, mixed> $options
     * @return string
     */
    public function remove(array $packages, array $options = []): string
    {
        $options += [
            '--no-progress' => true,
        ];
        $command = 'remove';

        return $this->run(compact('command', 'packages') + $options);
    }

    /**
     * Updates dependencies to the latest possible version and updates the composer.lock file
     * E.g. $composer->update() will update all dependencies while
     * $this->Composer->update(['symfony/css-crawler'], ['--optimize-autoloader'])
     * will update only the CSS crawler symfony component optimizing the autoloader afterwards.
     *
     * @param array<int, string> $packages
     * @param array<string, mixed> $options
     * @return string
     */
    public function update(array $packages, array $options = []): string
    {
        $options += [
            '--no-progress' => true,
        ];
        $command = 'update';

        return $this->run(compact('command', 'packages') + $options);
    }

    /**
     * Updates autoloader cache.
     *
     * @param array<string, mixed> $options
     * @return string
     */
    public function dumpAutoload(array $options = []): string
    {
        $command = 'dump-autoload';

        return $this->run(compact('command') + $options);
    }
}
