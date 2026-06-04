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

/**
 * App\Lib\ExtensionHandler Test Case
 *
 * @uses \App\Lib\ExtensionHandler
 */
class ExtensionHandlerTest extends TestCase
{
    protected string $baseDir;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'extensions_test_' . uniqid() . DIRECTORY_SEPARATOR;
        mkdir($this->baseDir, 0777, true);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $fs = new Filesystem();
        if ($fs->exists($this->baseDir)) {
            $fs->remove($this->baseDir);
        }

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // discover()
    // -------------------------------------------------------------------------

    /**
     * discover() returns only directories that contain a valid composer.json,
     * fills in default values for missing fields, and skips malformed JSON.
     */
    public function testDiscover(): void
    {
        $handler = new ExtensionHandler();

        // Valid extension with full metadata
        $valid1 = $this->baseDir . 'ValidOne/';
        mkdir($valid1, 0777, true);
        file_put_contents($valid1 . 'composer.json', json_encode([
            'description' => 'First valid plugin',
            'license' => ['MIT', 'GPL-3.0'],
            'extra' => ['type' => 'plugin'],
        ]));

        // Valid extension with minimal metadata — defaults must be applied
        $valid2 = $this->baseDir . 'ValidTwo/';
        mkdir($valid2, 0777, true);
        file_put_contents($valid2 . 'composer.json', json_encode(['name' => 'valid-two']));

        // Missing composer.json — must be skipped
        mkdir($this->baseDir . 'NoComposer/', 0777, true);

        // Malformed composer.json — must be skipped
        $malformed = $this->baseDir . 'Malformed/';
        mkdir($malformed, 0777, true);
        file_put_contents($malformed . 'composer.json', '{ invalid json');

        $result = $handler->discover($this->baseDir);

        $this->assertCount(2, $result);

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
     * discover() returns an empty array when the base directory does not exist.
     */
    public function testDiscoverNonExistentDirReturnsEmpty(): void
    {
        $result = (new ExtensionHandler())->discover($this->baseDir . 'does_not_exist/');
        $this->assertSame([], $result);
    }

    /**
     * discover() returns results sorted alphabetically by folder name.
     */
    public function testDiscoverReturnsSortedResults(): void
    {
        foreach (['Zebra', 'Alpha', 'Middle'] as $name) {
            $dir = $this->baseDir . $name . '/';
            mkdir($dir, 0777, true);
            file_put_contents($dir . 'composer.json', json_encode(['description' => $name]));
        }

        $keys = array_keys((new ExtensionHandler())->discover($this->baseDir));
        $this->assertSame(['Alpha', 'Middle', 'Zebra'], $keys);
    }

    // -------------------------------------------------------------------------
    // load()
    // -------------------------------------------------------------------------

    /**
     * load() validates, moves, and extracts a valid ZIP, returning the theme name.
     */
    public function testLoadWithValidZip(): void
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'valid_zip_');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        $zip->addFromString('TestPlugin/readme.txt', 'hello world');
        $zip->close();

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('getMetadata')->with('uri')->willReturn($zipPath);

        $file = $this->createMock(UploadedFileInterface::class);
        $file->expects($this->once())->method('getClientFilename')->willReturn('TestPlugin.zip');
        $file->expects($this->once())->method('getStream')->willReturn($stream);
        $file->expects($this->once())->method('moveTo')->willReturnCallback(
            fn($target) => copy($zipPath, $target),
        );

        $name = (new ExtensionHandler())->load($file, $this->baseDir);

        $this->assertSame('TestPlugin', $name);
        $this->assertFileExists($this->baseDir . 'TestPlugin/readme.txt');

        unlink($zipPath);
    }

    /**
     * load() throws when the filename does not match the required naming pattern.
     */
    public function testLoadWithInvalidNameThrows(): void
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'bad_zip_');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        $zip->addFromString('invalid-name/readme.txt', 'hello');
        $zip->close();

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('getMetadata')->with('uri')->willReturn($zipPath);

        $file = $this->createMock(UploadedFileInterface::class);
        $file->expects($this->once())->method('getClientFilename')->willReturn('invalid-name.zip');
        $file->expects($this->once())->method('getStream')->willReturn($stream);
        $file->expects($this->never())->method('moveTo');

        $this->expectException(Exception::class);
        (new ExtensionHandler())->load($file, $this->baseDir);

        unlink($zipPath);
    }

    /**
     * load() throws when a folder with the same name already exists.
     */
    public function testLoadThrowsWhenFolderAlreadyExists(): void
    {
        mkdir($this->baseDir . 'ExistingTheme/', 0777, true);

        $zipPath = tempnam(sys_get_temp_dir(), 'dup_zip_');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        $zip->addFromString('ExistingTheme/readme.txt', 'hello');
        $zip->close();

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getMetadata')->with('uri')->willReturn($zipPath);

        $file = $this->createMock(UploadedFileInterface::class);
        $file->method('getClientFilename')->willReturn('ExistingTheme.zip');
        $file->method('getStream')->willReturn($stream);
        $file->expects($this->never())->method('moveTo');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/already exists/');

        (new ExtensionHandler())->load($file, $this->baseDir);

        unlink($zipPath);
    }

    // -------------------------------------------------------------------------
    // unload()
    // -------------------------------------------------------------------------

    /**
     * unload() removes an installed extension directory and all its contents.
     */
    public function testUnloadSuccess(): void
    {
        $target = $this->baseDir . 'ToRemove/';
        mkdir($target, 0777, true);
        file_put_contents($target . 'file.txt', 'something');

        $this->assertDirectoryExists($target);
        (new ExtensionHandler())->unload('ToRemove', $this->baseDir);
        $this->assertDirectoryDoesNotExist($target);
    }

    /**
     * unload() throws when the extension directory does not exist.
     */
    public function testUnloadMissingThrows(): void
    {
        $this->expectException(Exception::class);
        (new ExtensionHandler())->unload('DoesNotExist', $this->baseDir);
    }

    // -------------------------------------------------------------------------
    // readComposerConfig()
    // -------------------------------------------------------------------------

    /**
     * readComposerConfig() parses and returns the contents of a valid composer.json.
     */
    public function testReadComposerConfigValid(): void
    {
        $folder = $this->baseDir . 'WithComposer/';
        mkdir($folder, 0777, true);
        file_put_contents($folder . 'composer.json', '{"name": "vendor/thing"}');

        $result = (new ExtensionHandler())->readComposerConfig($folder);
        $this->assertSame('vendor/thing', $result['name']);
    }

    /**
     * readComposerConfig() returns an empty array when composer.json is absent.
     */
    public function testReadComposerConfigMissingReturnsEmpty(): void
    {
        $folder = $this->baseDir . 'NoComposer/';
        mkdir($folder, 0777, true);

        $result = (new ExtensionHandler())->readComposerConfig($folder);
        $this->assertSame([], $result);
    }
}
