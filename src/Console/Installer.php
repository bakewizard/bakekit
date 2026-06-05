<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Console;

if (!defined('STDIN')) {
    define('STDIN', fopen('php://stdin', 'r'));
}

use Cake\Utility\Security;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Exception;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Provides installation hooks for when this application is installed through
 * composer. Customize this class to suit your needs.
 */
class Installer
{
    /**
     * An array of directories to be made writable
     *
     * @var list<string>
     */
    public const WRITABLE_DIRS = [
        'themes',
        'logs',
        'tmp',
        'tmp/cache',
        'tmp/cache/models',
        'tmp/cache/persistent',
        'tmp/cache/views',
        'tmp/sessions',
        'tmp/tests',
    ];

    /**
     * Does some routine installation tasks so people don't have to.
     *
     * @param \Composer\Script\Event $event The composer event object.
     * @throws \Exception Exception raised by validator.
     * @return void
     */
    public static function postInstall(Event $event): void
    {
        $io = $event->getIO();

        $rootDir = dirname(__DIR__, 2);

        if (!static::createAppLocalConfig($rootDir, $io)) {
            return;
        }

        static::createWritableDirectories($rootDir, $io);

        static::setFolderPermissions($rootDir, $io);
        static::setSecuritySalt($rootDir, $io);

        static::setDatabaseSettings($rootDir, $io);
    }

    /**
     * Create config/app_local.php file if it does not exist.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return bool
     */
    public static function createAppLocalConfig(string $dir, IOInterface $io): bool
    {
        $appLocalConfig = $dir . '/config/app_local.php';
        $appLocalConfigTemplate = $dir . '/config/app_local.example.php';

        if (file_exists($appLocalConfig)) {
            return false;
        }

        copy($appLocalConfigTemplate, $appLocalConfig);
        $io->write('Created `config/app_local.php` file');

        return true;
    }

    /**
     * Create the `logs` and `tmp` directories.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function createWritableDirectories(string $dir, IOInterface $io): void
    {
        foreach (static::WRITABLE_DIRS as $path) {
            $path = $dir . '/' . $path;
            if (!file_exists($path)) {
                mkdir($path);
                $io->write('Created `' . $path . '` directory');
            }
        }
    }

    /**
     * Set globally writable permissions on the "tmp" and "logs" directory.
     *
     * This is not the most secure default, but it gets people up and running quickly.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function setFolderPermissions(string $dir, IOInterface $io): void
    {
        // ask if the permissions should be changed
        if ($io->isInteractive()) {
            $validator = function (string $arg): string {
                if (in_array($arg, ['Y', 'y', 'N', 'n'])) {
                    return $arg;
                }
                throw new Exception('This is not a valid answer. Please choose Y or n.');
            };
            $setFolderPermissions = $io->askAndValidate(
                '<info>Set Folder Permissions ? (Default to Y)</info> [<comment>Y,n</comment>]? ',
                $validator,
                10,
                'Y',
            );

            if (in_array($setFolderPermissions, ['n', 'N'])) {
                return;
            }
        }

        // Change the permissions on a path and output the results.
        $changePerms = function (string $path) use ($io): void {
            $currentPerms = fileperms($path) & 0777;
            $worldWritable = $currentPerms | 0007;
            if ($worldWritable == $currentPerms) {
                return;
            }

            $res = chmod($path, $worldWritable);
            if ($res) {
                $io->write('Permissions set on ' . $path);
            } else {
                $io->write('Failed to set permissions on ' . $path);
            }
        };

        $walker = function (string $dir) use (&$walker, $changePerms): void {
            $files = array_diff(scandir($dir) ?: [], ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;

                if (!is_dir($path)) {
                    continue;
                }

                $changePerms($path);
                $walker($path);
            }
        };

        $walker($dir . '/tmp');
        $changePerms($dir . '/tmp');
        $changePerms($dir . '/logs');
    }

    /**
     * Set the security.salt value in the application's config file.
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function setSecuritySalt(string $dir, IOInterface $io): void
    {
        $newKey = hash('sha256', Security::randomBytes(64));
        static::setSecuritySaltInFile($dir, $io, $newKey, 'app_local.php');
    }

    /**
     * Set the security.salt value in a given file
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @param string $newKey key to set in the file
     * @param string $file A path to a file relative to the application's root
     * @return void
     */
    public static function setSecuritySaltInFile(string $dir, IOInterface $io, string $newKey, string $file): void
    {
        $config = $dir . '/config/' . $file;
        $content = file_get_contents($config);
        if ($content === false) {
            $io->write('Config file not readable or not found: config/' . $file);

            return;
        }

        $content = str_replace('__SALT__', $newKey, $content, $count);

        if ($count == 0) {
            $io->write('No Security.salt placeholder to replace.');

            return;
        }

        $result = file_put_contents($config, $content);
        if ($result) {
            $io->write('Updated Security.salt value in config/' . $file);

            return;
        }
        $io->write('Unable to update Security.salt value.');
    }

    /**
     * Set database settings values in a given file
     *
     * @param string $dir The application's root directory.
     * @param \Composer\IO\IOInterface $io IO interface to write to console.
     * @return void
     */
    public static function setDatabaseSettings(string $dir, IOInterface $io): void
    {
        if ($io->isInteractive()) {
            do {
                $io->write('Enter database settings:');

                $dbHost = $io->ask('<info>host (Default to localhost)</info>: ', 'localhost');
                $dbName = $io->ask('<info>name (Default to bakekit)</info>: ', 'bakekit');
                $dbUser = $io->ask('<info>user (Default to root)</info>: ', 'root');
                $dbPass = $io->ask('<info>password (Default to root)</info>: ', 'root');

                try {
                    $pdo = new PDO("mysql:dbname={$dbName};host={$dbHost}", $dbUser, $dbPass);
                    $connected = true;
                    $io->write('Connection established!');

                    $tablesList = [];
                    $result = $pdo->query('SHOW TABLES');
                    if ($result instanceof PDOStatement) {
                        while ($row = $result->fetch(PDO::FETCH_NUM)) {
                            $tablesList[] = $row[0];
                        }
                    } else {
                        $io->writeError('Failed to fetch tables from the database.');
                    }

                    if (!empty($tablesList)) {
                        $connected = false;
                        $io->writeError('Database is not empty!');
                    }
                } catch (PDOException $e) {
                    $connected = false;
                    $io->writeError($e->getMessage());
                }
            } while (!$connected);
        } else {
            $dbHost = 'localhost';
            $dbName = 'my_app';
            $dbUser = 'my_app';
            $dbPass = 'secret';
        }

        $config = $dir . '/config/app_local.php';
        $fileContent = file_get_contents($config);
        if ($fileContent === false) {
            $io->writeError('Failed to read the config file: ' . $config);

            return;
        }
        $content = str_replace(['__DB_HOST__', '__DB_NAME__', '__DB_USER__', '__DB_PASS__'], [$dbHost, $dbName, $dbUser, $dbPass], $fileContent, $count);

        if ($count < 4) {
            $io->writeError('Not all placeholders were replaced.');

            return;
        }

        $result = file_put_contents($config, $content);
        if ($result) {
            $io->write('Updated database values in config/app_local.php');
        } else {
            $io->writeError('Unable to update database values.');
        }
    }
}
