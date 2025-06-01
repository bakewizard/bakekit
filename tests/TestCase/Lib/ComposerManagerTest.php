<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\ComposerManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \App\Lib\ComposerManager
 */
class ComposerManagerTest extends TestCase
{
    public function testRequireCallsRunWithCorrectCommand(): void
    {
        $manager = $this->getMockBuilder(ComposerManager::class)
            ->onlyMethods(['run'])
            ->getMock();

        $manager->expects($this->once())
            ->method('run')
            ->with($this->callback(function ($options) {
                return $options['command'] === 'require'
                    && $options['packages'] === ['monolog/monolog:*']
                    && $options['--prefer-dist'] === true;
            }))
            ->willReturn('require success');

        $result = $manager->require(['monolog/monolog:*']);
        $this->assertSame('require success', $result);
    }

    public function testRemoveCallsRunWithCorrectCommand(): void
    {
        $manager = $this->getMockBuilder(ComposerManager::class)
            ->onlyMethods(['run'])
            ->getMock();

        $manager->expects($this->once())
            ->method('run')
            ->with($this->callback(function ($options) {
                return $options['command'] === 'remove'
                    && $options['packages'] === ['vendor/package'];
            }))
            ->willReturn('remove success');

        $result = $manager->remove(['vendor/package']);
        $this->assertSame('remove success', $result);
    }

    public function testGetConfigDataReturnsArrayFromJson(): void
    {
        $tempDir = sys_get_temp_dir() . '/composer_test_' . uniqid();
        mkdir($tempDir);
        $json = [
            'autoload' => [
                'psr-4' => [
                    'Test\\Namespace\\' => 'src/',
                ],
            ],
        ];
        file_put_contents($tempDir . '/composer.json', json_encode($json));

        $manager = new ComposerManager();
        $result = $this->invokeMethod($manager, 'getConfigData', [$tempDir]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('autoload', $result);
        $this->assertEquals('src/', $result['autoload']['psr-4']['Test\\Namespace\\']);

        unlink($tempDir . '/composer.json');
        rmdir($tempDir);
    }

    /**
     * Utility method to call protected/private methods.
     */
    private function invokeMethod(object $object, string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
