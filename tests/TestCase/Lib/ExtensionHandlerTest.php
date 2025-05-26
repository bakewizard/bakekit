<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\ExtensionHandler;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
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
        $this->deleteDir($this->baseDir);
        parent::tearDown();
    }

    protected function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testValidateNameValid(): void
    {
        $handler = new ExtensionHandler();
        $this->expectNotToPerformAssertions();
        $handler->validateName('MyPlugin');
    }

    public function testValidateNameInvalid(): void
    {
        $this->expectException(Exception::class);
        (new ExtensionHandler())->validateName('my-plugin');
    }

    public function testValidateZipMimeInvalid(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getMetadata')->with('uri')->willReturn(__FILE__);

        $file = $this->createMock(UploadedFileInterface::class);
        $file->method('getStream')->willReturn($stream);

        $handler = new ExtensionHandler();
        $this->expectException(Exception::class);
        $handler->validateZipMime($file);
    }

    public function testMoveToTempCreatesFile(): void
    {
        $src = tempnam(sys_get_temp_dir(), 'src_zip_');
        file_put_contents($src, 'dummy content');

        $file = $this->createMock(UploadedFileInterface::class);
        $file->expects($this->once())
            ->method('moveTo')
            ->willReturnCallback(function ($target) use ($src) {
                copy($src, $target);
            });

        $handler = new ExtensionHandler();
        $tempPath = $handler->moveToTemp($file);

        $this->assertFileExists($tempPath);
        unlink($src);
        unlink($tempPath);
    }

    public function testUnpackSuccess(): void
    {
        $handler = new ExtensionHandler();
        $zipFile = tempnam(sys_get_temp_dir(), 'test_zip_');
        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'hello');
        $zip->close();

        $targetDir = $this->baseDir . 'Unpacked/';
        mkdir($targetDir, 0777, true);

        $handler->unpack($zipFile, $targetDir);
        $this->assertFileExists($targetDir . 'readme.txt');

        unlink($zipFile);
    }

    public function testUnpackFailsOnBadZip(): void
    {
        $handler = new ExtensionHandler();
        $zipFile = tempnam(sys_get_temp_dir(), 'bad_zip_');
        file_put_contents($zipFile, 'not a zip');

        $this->expectException(Exception::class);
        $handler->unpack($zipFile, $this->baseDir);

        unlink($zipFile);
    }

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

    public function testUnloadMissingThrows(): void
    {
        $handler = new ExtensionHandler();
        $this->expectException(Exception::class);
        $handler->unload('DoesNotExist', $this->baseDir);
    }

    public function testReadComposerConfigValid(): void
    {
        $handler = new ExtensionHandler();
        $folder = $this->baseDir . 'WithComposer/';
        mkdir($folder, 0777, true);
        file_put_contents($folder . 'composer.json', '{"name": "vendor/thing"}');

        $result = $handler->readComposerConfig($folder);
        $this->assertEquals('vendor/thing', $result['name']);
    }

    public function testReadComposerConfigInvalid(): void
    {
        $handler = new ExtensionHandler();
        $folder = $this->baseDir . 'NoComposer/';
        mkdir($folder, 0777, true);

        $result = $handler->readComposerConfig($folder);
        $this->assertSame([], $result);
    }
}
