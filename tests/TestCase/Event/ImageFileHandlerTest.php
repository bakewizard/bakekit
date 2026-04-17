<?php
declare(strict_types=1);

namespace App\Test\TestCase\Event;

use App\Event\ImageFileHandler;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use FilesystemIterator;
use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * App\Event\ImageFileHandler Test Case
 *
 * ImageFileHandler processes uploaded images via Flysystem storage.
 * It receives CakePHP Entity objects (with ->get()) and a Filesystem instance.
 *
 * Constructor:
 *   - throws InvalidArgumentException for unsupported formats
 *
 * processFiles() (via handle()):
 *   - skips entities without tmp_name
 *   - creates thumbnails for each configured alias
 *   - supports square and rectangular thumb sizes
 *   - applies watermark when configured
 *   - supports jpeg / webp / avif output formats
 *
 * removeFiles() (via remove()):
 *   - deletes matching thumbnail files from storage
 *   - ignores files that don't match the pattern
 *
 * @uses \App\Event\ImageFileHandler
 */
class ImageFileHandlerTest extends TestCase
{
    private string $tempDir;
    private Filesystem $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bakekit_img_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        $this->storage = new Filesystem(new LocalFilesystemAdapter($this->tempDir));
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    /**
     * Unsupported format must throw immediately so misconfiguration is caught early.
     */
    public function testConstructorThrowsOnUnsupportedFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported image format: tiff');

