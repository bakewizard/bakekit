<?php
declare(strict_types=1);

namespace App\Test;

use Cake\Core\Plugin;
use Migrations\TestSuite\Migrator;

/**
 * TestEnvironmentManager
 *
 * Responsible for preparing the test environment:
 *  - running app migrations
 *  - running plugin migrations
 *  - supporting isolated plugin tests via testsuite name
 *
 * Usage: call from tests/bootstrap.php
 */
final class TestEnvironmentManager
{
    /**
     * The name of the "App" test suite as declared in phpunit.xml.
     * Must match <testsuite name="App"> exactly.
     */
    private const APP_SUITE_NAME = 'App';

    /**
     * Main entry point for preparing the test environment.
     */
    public static function boot(): void
    {
        $migrator = new Migrator();
        $testSuite = self::detectTestSuite();
        $steps = self::buildMigrationSteps($testSuite);
        $migrator->runMany($steps);
    }

    /**
     * Detects which --testsuite is being run from CLI argv.
     *
     * Supports:
     *   --testsuite SuiteName
     *   --testsuite=SuiteName
     *
     * @return string|null Suite name, or null if not specified
     */
    private static function detectTestSuite(): ?string
    {
        $argv = $_SERVER['argv'] ?? [];

        foreach ($argv as $index => $arg) {
            if ($arg === '--testsuite' && isset($argv[$index + 1])) {
                return trim($argv[$index + 1]);
            }
            if (str_starts_with($arg, '--testsuite=')) {
                return trim(substr($arg, strlen('--testsuite=')));
            }
        }

        return null;
    }

    /**
     * Builds migration steps based on the detected test suite.
     *
     * Rules:
     *  - No --testsuite → run all (app + all plugins)
     *  - --testsuite App → run app only
     *  - --testsuite <PluginName> with existing plugin dir → run that plugin only
     *  - --testsuite <unknown> → fallback to all
     *
     * @param string|null $testSuite
     * @return list<array<string, string>|array<never, never>>
     */
    private static function buildMigrationSteps(?string $testSuite): array
    {
        if ($testSuite === null) {
            return self::allMigrationSteps();
        }

        if ($testSuite === self::APP_SUITE_NAME) {
            return [[]]; // only app
        }

        if (self::pluginExists($testSuite)) {
            return [
                [],
                ['plugin' => $testSuite],
            ];
        }

        trigger_error(
            sprintf('TestEnvironmentManager: unknown testsuite "%s", falling back to all migrations.', $testSuite),
            E_USER_WARNING,
        );

        return self::allMigrationSteps();
    }

    /**
     * Returns migration steps for the app and all loaded plugins (deduplicated).
     *
     * @return list<array<string, string>|array<never, never>>
     */
    private static function allMigrationSteps(): array
    {
        $steps = [[]]; // app first
        $seen = [];

        foreach (Plugin::getCollection() as $plugin) {
            $name = $plugin->getName();
            if (!isset($seen[$name])) {
                $steps[] = ['plugin' => $name];
                $seen[$name] = true;
            }
        }

        return $steps;
    }

    /**
     * Checks whether a plugin directory exists under /plugins.
     *
     * @param string $pluginName
     * @return bool
     */
    private static function pluginExists(string $pluginName): bool
    {
        $path = ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $pluginName;

        return is_dir($path);
    }
}
