<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\ExtensionHandler;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class ExtensionHandlerTest extends TestCase
{
    protected string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'extensions_test_' . uniqid() . DIRECTORY_SEPARATOR;
        mkdir($this->baseDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem();
        if ($fs->exists($this->baseDir)) {
            $fs->remove($this->baseDir);
        }

        parent::tearDown();
    }

    /**
     * Tests the `discover()` method to ensure it correctly identifies
     * valid extensions and skips invalid or malformed ones.
     *
     * This test creates:
     * - One valid plugin with complete composer.json
     * - One valid plugin with minimal composer.json
     * - One folder without composer.json
     * - One folder with malformed composer.json
     *
     * @return void
     */
    public function testDiscover(): void
    {
        $handler = new ExtensionHandler();

        // Valid extension #1
        $valid1 = $this->baseDir . 'ValidOne/';
        mkdir($valid1, 0777, true);
        file_put_contents($valid1 . 'composer.json', json_encode([
            'description' => 'First valid plugin',
            'license' => ['MIT', 'GPL-3.0'],
            'extra' => ['type' => 'plugin'],
        ]));

        // Valid extension #2 with minimal info
        $valid2 = $this->baseDir . 'ValidTwo/';
        mkdir($valid2, 0777, true);
        file_put_contents($valid2 . 'composer.json', json_encode(['name' => 'valid-two']));

        // Invalid extension: missing composer.json
        $invalid = $this->baseDir . 'NoComposer/';
        mkdir($invalid, 0777, true);

        // Malformed composer.json
        $malformed = $this->baseDir . 'Malformed/';
        mkdir($malformed, 0777, true);
        file_put_contents($malformed . 'composer.json', '{ invalid json');

        $result = $handler->discover($this->baseDir);

        // Assertions
        $this->assertCount(2, $result); // Only ValidOne and ValidTwo should be returned

        $this->assertArrayHasKey('ValidOne', $result);
        $this->assertSame('First valid plugin', $result['ValidOne']['description']);
        $this->assertSame(['MIT', 'GPL-3.0'], $result['ValidOne']['license']);
        $this->assertSame(['type' => 'plugin'], $result['ValidOne']['extra']);

        $this->assertArrayHasKey('ValidTwo', $result);
        $this->assertSame('--- No description ---', $result['ValidTwo']['description']);
        $this->assertSame('--- No license ---', $result['ValidTwo']['license']);
        $this->assertSame([], $result['ValidTwo']['extra']);

        $this->assertArrayNotHasKey('NoComposer', $result);
        $this->assertArrayNotHasKey('Malformed', $result);
    }

    /**
     * Tests the `load()` method with a valid ZIP file.
     *
     * This test ensures that a ZIP file containing a folder named
     * exactly like the plugin name (e.g., "TestPlugin") is correctly
     * validated, moved, and extracted into the target base directory.
     *
     * The test mocks the UploadedFileInterface and its stream,
     * simulates a valid ZIP archive with a readme.txt inside,
     * and asserts that the plugin is installed into the expected folder.
     *
     * @return void
     * @throws \Exception If the ZIP structure or extraction fails.
     */
    public function testLoadWithValidZip(): void
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'valid_zip_');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::OVERWRITE);

        $zip->addFromString('TestPlugin/readme.txt', 'hello world');
        $zip->close();

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getMetadata')->with('uri')->willReturn($zipPath);

        $file = $this->createMock(UploadedFileInterface::class);
        $file->method('getClientFilename')->willReturn('TestPlugin.zip');
        $file->method('getStream')->willReturn($stream);
        $file->method('moveTo')->willReturnCallback(function ($target) use ($zipPath) {
            copy($zipPath, $target);
        });

        $handler = new ExtensionHandler();
        $name = $handler->load($file, $this->baseDir);

        $this->assertEquals('TestPlugin', $name);
        $this->assertFileExists($this->baseDir . 'TestPlugin/readme.txt');

        unlink($zipPath);
    }

    /**
     * Tests the `load()` method with an invalid extension name in the ZIP filename.
     *
     * This test verifies that the `load()` method correctly throws an exception
     * when the uploaded file's name does not pass the `validateName()` check.
     *
     * It simulates a ZIP file named `invalid-name.zip`, which should be rejected
     * because it does not match the expected naming pattern (`/^[A-Z][a-zA-Z0-9]+$/`).
     *
     * @return void
     * @throws \Exception
     */
    public function testLoadWithInvalidNameThrows(): void
    {
        $handler = new ExtensionHandler();

        $zipPath = tempnam(sys_get_temp_dir(), 'bad_zip_');
        file_put_contents($zipPath, 'fake zip');

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getMetadata')->with('uri')->willReturn($zipPath);

        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile->method('getClientFilename')->willReturn('invalid-name.zip');
        $uploadedFile->method('getStream')->willReturn($stream);
        $uploadedFile->method('moveTo')->willReturnCallback(function ($target) use ($zipPath) {
            copy($zipPath, $target);
        });

        $this->expectException(Exception::class);
        $handler->load($uploadedFile, $this->baseDir);

        unlink($zipPath);
    }

    /**
     * Tests the `unload()` method to ensure it successfully removes
     * an existing extension directory from the base directory.
     *
     * This test creates a temporary directory structure with a file inside,
     * simulating an installed extension. After calling `unload()`, it
     * verifies that the directory has been removed.
     *
     * @return void
     * @throws \Exception If directory removal fails.
     */
    public function testUnloadSuccess(): void
    {
        $handler = new ExtensionHandler();
        $target = $this->baseDir . 'ToRemove/';
        mkdir($target, 0777, true);
        file_put_contents($target . 'file.txt', 'something');

        $this->assertDirectoryExists($target);
        $handler->unload('ToRemove', $this->baseDir);
        $this->assertDirectoryDoesNotExist($target);
    }

    /**
     * Tests that the `unload()` method throws an Exception when attempting
     * to remove a directory that does not exist.
     *
     * This test ensures the method correctly handles the case where
     * the specified extension folder is missing by raising an appropriate error.
     *
     * @return void
     * @throws \Exception Expected when the extension directory does not exist.
     */
    public function testUnloadMissingThrows(): void
    {
        $handler = new ExtensionHandler();
        $this->expectException(Exception::class);
        $handler->unload('DoesNotExist', $this->baseDir);
    }

    /**
     * Tests the `readComposerConfig()` method with a valid `composer.json` file.
     *
     * This test creates a temporary directory containing a valid `composer.json` file,
     * then verifies that the method correctly reads and parses the file,
     * returning the expected array with the "name" key.
     *
     * @return void
     */
    public function testReadComposerConfigValid(): void
    {
        $handler = new ExtensionHandler();
        $folder = $this->baseDir . 'WithComposer/';
        mkdir($folder, 0777, true);
        file_put_contents($folder . 'composer.json', '{"name": "vendor/thing"}');

        $result = $handler->readComposerConfig($folder);
        $this->assertEquals('vendor/thing', $result['name']);
    }

    /**
     * Tests the `readComposerConfig()` method when no `composer.json` file exists.
     *
     * This test creates an empty directory without a `composer.json` file
     * and verifies that the method returns an empty array, indicating
     * that no configuration was found.
     *
     * @return void
     */
    public function testReadComposerConfigInvalid(): void
    {
        $handler = new ExtensionHandler();
        $folder = $this->baseDir . 'NoComposer/';
        mkdir($folder, 0777, true);

        $result = $handler->readComposerConfig($folder);
        $this->assertSame([], $result);
    }
}