        new ImageFileHandler($this->storage, ['format' => 'tiff']);
    }

    /**
     * All three allowed formats should construct without throwing.
     */
    public function testConstructorAcceptsAllowedFormats(): void
    {
        foreach (['jpeg', 'webp', 'avif'] as $format) {
            $handler = new ImageFileHandler($this->storage, ['format' => $format]);
            $this->assertInstanceOf(ImageFileHandler::class, $handler);
        }
    }

    // -------------------------------------------------------------------------
    // handle() — processFiles()
    // -------------------------------------------------------------------------

    /**
     * Entity without tmp_name must be silently skipped — no exception, no output file.
     */
    public function testHandleSkipsEntityWithoutTmpName(): void
    {
        $handler = new ImageFileHandler($this->storage, [
            'format' => 'jpeg',
            'thumbs' => ['small' => [100, 100]],
        ]);

        $file = new Entity(['id' => 1, 'tmp_name' => null, 'path' => '/']);
        $handler->handle([$file]);

        $this->assertEmpty($this->storage->listContents('/')->toArray());
    }

    /**
     * When no thumbs are configured, no thumbnail files should be written.
     * (The source image lives in tempDir too, so we check for the specific thumb name.)
     */
    public function testHandleWithNoThumbsWritesNoFiles(): void
    {
        $src = $this->makeTestImage();

        $handler = new ImageFileHandler($this->storage, [
            'format' => 'jpeg',
            'thumbs' => [],
        ]);

        $file = new Entity(['id' => 99, 'tmp_name' => $src, 'path' => '/']);
        $handler->handle([$file]);

        // No thumbnail should exist for entity id 99 with any alias
        $files = $this->storage->listContents('/', true)
            ->filter(fn($attr) => $attr->isFile() && str_starts_with(basename($attr->path()), '99-'))
            ->toArray();

        $this->assertEmpty($files);
    }

    /**
     * A square thumb size (e.g. 100) should produce a 100×100 canvas.
     */
    public function testHandleCreatesSingleSquareThumbnail(): void
    {
        $src = $this->makeTestImage();

        $handler = new ImageFileHandler($this->storage, [
            'format' => 'jpeg',
            'thumbs' => ['small' => 100],
        ]);

        $file = new Entity(['id' => 5, 'tmp_name' => $src, 'path' => '/']);
        $handler->handle([$file]);

        $thumbPath = $this->tempDir . '/5-small.jpeg';
        $this->assertFileExists($thumbPath);

        [$w, $h] = getimagesize($thumbPath);
        $this->assertSame(100, $w);
        $this->assertSame(100, $h);
    }

    /**
     * Array thumb size [width, height] should produce a thumbnail with those exact canvas dimensions.
     */
    public function testHandleCreatesRectangularThumbnail(): void
    {
        $src = $this->makeTestImage();

        $handler = new ImageFileHandler($this->storage, [
            'format' => 'jpeg',
            'thumbs' => ['wide' => [200, 50]],
        ]);

        $file = new Entity(['id' => 7, 'tmp_name' => $src, 'path' => '/']);
        $handler->handle([$file]);

        $thumbPath = $this->tempDir . '/7-wide.jpeg';
        $this->assertFileExists($thumbPath);

        [$w, $h] = getimagesize($thumbPath);
        $this->assertSame(200, $w);
        $this->assertSame(50, $h);
    }

    /**
     * Multiple thumb aliases must each produce a separate file.
     */
    public function testHandleCreatesMultipleThumbnails(): void
    {
        $src = $this->makeTestImage();

        $handler = new ImageFileHandler($this->storage, [
            'format' => 'jpeg',
            'thumbs' => [
                'small' => [100, 100],
                'medium' => [200, 200],
                'wide' => [300, 100],
            ],
        ]);

        $file = new Entity(['id' => 3, 'tmp_name' => $src, 'path' => '/']);
        $handler->handle([$file]);

        foreach (['small', 'medium', 'wide'] as $alias) {
            $this->assertFileExists($this->tempDir . "/3-{$alias}.jpeg");
        }
    }

    /**
     * Multiple entities in one handle() call must each produce their own thumbnails.
     */
    public function testHandleProcessesMultipleFiles(): void
    {
        $src = $this->makeTestImage();

        $handler = new ImageFileHandler($this->storage, [
            'format' => 'jpeg',
            'thumbs' => ['thumb' => [80, 80]],
        ]);

        $handler->handle([
            new Entity(['id' => 10, 'tmp_name' => $src, 'path' => '/']),
            new Entity(['id' => 11, 'tmp_name' => $src, 'path' => '/']),
        ]);

        $this->assertFileExists($this->tempDir . '/10-thumb.jpeg');
        $this->assertFileExists($this->tempDir . '/11-thumb.jpeg');
    }

    /**
     * Thumbnails should be written into the subdirectory specified by path.
     */
    public function testHandleWritesIntoSubdirectory(): void
    {
        $src = $this->makeTestImage();

        $handler = new ImageFileHandler($this->storage, [
            'format' => 'jpeg',
            'thumbs' => ['sm' => [60, 60]],
        ]);

        $file = new Entity(['id' => 42, 'tmp_name' => $src, 'path' => '/users/avatars']);
        $handler->handle([$file]);

        $this->assertFileExists($this->tempDir . '/users/avatars/42-sm.jpeg');
    }

    /**
     * WebP format should produce a .webp file.
     */
    public function testHandleCreatesWebpThumbnail(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('WebP not supported on this system.');
        }

        $src = $this->makeTestImage();

        $handler = new ImageFileHandler($this->storage, [
            'format' => 'webp',
            'thumbs' => ['web' => [100, 100]],
        ]);

        $file = new Entity(['id' => 20, 'tmp_name' => $src, 'path' => '/']);
        $handler->handle([$file]);

        $this->assertFileExists($this->tempDir . '/20-web.webp');
    }

    /**
     * AVIF format should produce a .avif file.
     */
    public function testHandleCreatesAvifThumbnail(): void
    {
        if (!function_exists('imageavif')) {
            $this->markTestSkipped('AVIF not supported on this system.');
        }

        $src = $this->makeTestImage();

        $handler = new ImageFileHandler($this->storage, [
            'format' => 'avif',
            'thumbs' => ['av' => [100, 100]],
        ]);

        $file = new Entity(['id' => 21, 'tmp_name' => $src, 'path' => '/']);
        $handler->handle([$file]);

        $this->assertFileExists($this->tempDir . '/21-av.avif');
    }

    /**
     * Watermark should be applied without error and the output file must exist.
     */
    public function testHandleAppliesWatermark(): void
    {
        $src = $this->makeTestImage();
        $wm = $this->makeWatermarkImage();

        $handler = new ImageFileHandler($this->storage, [
            'format' => 'jpeg',
            'thumbs' => ['marked' => [200, 200]],
            'watermark' => [
                'image' => $wm,
                'scale' => 0.2,
                'position' => 9,
            ],
        ]);

        $file = new Entity(['id' => 30, 'tmp_name' => $src, 'path' => '/']);
        $handler->handle([$file]);

        $this->assertFileExists($this->tempDir . '/30-marked.jpeg');
    }

    /**
     * Each watermark position (1–9) should produce a file without throwing.
     *
     * @dataProvider watermarkPositionProvider
     */
    #[DataProvider('watermarkPositionProvider')]
    public function testHandleAllWatermarkPositions(int $position): void
    {
        $src = $this->makeTestImage();
        $wm = $this->makeWatermarkImage();

        $handler = new ImageFileHandler($this->storage, [
            'format' => 'jpeg',
            'thumbs' => ['pos' => [150, 150]],
            'watermark' => [
                'image' => $wm,
                'scale' => 0.2,
                'position' => $position,
            ],
        ]);

        $file = new Entity(['id' => $position, 'tmp_name' => $src, 'path' => '/']);
        $handler->handle([$file]);

        $this->assertFileExists($this->tempDir . "/{$position}-pos.jpeg");
    }

    /**
     * @return array<string, array{int}>
     */
    public static function watermarkPositionProvider(): array
    {
        $cases = [];
        foreach (range(1, 9) as $p) {
            $cases["position $p"] = [$p];
        }

        return $cases;
    }

    // -------------------------------------------------------------------------
    // remove() — removeFiles()
    // -------------------------------------------------------------------------

    /**
     * remove() must delete all thumbnails that match the entity's id.
     */
    public function testRemoveDeletesMatchingThumbnails(): void
    {
        $src = $this->makeTestImage();

        $handler = new ImageFileHandler($this->storage, [
            'format' => 'jpeg',
            'thumbs' => [
                'small' => [100, 100],
                'large' => [300, 300],
            ],
        ]);

        $file = new Entity(['id' => 55, 'tmp_name' => $src, 'path' => '/']);
        $handler->handle([$file]);

        $this->assertFileExists($this->tempDir . '/55-small.jpeg');
        $this->assertFileExists($this->tempDir . '/55-large.jpeg');

        $handler->remove([$file]);

        $this->assertFileDoesNotExist($this->tempDir . '/55-small.jpeg');
        $this->assertFileDoesNotExist($this->tempDir . '/55-large.jpeg');
    }

    /**
     * remove() must not delete files belonging to a different entity id.
     */
    public function testRemoveDoesNotDeleteOtherEntityFiles(): void
    {
        $src = $this->makeTestImage();

        $handler = new ImageFileHandler($this->storage, [
            'format' => 'jpeg',
            'thumbs' => ['sm' => [80, 80]],
        ]);

        $file1 = new Entity(['id' => 1, 'tmp_name' => $src, 'path' => '/']);
        $file2 = new Entity(['id' => 2, 'tmp_name' => $src, 'path' => '/']);
        $handler->handle([$file1, $file2]);

        // Remove only file1
        $handler->remove([$file1]);

        $this->assertFileDoesNotExist($this->tempDir . '/1-sm.jpeg');
        $this->assertFileExists($this->tempDir . '/2-sm.jpeg');
    }

    /**
     * remove() on an entity with no matching files should not throw.
     */
    public function testRemoveWithNoMatchingFilesDoesNotThrow(): void
    {
        $handler = new ImageFileHandler($this->storage, [
            'format' => 'jpeg',
            'thumbs' => ['sm' => [80, 80]],
        ]);

        $file = new Entity(['id' => 999, 'tmp_name' => null, 'path' => '/']);

        // Must not throw
        $handler->remove([$file]);
        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Creates a 400×300 JPEG test image with visual content.
     */
    private function makeTestImage(int $width = 400, int $height = 300): string
    {
        $path = $this->tempDir . '/source_' . uniqid() . '.jpg';
        $img = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            $color = imagecolorallocate($img, 0, (int)(255 * $y / $height), 255);
            imageline($img, 0, $y, $width, $y, $color);
        }

        imagefilledellipse($img, $width / 4, $height / 2, 80, 80, imagecolorallocate($img, 255, 60, 60));
        imagefilledrectangle($img, $width / 2, $height / 2 - 40, $width / 2 + 80, $height / 2 + 40, imagecolorallocate($img, 60, 200, 100));

        imagejpeg($img, $path, 90);
        unset($img);

        return $path;
    }

    /**
     * Creates a simple transparent PNG watermark image.
     */
    private function makeWatermarkImage(): string
    {
        $path = $this->tempDir . '/watermark_' . uniqid() . '.png';
        $img = imagecreatetruecolor(100, 100);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 255, 255, 255, 127));
        imageline($img, 0, 0, 100, 100, imagecolorallocate($img, 255, 0, 0));
        imagepng($img, $path);
        unset($img);

        return $path;
    }

    /**
     * Recursively removes a directory and its contents.
     */
    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            ) as $item
        ) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
