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
 * A wrapper class for Composer to call it's commands from inside your code
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
    public function run(array $options): string
    {
        if (!is_file($this->composerPath)) {
            throw new Exception('composer.phar is not found.');
        }

        putenv("COMPOSER_HOME={$this->composerHome}");
        putenv('COMPOSER_CACHE_DIR=' . CACHE . 'composer');

        $options += [
            '--no-interaction' => true,
            '--working-dir' => ROOT,
        ];

        $cwd = getcwd();
        chdir(ROOT);

        $input = new ArrayInput($options);
        $output = new BufferedOutput();

        require "phar://{$this->composerPath}/src/bootstrap.php";

        $application = new Application();
        $application->setAutoExit(false);
        $application->run($input, $output);

        chdir($cwd);

        return $output->fetch();
    }

    /**
     * Installs one or more packages
     * E.g. $this->Composer->require(['monolog/monolog:~1.16', 'slim/slim'])
     * will install monolog in version 1.16 or later and the Slim framework
     * in the latest available version.
     *
     * @param array $packages
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
     * @param array $packages
     * @param array $options
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
     * @param array $packages
     * @param array $options
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
     * @param array $options
     * @return string
     */
    public function dumpAutoload(array $options = []): string
    {
        $command = 'dump-autoload';

        return $this->run(compact('command') + $options);
    }

    /**
     * Adds/removes psr4 autoload path.
     *
     * @param string $name Plugin name.
     * @param string|null $path
     * @return void
     */
    public function modifyPsr4Autoload(string $name, ?string $path = null): void
    {
        $file = ROOT . DS . 'composer.json';
        $config = $this->getConfigData();
        $namespace = str_replace('/', '\\', $name);

        if ($path) {
            $config['autoload']['psr-4'][$namespace . '\\'] = $path . '/src/';
            $config['autoload-dev']['psr-4'][$namespace . '\\Test\\'] = $path . '/tests/';
            $classLoader = include ROOT . '/vendor/autoload.php';
            $classLoader->addPsr4($namespace . '\\', [ROOT . '/' . $path . '/src']);
        } else {
            unset($config['autoload']['psr-4'][$namespace . '\\']);
            unset($config['autoload-dev']['psr-4'][$namespace . '\\Test\\']);
        }

        $out = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($file, $out);
    }

    /**
     * Reads composer.json into array.
     *
     * @param string $path
     * @return array
     * @throws \Exception
     */
    public function getConfigData(?string $path = null): array
    {
        if (!$path) {
            $path = ROOT;
        }

        if (!is_readable($path)) {
            throw new Exception(__('Main composer file not found.'));
        }

        return json_decode(file_get_contents($path . DS . 'composer.json'), true);
    }
}
